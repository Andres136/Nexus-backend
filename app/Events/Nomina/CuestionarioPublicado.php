<?php

namespace App\Events\Nomina;

use App\Models\Nomina\Cuestionario;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CuestionarioPublicado implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Cuestionario $cuestionario)
    {}

    public function broadcastOn(): array
    {
        return [
            new PresenceChannel("convocatoria-cuestionario.{$this->cuestionario->id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'cuestionario.publicado';
    }

    public function broadcastWith(): array
    {
        return [
            'cuestionario_id' => $this->cuestionario->id,
            'publicado_en' => $this->cuestionario->publicado_en?->toIso8601String(),
            'duracion_segundos' => $this->cuestionario->duracion_segundos,
            'deadline' => $this->cuestionario->deadline,
        ];
    }
}
