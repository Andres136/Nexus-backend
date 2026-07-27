<?php

namespace App\Console\Commands;

use App\Models\Nomina\WorkSession;
use App\Models\Productividad\JornadaOperativa;
use App\Notifications\RecordatorioMiDiaNotificacion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class RecordarMiDiaSinActividad extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:recordar-mi-dia-sin-actividad';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notifica a quien lleva fichado un rato en el kiosko sin registrar ninguna actividad en Mi Día';

    private const UMBRAL_MINUTOS = 30;
    private const COOLDOWN_MINUTOS = 60;

    public function handle(): void
    {
        $ahora = now(config('app.timezone'));
        $hoy = $ahora->toDateString();

        $sesiones = WorkSession::with('empleado')
            ->whereDate('registro_diario', $hoy)
            ->whereNotNull('hora_entrada')
            ->whereNull('hora_salida')
            ->where('hora_entrada', '<=', $ahora->copy()->subMinutes(self::UMBRAL_MINUTOS))
            ->get();

        $enviadas = 0;

        foreach ($sesiones as $sesion) {
            if (! $sesion->empleado) {
                continue;
            }

            // Ya registró algo hoy (jornada solo se crea al iniciar una
            // actividad o marcarse disponible) — nada que recordarle.
            $yaRegistroAlgo = JornadaOperativa::where('user_id', $sesion->user_id)
                ->whereDate('fecha', $hoy)
                ->exists();

            if ($yaRegistroAlgo) {
                continue;
            }

            $cacheKey = "recordatorio_mi_dia_{$sesion->user_id}_{$hoy}";
            if (Cache::has($cacheKey)) {
                continue;
            }

            $sesion->empleado->notify(new RecordatorioMiDiaNotificacion($sesion));
            Cache::put($cacheKey, true, now()->addMinutes(self::COOLDOWN_MINUTOS));
            $enviadas++;
        }

        $this->info("Recordatorios de Mi Día enviados: {$enviadas}");
    }
}
