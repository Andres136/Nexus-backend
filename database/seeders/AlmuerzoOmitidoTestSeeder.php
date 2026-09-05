<?php

namespace Database\Seeders;

use App\EstadoEnum;
use App\Models\Nomina\Contratacion;
use App\Models\Nomina\JornadaLaboral;
use App\Models\Nomina\KioskoDevice;
use App\Models\Nomina\WorkSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Datos de prueba para el detector de "Almuerzos no tomados".
 * Crea work_sessions en fechas pasadas para un empleado activo con contrato,
 * cubriendo: almuerzo no tomado, almuerzo parcial (>=30 y <30) y almuerzo completo.
 *
 *   php artisan db:seed --class=AlmuerzoOmitidoTestSeeder
 */
class AlmuerzoOmitidoTestSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('estado_id', EstadoEnum::ACTIVO->value)
            ->whereHas('contratacionActivaNomina')
            ->orderBy('id')
            ->first();

        if (! $user) {
            $this->command->warn('No hay empleados activos con contrato. Se omite el seeder.');

            return;
        }

        $jornada = JornadaLaboral::where('status', true)->orderByDesc('updated_at')->first();
        $jornadaId = $jornada?->id;
        $almuerzoConfig = (int) ($jornada?->duracion_almuerzo_minutos ?? 60);
        $kioskoId = KioskoDevice::query()->value('id');

        // La nómina liquida desde la fecha de inicio del contrato más reciente, así
        // que las sesiones deben caer después de esa fecha para que se reconozcan.
        $contrato = Contratacion::where('users_id', $user->id)
            ->where('status', 1)
            ->latest('inicio_contratacion')
            ->first();

        $base = Carbon::parse($contrato?->inicio_contratacion ?? now()->subMonth())
            ->max(now()->subMonths(2)->startOfMonth())
            ->next(Carbon::MONDAY);

        // desplazamiento en días hábiles desde $base => [minutos de almuerzo tomados]
        // entrada 07:00 / salida 17:30 salvo que se indique.
        $casos = [
            ['dia' => 0, 'salida' => '17:30', 'almuerzo' => 0],   // no tomado  -> 60 no tomados
            ['dia' => 1, 'salida' => '17:00', 'almuerzo' => 30],  // parcial    -> 30 (generable)
            ['dia' => 2, 'salida' => '16:00', 'almuerzo' => 60],  // completo   -> NO aparece
            ['dia' => 3, 'salida' => '17:00', 'almuerzo' => 45],  // parcial    -> 15 (no generable)
            ['dia' => 4, 'salida' => '18:00', 'almuerzo' => 0, 'entrada' => '08:00'], // no tomado -> 60
            ['dia' => 7, 'salida' => '17:10', 'almuerzo' => 20, 'entrada' => '07:15'], // parcial -> 40
        ];

        $creadas = 0;
        foreach ($casos as $caso) {
            $fecha = $this->sumarDiasHabiles($base, $caso['dia']);
            $entrada = Carbon::parse($fecha->toDateString().' '.($caso['entrada'] ?? '07:00').':00');
            $salida = Carbon::parse($fecha->toDateString().' '.$caso['salida'].':00');
            $bruto = (int) $entrada->diffInMinutes($salida);
            $almuerzoMin = $caso['almuerzo'];

            $salidaAlmuerzo = $almuerzoMin > 0 ? Carbon::parse($fecha->toDateString().' 12:00:00') : null;
            $ingresoAlmuerzo = $salidaAlmuerzo?->copy()->addMinutes($almuerzoMin);

            WorkSession::updateOrCreate(
                ['user_id' => $user->id, 'registro_diario' => $fecha->toDateString()],
                [
                    'kiosko_id'             => $kioskoId,
                    'hora_entrada'          => $entrada,
                    'hora_salida'           => $salida,
                    'hora_salida_almuerzo'  => $salidaAlmuerzo,
                    'hora_ingreso_almuerzo' => $ingresoAlmuerzo,
                    'minutos_almuerzo'      => $almuerzoMin,
                    'minutos_trabajados'    => max(0, $bruto - $almuerzoMin),
                    'minutos_pausa'         => 0,
                    'minutos_tardanza'      => 0,
                    'sabado_minutos'        => 0,
                    'festivo_minutos'       => 0,
                    'horario_laboral_id'    => $jornadaId,
                ]
            );
            $creadas++;
        }

        $this->command->info(
            "Seeder OK — empleado #{$user->id} ({$user->nombre_completo}), ".
            "{$creadas} work_sessions desde {$base->toDateString()} (config almuerzo: {$almuerzoConfig} min)."
        );
    }

    private function sumarDiasHabiles(Carbon $base, int $dias): Carbon
    {
        $fecha = $base->copy();
        for ($i = 0; $i < $dias; $i++) {
            $fecha->addDay();
            while ($fecha->isWeekend()) {
                $fecha->addDay();
            }
        }

        return $fecha;
    }
}
