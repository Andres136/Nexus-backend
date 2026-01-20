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

    $responsables = User::whereHas('responsabilidades', function ($q) use ($traslado) {
        $q->where('responsabilidades.id', Responsabilidad::BODEGA)
          ->where('responsabilidades_user.bodega_id', $traslado->bodega_origen_id)
          ->where('responsabilidades_user.activo', true);
    })->get();

    FacadesNotification::send(
        $responsables,
        new TrasladoPendienteBodegaNotificacion($traslado)
    );
}
}
