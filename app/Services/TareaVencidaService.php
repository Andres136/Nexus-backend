<?php
namespace App\Services;

use App\Models\Tareas;
use App\Models\User;
use App\Notifications\Crm\TareaVencidaNotificacion as CrmTareaVencidaNotificacion;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Cache;

class TareaVencidaService
{
    public function notificarTareasVencidas()
    {
        $hoyStr = Carbon::now()->toDateString(); // ej: '2025-05-05'

        // ✅ Verificar si ya se ejecutó hoy
        if (Cache::has('notificacion_tareas_' . $hoyStr)) {
            return; // Ya se ejecutó hoy
        }

      $hoy = Carbon::now();
    
        // Buscar tareas PENDIENTES (estado_id = 1) con fecha de vencimiento definida
        $tareas = Tareas::with('usuario')
            ->where('estado_id', [1,5])
            ->whereNotNull('fecha_fin')
            ->get();
    
        foreach ($tareas as $tarea) {
            $fechaFin = Carbon::parse($tarea->fecha_fin);
            $dosDiasAntes = $fechaFin->copy()->subDays(2);
    
            if ($hoy->greaterThanOrEqualTo($dosDiasAntes)) {
                try {
                    $usuariosNotificar = User::where('id', $tarea->user_id)->get();
                    Notification::send($usuariosNotificar, new CrmTareaVencidaNotificacion($tarea));
                    Log::info("Notificación enviada para tarea {$tarea->id} con fecha límite {$tarea->fecha_fin}");
                } catch (\Exception $e) {
                    Log::error("Error al enviar notificación para tarea {$tarea->id}: " . $e->getMessage());
                }
            }
        }

        // ✅ Marcar como ejecutado por 24 horas
        Cache::put('notificacion_tareas_' . $hoyStr, true, now()->addDay());
    }
}

