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
     * Notifica a los usuarios sobre las órdenes de compra pendientes.
     *
     * @return void
     */


     public function notificarOrdenesPorVencer()
     {
         $hoy = Carbon::now()->toDateString();
     
         // Previene duplicación diaria
         if (Cache::has('notificacion_ordenes_' . $hoy)) {
             return;
         }
     
         $ordenes = Orden_Compra::with(['user', 'cliente', 'sede'])
             ->where('estado_id', 1) // Solo pendientes
             ->whereNotNull('sede_id')
             ->get();
     
         // Notificar por sede (usuarios internos)
         foreach ($ordenes as $orden) {
             $fechaEntrega = Carbon::parse($orden->fecha_entrega);
             $dosDiasAntes = $fechaEntrega->copy()->subDays(2);
     
             if (Carbon::now()->greaterThanOrEqualTo($dosDiasAntes)) {
                 // Usuarios de la misma sede con rol 4 o 5
                 $usuariosSede = User::where('sede_id', $orden->sede_id)
                     ->whereIn('role_id', [5])
                     ->get();
     
                 Notification::send($usuariosSede, new OrdenesPorVencerNotificacion($orden));
     
                 // También notifica al creador de la orden si existe
                 if ($orden->user) {
                     $orden->user->notify(new OrdenesPorVencerNotificacion($orden));
                 }
             }
         }
     
         // Notificar a Coordinador de Operaciones con TODAS las órdenes a vencer
         $coordinadores = User::where('role_id', [4,6])->get(); // puedes afinar si es solo uno
     
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
