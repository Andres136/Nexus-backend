<?php

namespace App\Listeners\Traslados;

use App\Events\Traslados\TrasladoCreado;
use App\Models\Traslados\Responsabilidad;
use App\Models\User;
use App\Notifications\Traslados\TrasladoPendienteBodegaNotificacion;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification as FacadesNotification;

class NotificarResponsable
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
 public function handle(TrasladoCreado $event): void
{
    $traslado = $event->traslado;

    $responsables = $this->obtenerResponsablesBodega($traslado->bodega_origen_id);

    FacadesNotification::send(
        $responsables,
        new TrasladoPendienteBodegaNotificacion($traslado)
    );
}
private function obtenerResponsablesBodega(int $bodegaId)
{
    $responsabilidadId = Responsabilidad::where('codigo', 'bodega')->value('id');

 return User::whereHas('responsabilidades', function ($q) use ($responsabilidadId, $bodegaId) {
    $q->where('responsabilidad_id', $responsabilidadId)
      ->where('responsabilidades_user.bodega_id', $bodegaId)
      ->where('responsabilidades_user.activo', true);
})->get();

}

}