<?php

namespace App\Services\Nomina;

use App\Models\Nomina\WorkSession;
use App\Models\Nomina\HorarioOperacionDiaria;
use App\Models\Nomina\JornadaLaboral;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class WorkSessionService
{
    private const WITH = ['empleado', 'kiosko', 'jornadaLaboral'];
    private const PAUSA_PERMITIDA_MINUTOS = 15;
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
            ->when(!empty($filters['user_id']), fn($q) => $q->where('user_id', $filters['user_id']))
            ->when(!empty($filters['fecha']), fn($q) => $q->whereDate('registro_diario', $filters['fecha']))
            ->when(!empty($filters['fecha_inicio']), fn($q) => $q->whereDate('registro_diario', '>=', $filters['fecha_inicio']))
            ->when(!empty($filters['fecha_fin']), fn($q) => $q->whereDate('registro_diario', '<=', $filters['fecha_fin']))
            ->when(!empty($filters['search']), function ($query) use ($filters) {
                $search = trim($filters['search']);

                $query->where(function ($q) use ($search) {
                    $q->whereHas('empleado', fn ($empleado) =>
                        $empleado->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                    )
                    ->orWhereHas('kiosko', fn ($kiosko) =>
                        $kiosko->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%")
                    );
                });
            })
            ->when(!empty($filters['sede_id']), fn ($q) =>
                $q->whereHas('kiosko', fn ($kiosko) => $kiosko->where('sede_id', $filters['sede_id']))
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

    public function store(array $data, bool $validarFlujoKiosko = false): WorkSession
    {
        return DB::transaction(function () use ($data, $validarFlujoKiosko) {
            $this->validarSesionDiariaUnica($data);

            if ($validarFlujoKiosko) {
                $this->validarCreacionDesdeKiosko($data);
            }

            $data = $this->calcularMinutos($data);

            $session = WorkSession::create($data);

            Log::info('WorkSession creada', [
                'uuid' => $session->uuid,
                'dia'  => $session->registro_diario,
            ]);

            return $session->load(self::WITH);
        });
    }

    public function update(string $uuid, array $data, bool $validarFlujoKiosko = false): WorkSession
    {
        return DB::transaction(function () use ($uuid, $data, $validarFlujoKiosko) {
            $session = $this->getByUuid($uuid);

            if ($validarFlujoKiosko) {
                $this->validarActualizacionDesdeKiosko($session, $data);
            }

            $data = $this->calcularMinutos($data, $session);

            $session->update($data);

            Log::info('WorkSession actualizada', [
                'uuid' => $session->uuid,
                'dia'  => $session->registro_diario,
            ]);

            return $session->fresh(self::WITH);
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
        $entrada   = $data['hora_entrada']         ?? $session?->hora_entrada;
        $salida    = $data['hora_salida']           ?? $session?->hora_salida;
        $pausaSale = $data['hora_salida_brake']     ?? $session?->hora_salida_brake;
        $pausaVuelve = $data['hora_ingreso_brake'] ?? $session?->hora_ingreso_brake;
        $almuerzoSale = $data['hora_salida_almuerzo'] ?? $session?->hora_salida_almuerzo;
        $almuerzoVuelve = $data['hora_ingreso_almuerzo'] ?? $session?->hora_ingreso_almuerzo;
        $pausaMinutos = $session?->minutos_pausa ?? 0;
        $almuerzoMinutos = $session?->minutos_almuerzo ?? 0;
        $tardanzaMinutos = 0;
        $pausaPermitida = $jornada?->duracion_pausa_minutos ?? self::PAUSA_PERMITIDA_MINUTOS;
        $almuerzoPermitido = $jornada?->duracion_almuerzo_minutos ?? self::ALMUERZO_PERMITIDO_MINUTOS;

        if ($entrada) {
            $horaEntradaProgramada = $jornada?->hora_entrada ?? '07:00:00';
            $entradaReal = Carbon::parse($entrada);
            $entradaBase = Carbon::parse($entradaReal->toDateString() . ' ' . $horaEntradaProgramada);
            $tardanzaMinutos += $entradaReal->greaterThan($entradaBase)
                ? (int) $entradaBase->diffInMinutes($entradaReal)
                : 0;
        }

        if ($pausaSale && $pausaVuelve) {
            $data['minutos_pausa'] = (int) Carbon::parse($pausaSale)->diffInMinutes(Carbon::parse($pausaVuelve));
            $pausaMinutos = $data['minutos_pausa'];
            $tardanzaMinutos += max(0, $pausaMinutos - $pausaPermitida);
        }

        if ($almuerzoSale && $almuerzoVuelve) {
            $almuerzoMinutos = (int) Carbon::parse($almuerzoSale)->diffInMinutes(Carbon::parse($almuerzoVuelve));
            $data['minutos_almuerzo'] = $almuerzoMinutos;
            $tardanzaMinutos += max(0, $almuerzoMinutos - $almuerzoPermitido);
        }

        $data['minutos_tardanza'] = $tardanzaMinutos;

        if ($entrada && $salida) {
            $minutos = (int) Carbon::parse($entrada)->diffInMinutes(Carbon::parse($salida));
            $data['minutos_trabajados'] = max(0, $minutos - $pausaMinutos - $almuerzoMinutos);
        }

        return $data;
    }

    private function resolverJornada(array $data, ?WorkSession $session = null): ?JornadaLaboral
    {
        $jornadaId = $data['horario_laboral_id'] ?? $session?->horario_laboral_id;

        if (!$jornadaId) {
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

        if (!$fecha) {
            return $jornadaBase ? (object) $jornadaBase->toArray() : null;
        }

        $kioskoId = $data['kiosko_id'] ?? $session?->kiosko_id;
        $instruccionQuery = HorarioOperacionDiaria::with('jornadaLaboral')
            ->whereDate('fecha', Carbon::parse($fecha)->toDateString())
            ->where('status', true);

        if ($kioskoId) {
            $instruccionQuery->where(function ($q) use ($kioskoId) {
                $q->where('kiosko_device_id', $kioskoId)
                    ->orWhereNull('kiosko_device_id');
            })->orderByRaw('CASE WHEN kiosko_device_id = ? THEN 0 ELSE 1 END', [$kioskoId]);
        } else {
            $instruccionQuery->whereNull('kiosko_device_id');
        }

        $instruccion = $instruccionQuery->first();

        $jornada = $instruccion?->jornadaLaboral ?? $jornadaBase;
        if (!$jornada) {
            return null;
        }

        $operativa = (object) $jornada->toArray();
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
            if ($instruccion && $instruccion->{$campo} !== null) {
                $operativa->{$campo} = $instruccion->{$campo};
            }
        }

        return $operativa;
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

    private function validarActualizacionDesdeKiosko(WorkSession $session, array $data): void
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
        $this->validarVentanaHorario($campo, $data[$campo], $jornada);
    }

    private function validarSecuenciaMarcacion(WorkSession $session, string $campo): void
    {
        $pausaAbierta = $session->hora_salida_brake && !$session->hora_ingreso_brake;
        $almuerzoAbierto = $session->hora_salida_almuerzo && !$session->hora_ingreso_almuerzo;

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
        if (!$regla) {
            return;
        }

        foreach ($regla['requiere_llenos'] ?? [] as $requerido) {
            if (!$session->{$requerido}) {
                throw ValidationException::withMessages([$campo => $regla['mensaje']]);
            }
        }

        foreach ($regla['requiere_vacios'] ?? [] as $vacio) {
            if ($session->{$vacio}) {
                throw ValidationException::withMessages([$campo => $regla['mensaje']]);
            }
        }
    }

    private function validarVentanaHorario(string $campo, string $hora, ?object $jornada): void
    {
        if (!$jornada) {
            return;
        }

        $actual = $this->minutosHora($hora);
        $ventanas = [
            'hora_salida_brake' => [
                $this->minutosHora($jornada->hora_salida_pausa ?? null),
                $this->minutosHora($jornada->hora_ingreso_pausa ?? null)
                    ?? (($this->minutosHora($jornada->hora_salida_pausa ?? null) ?? 0) + ($jornada->duracion_pausa_minutos ?? self::PAUSA_PERMITIDA_MINUTOS)),
                'La salida a pausa solo se permite dentro del horario de pausa configurado.',
            ],
            'hora_ingreso_brake' => [
                $this->minutosHora($jornada->hora_salida_pausa ?? null),
                null,
                'El regreso de pausa solo se permite después de iniciar la pausa.',
            ],
            'hora_salida_almuerzo' => [
                $this->minutosHora($jornada->hora_salida_almuerzo ?? null),
                $this->minutosHora($jornada->hora_ingreso_almuerzo ?? null),
                'La salida a almuerzo solo se permite dentro del horario de almuerzo configurado.',
            ],
            'hora_ingreso_almuerzo' => [
                $this->minutosHora($jornada->hora_salida_almuerzo ?? null),
                null,
                'El regreso de almuerzo solo se permite después de iniciar el almuerzo.',
            ],
            'hora_salida' => [
                $this->minutosHora($jornada->hora_salida ?? null),
                null,
                'La salida laboral solo se permite desde la hora de salida configurada.',
            ],
        ];

        [$inicio, $fin, $mensaje] = $ventanas[$campo] ?? [null, null, null];
        if ($inicio === null) {
            return;
        }

        if ($actual < $inicio || ($fin !== null && $actual >= $fin)) {
            throw ValidationException::withMessages([$campo => $mensaje]);
        }
    }

    private function minutosHora(?string $hora): ?int
    {
        if (!$hora) {
            return null;
        }

        $valor = Carbon::parse($hora);
        return ($valor->hour * 60) + $valor->minute;
    }
}
