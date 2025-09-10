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

    $rolesPermitidos= [4,6]; // Agrega aquí los role_id permitidos
    $operacionesId = Departamentos::where('nombre', 'Operaciones')->value('id');

    $ordenes = Orden_Compra::with(['user', 'cliente', 'sede'])
        ->where('estado_id', 1)
        ->whereNotNull('sede_id')
        ->get();

foreach ($ordenes as $orden) {
    $fechaEntrega = Carbon::parse($orden->fecha_entrega);
    $dosDiasAntes = $fechaEntrega->copy()->subDays(2);

    if (Carbon::now()->greaterThanOrEqualTo($dosDiasAntes)) {
            // Notificar a usuarios del departamento de Operaciones en la misma sede
        if ($orden->departamento_id == $operacionesId) {
            $usuariosSede = User::where('sede_id', $orden->sede_id)
                ->where('departamento_id', $operacionesId)
                ->whereIn('role_id', $rolesPermitidos) // Filtrar por roles permitidos
                ->whereNotNull('email')
                ->get();

            if ($usuariosSede->count() > 0) {
                Notification::send($usuariosSede, new OrdenesPorVencerNotificacion($orden));
            }
        }

        // Siempre notifica al creador de la orden si existe
        if ($orden->user) {
            $orden->user->notify(new OrdenesPorVencerNotificacion($orden));
        }
    }
}
Cache::put('notificacion_ordenes_' . $hoy, true, now()->addDay());
  

}
}