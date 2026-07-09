<?php

namespace App\Services\Nomina;

use App\Models\Nomina\HoraExtra;
use App\Models\Nomina\HorarioOperacionDiaria;
use App\Models\Nomina\HorarioUsuarioSemanal;
use App\Models\Nomina\JornadaLaboral;
use App\Models\Nomina\Permiso;
use App\Models\Nomina\RecuperacionTiempo;
use App\Models\Nomina\WorkSession;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class WorkSessionService
{
    private const WITH = ['empleado', 'kiosko', 'jornadaLaboral'];

    private const TOLERANCIA_ENTRADA_MINUTOS = 15;

    private const ALMUERZO_PERMITIDO_MINUTOS = 60;

    private const CAMPOS_MARCACION = [
        'hora_entrada',
        'hora_salida_brake',
        'hora_ingreso_brake',
        'hora_salida_almuerzo',
        'hora_ingreso_almuerzo',
        'hora_salida',
    ];

    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 15;

        return WorkSession::with(self::WITH)
            ->when(! empty($filters['user_id']), fn ($q) => $q->where('user_id', $filters['user_id']))
            ->when(! empty($filters['fecha']), fn ($q) => $q->whereDate('registro_diario', $filters['fecha']))
            ->when(! empty($filters['fecha_inicio']), fn ($q) => $q->whereDate('registro_diario', '>=', $filters['fecha_inicio']))
            ->when(! empty($filters['fecha_fin']), fn ($q) => $q->whereDate('registro_diario', '<=', $filters['fecha_fin']))
            ->when(! empty($filters['search']), function ($query) use ($filters) {
                $search = trim($filters['search']);

                $query->where(function ($q) use ($search) {
                    $q->whereHas('empleado', fn ($empleado) => $empleado->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                    )
                        ->orWhereHas('kiosko', fn ($kiosko) => $kiosko->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%")
                        );
                });
            })
            ->when(! empty($filters['sede_id']), fn ($q) => $q->whereHas('kiosko', fn ($kiosko) => $kiosko->where('sede_id', $filters['sede_id']))
            )
            ->orderByDesc('registro_diario')
            ->paginate($perPage);
    }

    public function getByUuid(string $uuid): WorkSession
    {
        return WorkSession::with(self::WITH)
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    public function resumenAsistencias(array $filters = []): array
    {
        $userId = (int) ($filters['user_id'] ?? 0);
        $inicio = Carbon::parse($filters['fecha_inicio'] ?? now(config('app.timezone'))->startOfMonth())->startOfDay();
        $fin = Carbon::parse($filters['fecha_fin'] ?? now(config('app.timezone')))->startOfDay();

        if (! $userId) {
            throw ValidationException::withMessages(['user_id' => 'Selecciona un empleado para ver el resumen.']);
        }

        if ($fin->lt($inicio)) {
            throw ValidationException::withMessages(['fecha_fin' => 'La fecha final debe ser posterior o igual a la inicial.']);
        }

        $sessions = WorkSession::with('jornadaLaboral')
            ->where('user_id', $userId)
            ->whereBetween('registro_diario', [$inicio->toDateString(), $fin->toDateString()])
            ->get();

        $recuperaciones = RecuperacionTiempo::where('user_id', $userId)
            ->where('status', 'aprobada')
            ->whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()])
            ->get();

        $minutosDebe = $this->minutosEsperadosPeriodo($userId, $inicio, $fin, $sessions);
        $minutosTrabajados = (int) $sessions->sum('minutos_trabajados');
        $minutosTardanza = (int) $sessions->sum('minutos_tardanza');
        $minutosRecuperados = (int) $recuperaciones->sum('minutos_usados');

        return [
            'user_id' => $userId,
            'fecha_inicio' => $inicio->toDateString(),
            'fecha_fin' => $fin->toDateString(),
            'minutos_debe' => $minutosDebe,
            'horas_debe' => round($minutosDebe / 60, 2),
            'minutos_trabajados' => $minutosTrabajados,
            'horas_trabajadas' => round($minutosTrabajados / 60, 2),
            'minutos_tardanza' => $minutosTardanza,
            'dias_tarde' => $sessions->where('minutos_tardanza', '>', 0)->count(),
            'minutos_recuperados' => $minutosRecuperados,
            'minutos_recuperacion_autorizados' => (int) $recuperaciones->sum('minutos_autorizados'),
            'minutos_saldo' => max(0, $minutosDebe - $minutosTrabajados),
            'sesiones' => $sessions->count(),
        ];
    }

    public function store(array $data, bool $validarFlujoKiosko = false): WorkSession
    {
        return DB::transaction(function () use ($data, $validarFlujoKiosko) {
            $this->validarSesionDiariaUnica($data);

            if ($validarFlujoKiosko) {
                $this->validarCreacionDesdeKiosko($data);
            }

            $data = $this->completarJornadaLaboralId($data);
            $data = $this->calcularMinutos($data);

            $session = WorkSession::create($data);

            Log::info('WorkSession creada', [
                'uuid' => $session->uuid,
                'dia' => $session->registro_diario,
            ]);

            return $session->load(self::WITH);
        });
    }

    public function update(string $uuid, array $data, bool $validarFlujoKiosko = false): WorkSession
    {
        return DB::transaction(function () use ($uuid, $data, $validarFlujoKiosko) {
            $session = $this->getByUuid($uuid);
            $avisoKiosko = null;

            if ($validarFlujoKiosko) {
                [$data, $avisoKiosko] = $this->validarActualizacionDesdeKiosko($session, $data);
            }

            $data = $this->calcularMinutos($data, $session);

            $session->update($data);

            Log::info('WorkSession actualizada', [
                'uuid' => $session->uuid,
                'dia' => $session->registro_diario,
            ]);

            $session = $session->fresh(self::WITH);

            if (array_key_exists('hora_salida', $data) && $data['hora_salida']) {
                $this->registrarRecuperacionUsada($session);
                $session = $session->fresh(self::WITH);
            }

            if ($avisoKiosko) {
                $session->setAttribute('aviso_kiosko', $avisoKiosko);
            }

            return $session;
        });
    }

    public function destroy(string $uuid): void
    {
        DB::transaction(function () use ($uuid) {
            $session = $this->getByUuid($uuid);
            $session->delete();

            Log::info('WorkSession eliminada', ['uuid' => $session->uuid]);
        });
    }

    private function calcularMinutos(array $data, ?WorkSession $session = null): array
    {
        $jornada = $this->resolverJornadaOperativa($data, $session);
        $entrada = $data['hora_entrada'] ?? $session?->hora_entrada;
        $salida = $data['hora_salida'] ?? $session?->hora_salida;
        $pausaSale = $data['hora_salida_brake'] ?? $session?->hora_salida_brake;
        $pausaVuelve = $data['hora_ingreso_brake'] ?? $session?->hora_ingreso_brake;
        $almuerzoSale = $data['hora_salida_almuerzo'] ?? $session?->hora_salida_almuerzo;
        $almuerzoVuelve = $data['hora_ingreso_almuerzo'] ?? $session?->hora_ingreso_almuerzo;
        $pausaMinutos = $session?->minutos_pausa ?? 0;
        $almuerzoMinutos = $session?->minutos_almuerzo ?? 0;
        $tardanzaMinutos = 0;
        $pausaPermitida = $this->minutosPausaConfigurada($jornada);
        $almuerzoPermitido = $jornada?->duracion_almuerzo_minutos ?? self::ALMUERZO_PERMITIDO_MINUTOS;

        if ($entrada) {
            $horaEntradaProgramada = $jornada?->hora_entrada ?? '07:00:00';
            $horaEntradaLimite = $jornada->hora_entrada_limite ?? null;
            $entradaReal = Carbon::parse($entrada);
            $entradaBase = Carbon::parse($entradaReal->toDateString().' '.$horaEntradaProgramada);
            $entradaLimite = $horaEntradaLimite
                ? Carbon::parse($entradaReal->toDateString().' '.$horaEntradaLimite)
                : $entradaBase->copy()->addMinutes(self::TOLERANCIA_ENTRADA_MINUTOS);
            $tardanzaMinutos += $entradaReal->greaterThan($entradaLimite) && ! $this->tienePermisoEntradaAprobado((int) ($data['user_id'] ?? $session?->user_id), $entradaReal)
                ? (int) $entradaLimite->diffInMinutes($entradaReal)
                : 0;
        }

        if ($pausaSale && $pausaVuelve) {
            $data['minutos_pausa'] = (int) Carbon::parse($pausaSale)->diffInMinutes(Carbon::parse($pausaVuelve));
            $pausaMinutos = $data['minutos_pausa'];
            if ($pausaPermitida !== null) {
                $tardanzaMinutos += max(0, $pausaMinutos - $pausaPermitida);
            }
        }

        if ($almuerzoSale && $almuerzoVuelve) {
            $almuerzoMinutos = (int) Carbon::parse($almuerzoSale)->diffInMinutes(Carbon::parse($almuerzoVuelve));
            $data['minutos_almuerzo'] = $almuerzoMinutos;

            $regresoProgramado = $jornada?->hora_ingreso_almuerzo
                ? Carbon::parse(Carbon::parse($almuerzoVuelve)->toDateString().' '.$jornada->hora_ingreso_almuerzo)
                : null;
            $regresoReal = Carbon::parse($almuerzoVuelve);

            $tardanzaAlmuerzoHorario = $regresoProgramado && $regresoReal->greaterThan($regresoProgramado)
                ? (int) $regresoProgramado->diffInMinutes($regresoReal)
                : 0;

            $tardanzaAlmuerzoDuracion = max(0, $almuerzoMinutos - $almuerzoPermitido);

            $tardanzaMinutos += max($tardanzaAlmuerzoHorario, $tardanzaAlmuerzoDuracion);
        }

        $data['minutos_tardanza'] = $tardanzaMinutos;

        if ($entrada && $salida) {
            $minutos = (int) Carbon::parse($entrada)->diffInMinutes(Carbon::parse($salida));
            $data['minutos_trabajados'] = max(0, $minutos - $almuerzoMinutos);
        }

        return $data;
    }

    private function completarJornadaLaboralId(array $data): array
    {
        if (! empty($data['horario_laboral_id'])) {
            return $data;
        }

        $horarioUsuario = $this->resolverHorarioUsuarioSemanal($data);
        if ($horarioUsuario?->jornada_laboral_id) {
            $data['horario_laboral_id'] = $horarioUsuario->jornada_laboral_id;

            return $data;
        }

        $jornada = JornadaLaboral::where('status', true)
            ->orderByDesc('updated_at')
            ->first()
            ?? JornadaLaboral::query()->orderByDesc('updated_at')->first();

        if (! $jornada) {
            throw ValidationException::withMessages([
                'horario_laboral_id' => 'No hay jornadas laborales configuradas.',
            ]);
        }

        $data['horario_laboral_id'] = $jornada->id;

        return $data;
    }

    private function tienePermisoEntradaAprobado(int $userId, Carbon $entradaReal): bool
    {
        if (! $userId) {
            return false;
        }

        // Los permisos se solicitan con precisión de minutos, por lo que el
        // minuto final debe quedar cubierto completo.
        $horaEntrada = $entradaReal->format('H:i:00');

        return Permiso::where('user_id', $userId)
            ->whereDate('fecha', $entradaReal->toDateString())
            ->where('status', 'aprobado')
            ->whereIn('tipo', ['llegada_tarde', 'ausencia_parcial'])
            ->whereTime('hora_inicio', '<=', $horaEntrada)
            ->whereTime('hora_fin', '>=', $horaEntrada)
            ->exists();
    }

    private function resolverJornada(array $data, ?WorkSession $session = null): ?JornadaLaboral
    {
        $jornadaId = $data['horario_laboral_id'] ?? $session?->horario_laboral_id;

        if (! $jornadaId) {
            return null;
        }

        if ($session?->relationLoaded('jornadaLaboral') && (int) $session->horario_laboral_id === (int) $jornadaId) {
            return $session->jornadaLaboral;
        }

        return JornadaLaboral::find($jornadaId);
    }

    private function resolverJornadaOperativa(array $data, ?WorkSession $session = null): ?object
    {
        $jornadaBase = $this->resolverJornada($data, $session);
        $fecha = $data['registro_diario'] ?? $session?->registro_diario;

        if (! $fecha) {
            return $jornadaBase ? (object) $jornadaBase->toArray() : null;
        }

        $kioskoId = $data['kiosko_id'] ?? $session?->kiosko_id;
        $userId = $data['user_id'] ?? $session?->user_id;
        $instruccionQuery = HorarioOperacionDiaria::with('jornadaLaboral')
            ->whereDate('fecha', Carbon::parse($fecha)->toDateString())
            ->where('status', true);

        if ($kioskoId) {
            $instruccionQuery->where(function ($q) use ($kioskoId) {
                $q->where('kiosko_device_id', $kioskoId)
                    ->orWhereNull('kiosko_device_id');
            });
        } else {
            $instruccionQuery->whereNull('kiosko_device_id');
        }

        if ($userId) {
            $instruccionQuery->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                    ->orWhereNull('user_id');
            });
        } else {
            $instruccionQuery->whereNull('user_id');
        }

        if ($kioskoId && $userId) {
            $instruccionQuery->orderByRaw(
                'CASE WHEN kiosko_device_id = ? AND user_id = ? THEN 0 WHEN kiosko_device_id IS NULL AND user_id = ? THEN 1 WHEN kiosko_device_id = ? AND user_id IS NULL THEN 2 ELSE 3 END',
                [$kioskoId, $userId, $userId, $kioskoId]
            );
        } elseif ($kioskoId) {
            $instruccionQuery->orderByRaw('CASE WHEN kiosko_device_id = ? THEN 0 ELSE 1 END', [$kioskoId]);
        } elseif ($userId) {
            $instruccionQuery->orderByRaw('CASE WHEN user_id = ? THEN 0 ELSE 1 END', [$userId]);
        }

        $instruccion = $instruccionQuery->first();
        $horarioUsuario = $this->resolverHorarioUsuarioSemanal($data, $session);

        $jornada = $instruccion?->jornadaLaboral ?? $horarioUsuario?->jornadaLaboral ?? $jornadaBase;
        if (! $jornada) {
            return null;
        }

        $operativa = (object) $jornada->toArray();
        $instruccionEsUsuario = $instruccion && $instruccion->user_id !== null;
        foreach ([
            'hora_entrada',
            'hora_entrada_limite',
            'hora_salida_pausa',
            'hora_ingreso_pausa',
            'hora_salida_almuerzo',
            'hora_ingreso_almuerzo',
            'hora_salida',
            'duracion_pausa_minutos',
            'duracion_almuerzo_minutos',
        ] as $campo) {
            if (! property_exists($operativa, $campo)) {
                $operativa->{$campo} = null;
            }

            if ($instruccion && ! $instruccionEsUsuario && $instruccion->{$campo} !== null) {
                $operativa->{$campo} = $instruccion->{$campo};
            }

            if ($horarioUsuario && $horarioUsuario->{$campo} !== null) {
                $operativa->{$campo} = $horarioUsuario->{$campo};
            }

            if ($instruccionEsUsuario && $instruccion->{$campo} !== null) {
                $operativa->{$campo} = $instruccion->{$campo};
            }
        }

        if ($horarioUsuario) {
            $operativa->horario_usuario_semanal = $horarioUsuario->toArray();
        }

        return $operativa;
    }

    private function resolverHorarioUsuarioSemanal(array $data, ?WorkSession $session = null): ?HorarioUsuarioSemanal
    {
        $userId = $data['user_id'] ?? $session?->user_id;
        $fecha = $data['registro_diario'] ?? $session?->registro_diario;

        if (! $userId || ! $fecha) {
            return null;
        }

        return HorarioUsuarioSemanal::with('jornadaLaboral')
            ->where('user_id', $userId)
            ->where('dia_semana', Carbon::parse($fecha)->dayOfWeekIso)
            ->where('status', true)
            ->first();
    }

    private function validarCreacionDesdeKiosko(array $data): void
    {
        if (empty($data['hora_entrada'])) {
            throw ValidationException::withMessages([
                'hora_entrada' => 'El kiosko solo puede iniciar la jornada registrando entrada.',
            ]);
        }
    }

    private function validarSesionDiariaUnica(array $data): void
    {
        DB::table('users')
            ->where('id', $data['user_id'])
            ->lockForUpdate()
            ->first();

        $existeSesion = WorkSession::where('user_id', $data['user_id'])
            ->whereDate('registro_diario', Carbon::parse($data['registro_diario'])->toDateString())
            ->exists();

        if ($existeSesion) {
            throw ValidationException::withMessages([
                'registro_diario' => 'El empleado ya tiene una entrada registrada para este día.',
            ]);
        }
    }

    private function validarActualizacionDesdeKiosko(WorkSession $session, array $data): array
    {
        $campos = array_values(array_filter(
            self::CAMPOS_MARCACION,
            fn (string $campo) => array_key_exists($campo, $data) && $data[$campo] !== null
        ));

        if (count($campos) !== 1) {
            throw ValidationException::withMessages([
                'marcacion' => 'El kiosko solo puede registrar una marcación a la vez.',
            ]);
        }

        $campo = $campos[0];

        if ($campo === 'hora_entrada') {
            throw ValidationException::withMessages([
                'hora_entrada' => 'La entrada ya fue registrada. Solo se permiten pausas, almuerzo o salida.',
            ]);
        }

        if ($session->{$campo}) {
            throw ValidationException::withMessages([
                $campo => 'Esta marcación ya fue registrada y no puede repetirse desde el kiosko.',
            ]);
        }

        $jornada = $this->resolverJornadaOperativa($data, $session);
        $this->validarSecuenciaMarcacion($session, $campo);
        $this->validarDuracionMinimaDescanso($session, $campo, $data[$campo], $jornada);
        $this->validarVentanaHorario($campo, $data[$campo], $jornada);
        $avisoKiosko = $this->ajustarSalidaSegunHoraExtraAprobada($session, $campo, $data, $jornada);

        return [$data, $avisoKiosko];
    }

    private function validarSecuenciaMarcacion(WorkSession $session, string $campo): void
    {
        $pausaAbierta = $session->hora_salida_brake && ! $session->hora_ingreso_brake;
        $almuerzoAbierto = $session->hora_salida_almuerzo && ! $session->hora_ingreso_almuerzo;

        if (in_array($campo, ['hora_salida_brake', 'hora_salida_almuerzo'], true) && ($pausaAbierta || $almuerzoAbierto)) {
            throw ValidationException::withMessages([
                $campo => 'No se puede iniciar otra pausa o almuerzo mientras hay una marcación abierta.',
            ]);
        }

        if ($campo === 'hora_salida') {
            if ($pausaAbierta || $almuerzoAbierto) {
                throw ValidationException::withMessages([
                    'hora_salida' => 'No se puede cerrar la jornada mientras hay pausa o almuerzo abierto.',
                ]);
            }

            return;
        }

        $reglas = [
            'hora_salida_brake' => [
                'requiere_vacios' => ['hora_ingreso_brake', 'hora_salida'],
                'mensaje' => 'La pausa no puede repetirse o registrarse después de la salida.',
            ],
            'hora_ingreso_brake' => [
                'requiere_llenos' => ['hora_salida_brake'],
                'requiere_vacios' => ['hora_salida'],
                'mensaje' => 'Primero debe existir una salida a pausa y la jornada no debe estar cerrada.',
            ],
            'hora_salida_almuerzo' => [
                'requiere_vacios' => ['hora_ingreso_almuerzo', 'hora_salida'],
                'mensaje' => 'El almuerzo no puede repetirse o registrarse después de la salida.',
            ],
            'hora_ingreso_almuerzo' => [
                'requiere_llenos' => ['hora_salida_almuerzo'],
                'requiere_vacios' => ['hora_salida'],
                'mensaje' => 'Primero debe existir una salida a almuerzo y la jornada no debe estar cerrada.',
            ],
        ];

        $regla = $reglas[$campo] ?? null;
        if (! $regla) {
            return;
        }

        foreach ($regla['requiere_llenos'] ?? [] as $requerido) {
            if (! $session->{$requerido}) {
                throw ValidationException::withMessages([$campo => $regla['mensaje']]);
            }
        }

        foreach ($regla['requiere_vacios'] ?? [] as $vacio) {
            if ($session->{$vacio}) {
                throw ValidationException::withMessages([$campo => $regla['mensaje']]);
            }
        }
    }

    private function validarDuracionMinimaDescanso(WorkSession $session, string $campo, string $hora, ?object $jornada): void
    {
        $config = match ($campo) {
            'hora_ingreso_brake' => [
                'salida' => $session->hora_salida_brake,
                'minutos' => $this->minutosPausaConfigurada($jornada),
                'regreso_programado' => $this->horaProgramadaRegresoDescanso($jornada, 'pausa'),
                'campo' => 'hora_ingreso_brake',
                'nombre' => 'break',
            ],
            'hora_ingreso_almuerzo' => [
                'salida' => $session->hora_salida_almuerzo,
                'minutos' => $jornada?->duracion_almuerzo_minutos,
                'regreso_programado' => $this->horaProgramadaRegresoDescanso($jornada, 'almuerzo'),
                'campo' => 'hora_ingreso_almuerzo',
                'nombre' => 'almuerzo',
            ],
            default => null,
        };

        if (! $config || ! $config['salida']) {
            return;
        }

        $regresoDescanso = Carbon::parse($hora);
        $regresoPermitido = $config['regreso_programado']
            ? Carbon::parse($regresoDescanso->toDateString().' '.$config['regreso_programado'])
            : null;

        if (! $regresoPermitido && $config['minutos'] !== null) {
            $salidaProgramada = $this->horaProgramadaSalidaDescanso($jornada, $config['nombre'] === 'break' ? 'pausa' : 'almuerzo');
            $regresoPermitido = $salidaProgramada
                ? Carbon::parse($regresoDescanso->toDateString().' '.$salidaProgramada)->addMinutes((int) $config['minutos'])
                : null;
        }

        if (! $regresoPermitido) {
            return;
        }

        if ($regresoDescanso->lessThan($regresoPermitido)) {
            $segundosRestantes = $regresoPermitido->getTimestamp() - $regresoDescanso->getTimestamp();
            $minutosRestantes = max(1, (int) ceil($segundosRestantes / 60));

            throw ValidationException::withMessages([
                $config['campo'] => "Aún estás en {$config['nombre']}. Tu próximo registro será en {$minutosRestantes} minuto(s).",
            ]);
        }
    }

    private function validarVentanaHorario(string $campo, string $hora, ?object $jornada): void
    {
        if (! $jornada) {
            return;
        }

        $actual = $this->minutosHora($hora);
        $ventanas = [
            'hora_salida_brake' => [
                $this->minutosHora($this->horaProgramadaSalidaDescanso($jornada, 'pausa')),
                $this->minutosHora($this->horaProgramadaRegresoDescanso($jornada, 'pausa')),
                'La salida a break solo se permite dentro del horario laboral configurado.',
                true,
            ],
            'hora_ingreso_brake' => [
                $this->minutosHora($this->horaProgramadaRegresoDescanso($jornada, 'pausa')),
                null,
                'El regreso de break solo se permite desde la hora laboral configurada.',
                true,
            ],
            'hora_salida_almuerzo' => [
                $this->minutosHora($this->horaProgramadaSalidaDescanso($jornada, 'almuerzo')),
                $this->minutosHora($this->horaProgramadaRegresoDescanso($jornada, 'almuerzo')),
                'La salida a almuerzo solo se permite dentro del horario laboral configurado.',
                true,
            ],
            'hora_ingreso_almuerzo' => [
                $this->minutosHora($this->horaProgramadaRegresoDescanso($jornada, 'almuerzo')),
                null,
                'El regreso de almuerzo solo se permite desde la hora laboral configurada.',
                true,
            ],
            'hora_salida' => [
                $this->minutosHora($jornada->hora_salida ?? null),
                null,
                'La salida laboral solo se permite desde la hora de salida configurada.',
                false,
            ],
        ];

        [$inicio, $fin, $mensaje, $requiereHorario] = $ventanas[$campo] ?? [null, null, null, false];
        if ($inicio === null) {
            if ($requiereHorario) {
                throw ValidationException::withMessages([$campo => 'Esta marcación no tiene horario laboral configurado.']);
            }

            return;
        }

        if ($actual < $inicio || ($fin !== null && $actual >= $fin)) {
            throw ValidationException::withMessages([$campo => $mensaje]);
        }
    }

    private function horaProgramadaSalidaDescanso(?object $jornada, string $tipo): ?string
    {
        return match ($tipo) {
            'pausa' => $jornada?->hora_salida_pausa ?? null,
            'almuerzo' => $jornada?->hora_salida_almuerzo ?? null,
            default => null,
        };
    }

    private function horaProgramadaRegresoDescanso(?object $jornada, string $tipo): ?string
    {
        $horaRegreso = match ($tipo) {
            'pausa' => $jornada?->hora_ingreso_pausa ?? null,
            'almuerzo' => $jornada?->hora_ingreso_almuerzo ?? null,
            default => null,
        };

        if ($horaRegreso) {
            return $horaRegreso;
        }

        $horaSalida = $this->horaProgramadaSalidaDescanso($jornada, $tipo);
        $duracion = match ($tipo) {
            'pausa' => $this->minutosPausaConfigurada($jornada),
            'almuerzo' => $jornada?->duracion_almuerzo_minutos ?? null,
            default => null,
        };

        if (! $horaSalida || $duracion === null) {
            return null;
        }

        return Carbon::parse($horaSalida)->addMinutes((int) $duracion)->format('H:i:s');
    }

    private function ajustarSalidaSegunHoraExtraAprobada(WorkSession $session, string $campo, array &$data, ?object $jornada): ?string
    {
        if ($campo !== 'hora_salida' || ! $jornada?->hora_salida) {
            return null;
        }

        $hora = $data[$campo];
        $salidaProgramada = $this->minutosHora($jornada->hora_salida);
        $salidaReal = $this->minutosHora($hora);

        if ($salidaProgramada === null || $salidaReal === null || $salidaReal <= $salidaProgramada) {
            return null;
        }

        $fecha = Carbon::parse($session->registro_diario)->toDateString();
        $minutosAdicionales = $salidaReal - $salidaProgramada;
        $minutosRecuperacion = RecuperacionTiempo::where('user_id', $session->user_id)
            ->whereDate('fecha', $fecha)
            ->where('status', 'aprobada')
            ->get()
            ->sum(fn (RecuperacionTiempo $item) => max(0, $item->minutos_autorizados - $item->minutos_usados));

        if ($minutosRecuperacion > 0 && $minutosAdicionales <= $minutosRecuperacion) {
            return 'Salida registrada dentro del tiempo autorizado para recuperación.';
        }

        if ($minutosRecuperacion > 0 && $minutosAdicionales > $minutosRecuperacion) {
            return "Salida registrada. La recuperación autorizada cubre {$minutosRecuperacion} minuto(s); el excedente no sera reconocido sin aprobación adicional.";
        }

        $horasAprobadas = HoraExtra::where('user_id', $session->user_id)
            ->whereDate('fecha', $fecha)
            ->where('status', 'aprobada')
            ->where(function ($q) use ($session) {
                $q->whereNull('kiosko_device_id');

                if ($session->kiosko_id) {
                    $q->orWhere('kiosko_device_id', $session->kiosko_id);
                }
            })
            ->sum('horas');

        if ((float) $horasAprobadas <= 0) {
            return 'Salida registrada. El tiempo adicional no sera reconocido porque no tiene horas extra autorizadas.';
        }

        $minutosAprobados = (int) round(((float) $horasAprobadas) * 60);
        $salidaMaximaAutorizada = $salidaProgramada + $minutosAprobados;

        if ($salidaReal > $salidaMaximaAutorizada) {
            return "Salida registrada. Tus horas extras aprobadas cubren hasta {$horasAprobadas} hora(s); el excedente no sera reconocido sin aprobacion adicional.";
        }

        return 'Salida registrada dentro del tiempo de horas extra autorizado.';
    }

    private function registrarRecuperacionUsada(WorkSession $session): void
    {
        $jornada = $this->resolverJornadaOperativa([], $session);
        if (! $jornada?->hora_salida || ! $session->hora_salida) {
            return;
        }

        $salidaProgramada = $this->minutosHora($jornada->hora_salida);
        $salidaReal = $this->minutosHora((string) $session->hora_salida);
        if ($salidaProgramada === null || $salidaReal === null || $salidaReal <= $salidaProgramada) {
            return;
        }

        $minutosPendientes = $salidaReal - $salidaProgramada;
        $recuperaciones = RecuperacionTiempo::where('user_id', $session->user_id)
            ->whereDate('fecha', Carbon::parse($session->registro_diario)->toDateString())
            ->where('status', 'aprobada')
            ->orderBy('hora_inicio')
            ->lockForUpdate()
            ->get();

        foreach ($recuperaciones as $recuperacion) {
            if ($minutosPendientes <= 0) {
                break;
            }

            $disponible = max(0, $recuperacion->minutos_autorizados - $recuperacion->minutos_usados);
            if ($disponible <= 0) {
                continue;
            }

            $usar = min($disponible, $minutosPendientes);
            $recuperacion->update([
                'work_session_id' => $session->id,
                'minutos_usados' => $recuperacion->minutos_usados + $usar,
            ]);

            $minutosPendientes -= $usar;
        }
    }

    private function minutosEsperadosPeriodo(int $userId, Carbon $inicio, Carbon $fin, $sessions): int
    {
        $total = 0;
        $sesionesPorFecha = $sessions->keyBy(fn (WorkSession $session) => Carbon::parse($session->registro_diario)->toDateString());
        $cursor = $inicio->copy();

        while ($cursor->lte($fin)) {
            $horario = HorarioUsuarioSemanal::where('user_id', $userId)
                ->where('dia_semana', $cursor->dayOfWeekIso)
                ->where('status', true)
                ->first();

            if ($horario) {
                $total += $this->minutosEsperadosHorario($horario);
            } else {
                $session = $sesionesPorFecha->get($cursor->toDateString());
                if ($session?->jornadaLaboral) {
                    $total += $this->minutosEsperadosHorario($session->jornadaLaboral);
                }
            }

            $cursor->addDay();
        }

        return $total;
    }

    private function minutosEsperadosHorario(object $horario): int
    {
        $entrada = $this->minutosHora($horario->hora_entrada ?? null);
        $salida = $this->minutosHora($horario->hora_salida ?? null);
        if ($entrada === null || $salida === null) {
            return 0;
        }

        if ($salida <= $entrada) {
            $salida += 24 * 60;
        }

        $almuerzo = (int) ($horario->duracion_almuerzo_minutos ?? 0);
        $salidaAlmuerzo = $this->minutosHora($horario->hora_salida_almuerzo ?? null);
        $ingresoAlmuerzo = $this->minutosHora($horario->hora_ingreso_almuerzo ?? null);
        if ($salidaAlmuerzo !== null && $ingresoAlmuerzo !== null) {
            $almuerzo = max(0, $ingresoAlmuerzo - $salidaAlmuerzo);
        }

        return max(0, ($salida - $entrada) - $almuerzo);
    }

    private function minutosPausaConfigurada(?object $jornada): ?int
    {
        $minutos = $jornada?->duracion_pausa_minutos ?? null;
        if ($minutos === null) {
            return null;
        }

        return max(1, (int) $minutos);
    }

    private function minutosHora(?string $hora): ?int
    {
        if (! $hora) {
            return null;
        }

        $valor = Carbon::parse($hora);

        return ($valor->hour * 60) + $valor->minute;
    }
}
