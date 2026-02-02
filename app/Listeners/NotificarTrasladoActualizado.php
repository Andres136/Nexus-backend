<?php

namespace App\Listeners;

use App\Events\Traslados\TrasladoActualizado;
use App\Models\Traslados\Responsabilidad;
use App\Models\User;
use App\Notifications\Traslados\TrasladoActualizadoNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class NotificarTrasladoActualizado
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
   public function handle(TrasladoActualizado $event)
{
    $traslado = $event->traslado;

    // Notificar responsable de bodega
    $responsables = $this->obtenerResponsablesBodega($traslado->bodega_origen_id);

    Notification::send($responsables, new TrasladoActualizadoNotification($traslado));

    // Notificar creador si no es el editor
    if ($traslado->usuario_creador_id !== $event->usuarioEditorId) {
        $traslado->creador->notify(
            new TrasladoActualizadoNotification($traslado)
        );
    }
}

private function obtenerResponsablesBodega(int $bodegaId)
{
    $responsabilidadId = Responsabilidad::where('nombre', 'Bodega')->value('id');

 return User::whereHas('responsabilidades', function ($q) use ($responsabilidadId, $bodegaId) {
    $q->where('responsabilidad_id', $responsabilidadId)
      ->where('responsabilidades_user.bodega_id', $bodegaId)
      ->where('responsabilidades_user.activo', true);
})->get();

}

}
