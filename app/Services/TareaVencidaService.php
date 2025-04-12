<?php
namespace App\Services;

use App\Models\Tareas;
use App\Models\User;
use App\Notifications\Crm\TareaVencidaNotificacion as CrmTareaVencidaNotificacion;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Notifications\TareaVencidaNotificacion;

class TareaVencidaService
{
    public function notificarTareasVencidas()
    {
        $hoy = Carbon::now();
    
        // Buscar tareas PENDIENTES (estado_id = 1) con fecha de vencimiento definida
        $tareas = Tareas::with('usuario')
            ->where('estado_id', 1)
            ->whereNotNull('fecha_fin')
            ->get();
    
        foreach ($tareas as $tarea) {
            $fechaFin = Carbon::parse($tarea->fecha_fin);
            $dosDiasAntes = $fechaFin->copy()->subDays(2);
    
            if ($hoy->greaterThanOrEqualTo($dosDiasAntes)) {
                try {
                    // 🔔 Solo notificar al usuario asignado
                    $usuariosNotificar = User::where('id', $tarea->user_id)->get();
    
                    // Enviar notificación en segundo plano (queue)
                    Notification::send($usuariosNotificar, new CrmTareaVencidaNotificacion($tarea));
    
                    // Registrar en logs
                    Log::info("Notificación enviada para tarea {$tarea->id} con fecha límite {$tarea->fecha_fin}");
    
                } catch (\Exception $e) {
                    Log::error("Error al enviar notificación para tarea {$tarea->id}: " . $e->getMessage());
                }
            }
        }
    }
    
}
