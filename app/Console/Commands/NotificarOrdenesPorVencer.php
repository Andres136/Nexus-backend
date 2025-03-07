<?php

namespace App\Console\Commands;

use App\Models\Crm\Orden_Compra;
use Illuminate\Console\Command;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;
use App\Notifications\OrdenCompraNotificacion;
use App\Notifications\OrdenesPorVencerNotificacion;

class NotificarOrdenesPorVencer extends Command
{
    protected $signature = 'notificar:ordenesporvencer';
    protected $description = 'Enviar notificaciones para las órdenes de compra próximas a vencer';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        // 🔹 Obtener órdenes que están en estado PENDIENTE (estado_id = 1)
        $ordenes = Orden_Compra::with('user')
            ->where('estado_id', '=', 1) // Solo órdenes pendientes
            ->get();

        $hoy = Carbon::now();

        foreach ($ordenes as $orden) {
            $fechaEntrega = Carbon::parse($orden->fecha_entrega);
            $dosDiasAntes = $fechaEntrega->copy()->subDays(2);

            if ($hoy->greaterThanOrEqualTo($dosDiasAntes)) {
                // 🔹 Buscar usuarios con role_id específico
                $usuariosNotificar = User::whereIn('role_id', [1, 5, 3])->get(); // Ajusta los IDs de roles

                // 🔹 Enviar notificación a los usuarios
                Notification::send($usuariosNotificar, new OrdenesPorVencerNotificacion($orden));

                // 🔹 Enviar notificación al usuario asociado a la orden
                if ($orden->user) {
                    $orden->user->notify(new OrdenesPorVencerNotificacion($orden,));
                }

                $this->info("Notificación enviada para la orden {$orden->id}");
            }
        }
    }
}
