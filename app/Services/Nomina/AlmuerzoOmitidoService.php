<?php

namespace App\Services\Nomina;

use App\Models\Nomina\HoraExtra;
use App\Models\Nomina\JornadaLaboral;
use App\Models\Nomina\WorkSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Detecta los días en que un empleado no tomó (completo) el almuerzo configurado
 * en su jornada y permite convertir ese tiempo en solicitudes de hora extra
 * pendientes de aprobación. No modifica las work_sessions: solo lee.
 */
class AlmuerzoOmitidoService
{
    // Un turno de al menos 6 horas continuas contempla pausa de almuerzo.
    private const TURNO_MINIMO_CON_ALMUERZO_MINUTOS = 360;

    /**
     * Sesiones del empleado en el rango donde faltó tiempo de almuerzo.
     */
    public function detectar(array $filtros): Collection
    {
        $userId = (int) $filtros['user_id'];
        $desde = Carbon::parse($filtros['fecha_desde'] ?? now()->startOfMonth())->toDateString();
        $hasta = Carbon::parse($filtros['fecha_hasta'] ?? now())->toDateString();

        $sessions = WorkSession::with('jornadaLaboral')
            ->where('user_id', $userId)
            ->whereNotNull('hora_entrada')
            ->whereNotNull('hora_salida')
            ->whereBetween('registro_diario', [$desde, $hasta])
            ->orderBy('registro_diario')
            ->get();

        $jornadaActiva = JornadaLaboral::where('status', true)->orderByDesc('updated_at')->first();

        // Horas extra no rechazadas del rango, agrupadas por fecha, para detectar
        // si alguna YA cubre la franja del almuerzo (solo esas cuentan como duplicado;
        // una hora extra por "quedarse tarde" no bloquea la del almuerzo).
        $horasExtraPorFecha = HoraExtra::where('user_id', $userId)
            ->where('status', '!=', 'rechazada')
            ->whereBetween('fecha', [$desde, $hasta])
            ->get(['fecha', 'hora_inicio', 'hora_fin'])
            ->groupBy(fn ($h) => $h->fecha->toDateString());

        return $sessions->map(function (WorkSession $session) use ($jornadaActiva, $horasExtraPorFecha) {
            $jornada = $session->jornadaLaboral ?? $jornadaActiva;
            $almuerzoConfig = (int) ($jornada?->duracion_almuerzo_minutos ?? 0);

            if ($almuerzoConfig <= 0) {
                return null; // esa jornada no contempla almuerzo
            }

            $minutosBrutos = (int) Carbon::parse($session->hora_entrada)
                ->diffInMinutes(Carbon::parse($session->hora_salida));

            // Solo aplica almuerzo si el turno fue lo bastante largo para incluirlo.
            if ($minutosBrutos < self::TURNO_MINIMO_CON_ALMUERZO_MINUTOS) {
                return null;
            }

            $almuerzoTomado = (int) ($session->minutos_almuerzo ?? 0);
            $minutosNoTomados = max(0, $almuerzoConfig - $almuerzoTomado);

            if ($minutosNoTomados <= 0) {
                return null;
            }

            $fecha = $session->registro_diario->toDateString();

            [$franjaIni, $franjaFin] = $this->franjaAlmuerzo($jornada, $almuerzoConfig);
            $yaTieneHoraExtra = $horasExtraPorFecha->get($fecha, collect())
                ->contains(fn ($h) => $this->solapan(
                    $h->hora_inicio, $h->hora_fin, $franjaIni, $franjaFin
                ));

            return [
                'work_session_uuid'   => $session->uuid,
                'fecha'               => $fecha,
                'hora_entrada'        => optional($session->hora_entrada)->format('H:i'),
                'hora_salida'         => optional($session->hora_salida)->format('H:i'),
                'minutos_almuerzo'    => $almuerzoTomado,
                'almuerzo_configurado'=> $almuerzoConfig,
                'minutos_no_tomados'  => $minutosNoTomados,
                'minutos_trabajados'  => (int) ($session->minutos_trabajados ?? 0),
                // 'no_tomado' = no marcó almuerzo; 'parcial' = lo tomó más corto.
                'deteccion'           => $almuerzoTomado <= 0 ? 'no_tomado' : 'parcial',
                // Una hora extra no puede ser menor a 30 min (regla del sistema).
                'generable'           => $minutosNoTomados >= 30,
                'ya_tiene_hora_extra' => $yaTieneHoraExtra,
            ];
        })->filter()->values();
    }

    /**
     * Crea una hora extra pendiente por cada sesión seleccionada.
     *
     * @param  array{user_id:int,sesiones:array<int,array{work_session_uuid:string,minutos:int}>,tipo?:string,motivo?:string}  $data
     */
    public function generarSolicitudes(array $data): Collection
    {
        return DB::transaction(function () use ($data) {
            $userId = (int) $data['user_id'];
            $tipo = $data['tipo'] ?? 'diurna';
            $solicitadoPor = Auth::id();

            $empleado = User::select('id', 'sede_id')->findOrFail($userId);
            $jornadaActiva = JornadaLaboral::where('status', true)->orderByDesc('updated_at')->first();

            $uuids = collect($data['sesiones'])->pluck('work_session_uuid');
            $sessions = WorkSession::with('jornadaLaboral')
                ->where('user_id', $userId)
                ->whereIn('uuid', $uuids)
                ->get()
                ->keyBy('uuid');

            $registros = collect();

            foreach ($data['sesiones'] as $sel) {
                $session = $sessions->get($sel['work_session_uuid']);
                if (! $session) {
                    continue;
                }

                $jornada = $session->jornadaLaboral ?? $jornadaActiva;
                $almuerzoConfig = (int) ($jornada?->duracion_almuerzo_minutos ?? 0);
                $minutosNoTomados = max(0, $almuerzoConfig - (int) ($session->minutos_almuerzo ?? 0));

                // El servidor manda: nunca más de lo que realmente faltó.
                $minutos = min((int) $sel['minutos'], $minutosNoTomados);
                if ($minutos < 30) {
                    continue;
                }

                $fecha = $session->registro_diario->toDateString();

                [$franjaIni, $franjaFin] = $this->franjaAlmuerzo($jornada, $almuerzoConfig);
                $horaInicio = $franjaIni;
                $horaFin = Carbon::parse($fecha.' '.$horaInicio)->addMinutes($minutos)->format('H:i');

                // No duplicar: solo se salta si ya hay una hora extra (no rechazada)
                // que solape la franja del almuerzo. Otras (p. ej. quedarse tarde)
                // no bloquean: la nómina ya topa la suma del día al exceso real.
                $existeEnFranja = HoraExtra::where('user_id', $userId)
                    ->whereDate('fecha', $fecha)
                    ->where('status', '!=', 'rechazada')
                    ->get(['hora_inicio', 'hora_fin'])
                    ->contains(fn ($h) => $this->solapan($h->hora_inicio, $h->hora_fin, $franjaIni, $franjaFin));
                if ($existeEnFranja) {
                    continue;
                }

                $horaExtra = HoraExtra::create([
                    'user_id'        => $userId,
                    'sede_id'        => $empleado->sede_id,
                    'solicitado_por' => $solicitadoPor,
                    'origen'         => 'admin',
                    'fecha'          => $fecha,
                    'hora_inicio'    => $horaInicio,
                    'hora_fin'       => $horaFin,
                    'horas'          => round($minutos / 60, 2),
                    'tipo'           => $tipo,
                    'motivo'         => $data['motivo']
                        ?? "Almuerzo no tomado ({$minutos} min) — generado desde detector",
                    'status'         => 'pendiente',
                ]);

                $registros->push($horaExtra);
            }

            Log::info('Horas extra generadas desde almuerzos no tomados', [
                'user_id'        => $userId,
                'solicitadas'    => count($data['sesiones']),
                'creadas'        => $registros->count(),
                'solicitado_por' => $solicitadoPor,
            ]);

            return $registros;
        });
    }

    /**
     * Franja horaria del almuerzo según la jornada ('HH:MM' inicio y fin).
     * Si la jornada no define la salida a almuerzo, se asume 12:00.
     */
    private function franjaAlmuerzo(?JornadaLaboral $jornada, int $almuerzoConfig): array
    {
        $inicio = $jornada?->hora_salida_almuerzo
            ? Carbon::parse($jornada->hora_salida_almuerzo)
            : Carbon::parse('12:00');

        $fin = $jornada?->hora_ingreso_almuerzo
            ? Carbon::parse($jornada->hora_ingreso_almuerzo)
            : $inicio->copy()->addMinutes(max(1, $almuerzoConfig));

        return [$inicio->format('H:i'), $fin->format('H:i')];
    }

    /**
     * ¿Se solapan dos rangos horarios 'HH:MM(:SS)'?
     */
    private function solapan(?string $ini1, ?string $fin1, string $ini2, string $fin2): bool
    {
        if (! $ini1 || ! $fin1) {
            return false;
        }

        $a1 = $this->minutosDelDia($ini1);
        $a2 = $this->minutosDelDia($fin1);
        $b1 = $this->minutosDelDia($ini2);
        $b2 = $this->minutosDelDia($fin2);

        return $a1 < $b2 && $b1 < $a2;
    }

    private function minutosDelDia(string $hora): int
    {
        [$h, $m] = array_pad(explode(':', $hora), 2, 0);

        return ((int) $h) * 60 + (int) $m;
    }
}
