<?php
namespace App\Services;

use App\Models\Crm\Orden_Compra;
use App\Models\Departamentos;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;
use App\Notifications\OrdenCompraNotificacion;
use App\Notifications\OrdenesPorVencerNotificacion;
use Illuminate\Support\Facades\Cache;

class OrdenCompraService
{
    /**
     * Notifica a los usuarios sobre las órdenes de compra pendientes.
     *
     * @return void
     */

public function notificarOrdenesPorVencer()
{
    $hoy = Carbon::now()->toDateString();

    if (Cache::has('notificacion_ordenes_' . $hoy)) {
        return;
    }

    $operacionesId = Departamentos::where('nombre', 'Operaciones')->value('id');

    $ordenes = Orden_Compra::with(['user', 'cliente', 'sede'])
        ->where('estado_id', 1)
        ->whereNotNull('sede_id')
        ->get();

    foreach ($ordenes as $orden) {
        $fechaEntrega = Carbon::parse($orden->fecha_entrega);
        $dosDiasAntes = $fechaEntrega->copy()->subDays(2);

        if (Carbon::now()->greaterThanOrEqualTo($dosDiasAntes)) {

            // ⬇️ Solo notificar si el departamento es Operaciones
            if ($orden->departamento_id == $operacionesId) {
                $usuariosSede = User::where('sede_id', $orden->sede_id)
                    ->whereIn('role_id', [5])
                    ->get();

                Notification::send($usuariosSede, new OrdenesPorVencerNotificacion($orden));
            }

            // Siempre notifica al creador de la orden si existe
            if ($orden->user) {
                $orden->user->notify(new OrdenesPorVencerNotificacion($orden));
            }
        }
    }

    // Notificar coordinadores de operaciones (siempre)
    $coordinadores = User::whereIn('role_id', [4, 6])->get();

    foreach ($ordenes as $orden) {
        $fechaEntrega = Carbon::parse($orden->fecha_entrega);
        $dosDiasAntes = $fechaEntrega->copy()->subDays(2);

        if (Carbon::now()->greaterThanOrEqualTo($dosDiasAntes)) {
            Notification::send($coordinadores, new OrdenesPorVencerNotificacion($orden));
        }
    }

    Cache::put('notificacion_ordenes_' . $hoy, true, now()->addDay());
}


}
