<?php

namespace App\Services\Nomina;

use App\Models\Nomina\HoraExtra;
use App\Models\Nomina\HorarioOperacionDiaria;
use App\Models\Nomina\HorarioUsuarioSemanal;
use App\Models\Nomina\JornadaLaboral;
use App\Models\Nomina\Permiso;
use App\Models\Nomina\RecuperacionTiempo;
use App\Models\Nomina\WorkSession;
use App\RolEnum;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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

        return $this->aplicarFiltros(WorkSession::with(self::WITH), $filters)
            // Las sesiones con tardanza aparecen primero; dentro de cada grupo,
            // de mayor a menor tardanza y luego por fecha más reciente.
            ->orderByRaw('CASE WHEN minutos_tardanza > 0 THEN 0 ELSE 1 END')
            ->orderByDesc('minutos_tardanza')
            ->orderByDesc('registro_diario')
            ->paginate($perPage)
            // No se expone si un Administrador marcó por foto de respaldo
            // (cédula) en este listado.
            ->through(function (WorkSession $session) {
                if ((int) $session->empleado?->role_id === RolEnum::ADMINISTRADOR->value) {
                    $session->foto_respaldo = null;
                }

                return $session;
            });
    }

    private function aplicarFiltros(Builder $query, array $filters): Builder
    {
        return $query
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
            );
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

    /**
     * Agrega minutos de tardanza y trabajados sobre el conjunto de sesiones que cumplen
     * los filtros activos de la vista de asistencia (sin exigir user_id), para tarjetas KPI.
     */
    public function resumenFiltrado(array $filters = []): array
    {
        $query = fn () => $this->aplicarFiltros(WorkSession::query(), $filters);

        return [
            'total_sesiones' => $query()->count(),
            'minutos_trabajados' => (int) $query()->sum('minutos_trabajados'),
            'minutos_tardanza' => (int) $query()->sum('minutos_tardanza'),
            'dias_tarde' => $query()->where('minutos_tardanza', '>', 0)->count(),
            'empleados_con_tardanza' => $query()->where('minutos_tardanza', '>', 0)->pluck('user_id')->unique()->count(),
        ];
    }

    /**
     * Agrega minutos de tardanza por empleado dentro de los filtros activos, para exportar a Excel.
     */
    public function exportarTardanzaPorUsuario(array $filters = []): \Illuminate\Support\Collection
    {
        return $this->aplicarFiltros(WorkSession::query(), $filters)
            ->selectRaw('user_id, COUNT(*) as total_sesiones, SUM(minutos_trabajados) as minutos_trabajados, SUM(minutos_tardanza) as minutos_tardanza, SUM(CASE WHEN minutos_tardanza > 0 THEN 1 ELSE 0 END) as dias_tarde')
            ->groupBy('user_id')
            ->with('empleado:id,name,email')
            ->orderByDesc('minutos_tardanza')
            ->get();
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
            $data = $this->guardarFotoRespaldo($data);

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

            // Un permiso de llegada_tarde/ausencia_parcial aprobado que ya estaba
            // vigente extiende el plazo hasta su hora_fin: si llega dentro del
            // permiso no hay tardanza, y si llega después solo se cobra el
            // excedente sobre esa hora_fin (no toda la tardanza desde la jornada).
            $permisoEntrada = $this->permisoEntradaAprobado((int) ($data['user_id'] ?? $session?->user_id), $entradaReal);
            $limiteEfectivo = $entradaLimite;
            if ($permisoEntrada) {
                $finPermiso = Carbon::parse($entradaReal->toDateString().' '.$permisoEntrada->hora_fin);
                if ($finPermiso->greaterThan($limiteEfectivo)) {
                    $limiteEfectivo = $finPermiso;
                }
            }

            $tardanzaMinutos += $entradaReal->greaterThan($limiteEfectivo)
                ? (int) $limiteEfectivo->diffInMinutes($entradaReal)
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

            // Tardanza se mide contra la salida real + duración configurada, no contra
            // la hora fija de regreso: si el empleado sale tarde a almuerzo, su ventana
            // de regreso corre desde su salida real, no desde el horario programado.
            $tardanzaMinutos += max(0, $almuerzoMinutos - $almuerzoPermitido);
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

    // La foto de respaldo se envía como data URL base64 (kiosko marcando por
    // cédula tras fallar el reconocimiento facial). Si falla la decodificación
    // o el guardado, se descarta el campo pero la marcación se crea igual: la
    // foto es solo evidencia adicional, nunca debe bloquear el registro.
    private function guardarFotoRespaldo(array $data): array
    {
        if (empty($data['foto_respaldo']) || ! str_starts_with($data['foto_respaldo'], 'data:image/')) {
            unset($data['foto_respaldo']);

            return $data;
        }

        try {
            [$meta, $contenido] = explode(',', $data['foto_respaldo'], 2);
            preg_match('/data:image\/(\w+);base64/', $meta, $matches);
            $extension = $matches[1] ?? 'jpg';
            $binario = base64_decode($contenido, true);

            if ($binario === false) {
                throw new \RuntimeException('Contenido base64 inválido.');
            }

            $path = 'nomina/work_session_photos/'.Str::uuid().'.'.$extension;
            Storage::disk('public')->put($path, $binario);
            $data['foto_respaldo'] = $path;
        } catch (\Throwable $e) {
            Log::warning('No se pudo guardar la foto de respaldo del kiosko', ['error' => $e->getMessage()]);
            unset($data['foto_respaldo']);
        }

        return $data;
    }

    // Devuelve el permiso de llegada_tarde/ausencia_parcial aprobado que ya
    // estaba vigente al momento de la entrada (hora_inicio <= entrada), sin
    // exigir que la entrada caiga dentro de hora_fin: eso lo resuelve el
    // llamador, que cobra tardanza solo por el excedente sobre hora_fin si
    // llegó después de que el permiso venció. Si hay varios, se toma el de
    // hora_fin más tardía (el más favorable).
    private function permisoEntradaAprobado(int $userId, Carbon $entradaReal): ?Permiso
    {
        if (! $userId) {
            return null;
        }

        $horaEntrada = $entradaReal->format('H:i:00');

        return Permiso::where('user_id', $userId)
            ->whereDate('fecha', $entradaReal->toDateString())
            ->where('status', 'aprobado')
            ->whereIn('tipo', ['llegada_tarde', 'ausencia_parcial'])
            ->whereTime('hora_inicio', '<=', $horaEntrada)
            ->orderByDesc('hora_fin')
            ->first();
    }

    private function tienePermisoSalidaAprobado(int $userId, Carbon $salidaReal): bool
    {
        if (! $userId) {
            return false;
        }

        // Los permisos se solicitan con precisión de minutos, por lo que el
        // minuto de salida debe quedar cubierto completo.
        $horaSalida = $salidaReal->format('H:i:00');

        return Permiso::where('user_id', $userId)
            ->whereDate('fecha', $salidaReal->toDateString())
            ->where('status', 'aprobado')
            ->whereIn('tipo', ['salida_temprana', 'ausencia_parcial'])
            ->whereTime('hora_inicio', '<=', $horaSalida)
            ->whereTime('hora_fin', '>=', $horaSalida)
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
        $this->validarVentanaHorario($campo, $data[$campo], $jornada, $session);
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
                'requiere_vacios' => ['hora_ingreso_brake', 'hora_salida', 'hora_salida_almuerzo'],
                'mensaje' => 'La pausa no puede registrarse: ya se tomó el almuerzo o ya se cerró la jornada.',
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

    // Ni el regreso de pausa (hora_ingreso_brake) ni el de almuerzo
    // (hora_ingreso_almuerzo) tienen mínimo obligatorio: si el empleado vuelve
    // antes de la duración configurada, se registra tal cual; si vuelve
    // después, calcularMinutos() marca el excedente como tardanza.

    private function validarVentanaHorario(string $campo, string $hora, ?object $jornada, ?WorkSession $session = null): void
    {
        if (! $jornada) {
            return;
        }

        $actual = $this->minutosHora($hora);
        $ventanas = [
            // El límite superior es el inicio del almuerzo, no el regreso programado
            // de la pausa: así se permite salir a pausa tarde (después de su hora
            // programada) siempre que aún no haya empezado el almuerzo.
            'hora_salida_brake' => [
                $this->minutosHora($this->horaProgramadaSalidaDescanso($jornada, 'pausa')),
                $this->minutosHora($this->horaProgramadaSalidaDescanso($jornada, 'almuerzo')),
                'La salida a break solo se permite antes de que inicie el almuerzo.',
                true,
            ],
            // hora_ingreso_brake no tiene ventana: el regreso de pausa se acepta en
            // cualquier momento (temprano se registra tal cual, tarde queda como
            // tardanza vía calcularMinutos()).
            // El límite superior es la hora de salida laboral, no el regreso
            // programado de almuerzo: así se permite salir a almorzar tarde
            // (después de su hora programada) siempre que la jornada no haya cerrado.
            'hora_salida_almuerzo' => [
                $this->minutosHora($this->horaProgramadaSalidaDescanso($jornada, 'almuerzo')),
                $this->minutosHora($jornada->hora_salida ?? null),
                'La salida a almuerzo solo se permite antes de la hora de salida laboral.',
                true,
            ],
            // hora_ingreso_almuerzo no tiene ventana: el regreso de almuerzo se
            // acepta en cualquier momento (temprano se registra tal cual, tarde
            // queda como tardanza vía calcularMinutos()).
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
            // Salida temprana autorizada por permiso aprobado: no se bloquea.
            if ($campo === 'hora_salida' && $actual < $inicio && $session
                && $this->tienePermisoSalidaAprobado((int) $session->user_id, Carbon::parse($hora))) {
                return;
            }

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
