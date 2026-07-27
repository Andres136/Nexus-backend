<?php

namespace App\Listeners\Traslados;

use App\Events\Traslados\TrasladoAprobadorPorBodega;
use App\Events\Traslados\TrasladoCreado;
use App\Models\Traslados\Responsabilidad;
use App\Models\User;
use App\Notifications\Traslados\NotificacionTrasladoCreado;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class NotificarResponsableInventario
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
 public function handle(TrasladoAprobadorPorBodega $event): void
{
    $traslado = $event->traslado;

    $responsables = $this->obtenerResponsablesInventario();

    Notification::send(
        $responsables,
        new NotificacionTrasladoCreado($traslado)
    );
}

private function obtenerResponsablesInventario(): \Illuminate\Support\Collection
{
    $idInventario = Responsabilidad::where('codigo', 'inventario')->value('id');
    return User::whereHas('responsabilidades', function ($q) use ($idInventario) {
        $q->where('responsabilidades.id', $idInventario)
          ->where('responsabilidades_user.activo', true);
    })->get();

}
}
