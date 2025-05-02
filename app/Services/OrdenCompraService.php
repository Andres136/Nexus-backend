<?php
namespace App\Services;

use App\Models\Crm\Orden_Compra;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;
use App\Notifications\OrdenCompraNotificacion;
use App\Notifications\OrdenesPorVencerNotificacion;
use Illuminate\Support\Facades\Cache;

class OrdenCompraService
{
    /**
     * Verifica las órdenes próximas a vencer y envía notificaciones.
     */
    // public function notificarOrdenesPorVencer()
    // {
    //     $hoy = Carbon::now();

    //     // 🔹 Buscar órdenes que están en estado PENDIENTE (estado_id = 1)
    //     $ordenes = Orden_Compra::with('user')
    //         ->where('estado_id', '=', 1) // Solo órdenes pendientes
    //         ->get();

    //     foreach ($ordenes as $orden) {
    //         $fechaEntrega = Carbon::parse($orden->fecha_entrega);
    //         $dosDiasAntes = $fechaEntrega->copy()->subDays(2);

    //         if ($hoy->greaterThanOrEqualTo($dosDiasAntes)) {
    //             // 🔹 Obtener los usuarios con role_id específico
    //             $usuariosNotificar = User::whereIn('role_id', [ 5,4,6,7])->get(); // Ajusta los IDs de roles según la base de datos

    //             // 🔹 Enviar notificación a los usuarios correspondientes
    //             Notification::send($usuariosNotificar, new OrdenesPorVencerNotificacion($orden));

    //             // 🔹 Enviar notificación al usuario que creó la orden
    //             if ($orden->user) {
    //                 $orden->user->notify(new OrdenesPorVencerNotificacion($orden));
    //             }
    //         }
    //     }
    // }


    public function notificarOrdenesPorVencer()
{
    $hoy = Carbon::now()->toDateString(); // ej: '2025-05-02'

    // Si ya se ejecutó hoy, no hace nada
    if (Cache::has('notificacion_ordenes_' . $hoy)) {
        return; // ya fue ejecutado hoy
    }

    $ordenes = Orden_Compra::with('user')
        ->where('estado_id', '=', 1) // Solo órdenes pendientes
        ->get();

    foreach ($ordenes as $orden) {
        $fechaEntrega = Carbon::parse($orden->fecha_entrega);
        $dosDiasAntes = $fechaEntrega->copy()->subDays(2);

        if (Carbon::now()->greaterThanOrEqualTo($dosDiasAntes)) {
            $usuariosNotificar = User::whereIn('role_id', [5, 4, 6, 7])->get();
            Notification::send($usuariosNotificar, new OrdenesPorVencerNotificacion($orden));

            if ($orden->user) {
                $orden->user->notify(new OrdenesPorVencerNotificacion($orden));
            }
        }
    }

    // Guarda en caché por 24 horas
    Cache::put('notificacion_ordenes_' . $hoy, true, now()->addDay());
}

}
