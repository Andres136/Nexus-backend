<?php

namespace App\Events\Traslados;

use App\Models\Traslados\Traslado_Bodega;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TrasladoActualizado
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
     public Traslado_Bodega $traslado;
    public int $usuarioEditorId;

    public function __construct(Traslado_Bodega $traslado, int $usuarioEditorId)
    {
        $this->traslado = $traslado;
        $this->usuarioEditorId = $usuarioEditorId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
