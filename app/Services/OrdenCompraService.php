<?php
namespace App\Services;

use App\Models\Crm\Orden_Compra;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;
use App\Notifications\OrdenCompraNotificacion;
use App\Notifications\OrdenesPorVencerNotificacion;

class OrdenCompraService
{
    /**
     * Verifica las órdenes próximas a vencer y envía notificaciones.
     */
    public function notificarOrdenesPorVencer()
    {
        $hoy = Carbon::now();

        // 🔹 Buscar órdenes que están en estado PENDIENTE (estado_id = 1)
        $ordenes = Orden_Compra::with('user')
            ->where('estado_id', '=', 1) // Solo órdenes pendientes
            ->get();

        foreach ($ordenes as $orden) {
            $fechaEntrega = Carbon::parse($orden->fecha_entrega);
            $dosDiasAntes = $fechaEntrega->copy()->subDays(2);

            if ($hoy->greaterThanOrEqualTo($dosDiasAntes)) {
                // 🔹 Obtener los usuarios con role_id específico
                $usuariosNotificar = User::whereIn('role_id', [1, 5, 3])->get(); // Ajusta los IDs de roles según la base de datos

                // 🔹 Enviar notificación a los usuarios correspondientes
                Notification::send($usuariosNotificar, new OrdenesPorVencerNotificacion($orden));

                // 🔹 Enviar notificación al usuario que creó la orden
                if ($orden->user) {
                    $orden->user->notify(new OrdenesPorVencerNotificacion($orden));
                }
            }
        }
    }
}
