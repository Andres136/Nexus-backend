<?php

namespace App\Services\Crm;

use App\Models\Crm\ChatbotCita;
use App\Models\Crm\ChatbotConversacion;
use App\Models\User;
use App\Notifications\Crm\ChatbotCitaAgendadaNotificacion;
use App\RolEnum;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class ChatbotCitaService
{
    public function crearDesdeBot(ChatbotConversacion $conversacion, Carbon $fechaInicio, ?string $titulo, ?string $notas): ChatbotCita
    {
        $cita = ChatbotCita::create([
            'chatbot_conversacion_id' => $conversacion->id,
            'user_id' => $conversacion->user_id,
            'nombre_lead' => $conversacion->nombre_lead ?? 'Visitante web',
            'email_lead' => $conversacion->email_lead,
            'empresa_lead' => $conversacion->empresa_lead,
            'telefono_lead' => $conversacion->telefono_lead,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaInicio->copy()->addMinutes(30),
            'titulo' => $titulo ?: 'Cita agendada por el asistente virtual',
            'notas' => $notas,
            'estado' => 'pendiente',
            'creado_por' => 'bot',
        ]);

        $this->notificarCitaAgendada($cita);

        return $cita;
    }

    public function listar(User $user, array $filtros): Collection
    {
        $esPrivilegiado = $user->role_id == RolEnum::ADMINISTRADOR->value || $user->esResponsableDeSuDepartamento();

        return ChatbotCita::query()
            ->with('usuario:id,name')
            ->when(!$esPrivilegiado, fn ($q) => $q->where('user_id', $user->id))
            ->when($filtros['desde'] ?? null, fn ($q, $d) => $q->where('fecha_inicio', '>=', $d))
            ->when($filtros['hasta'] ?? null, fn ($q, $h) => $q->where('fecha_inicio', '<=', $h))
            ->orderBy('fecha_inicio')
            ->get();
    }

    public function crear(array $datos): ChatbotCita
    {
        $fechaInicio = Carbon::parse($datos['fecha_inicio']);

        $cita = ChatbotCita::create([
            'user_id' => $datos['user_id'] ?? null,
            'nombre_lead' => $datos['nombre_lead'],
            'email_lead' => $datos['email_lead'] ?? null,
            'empresa_lead' => $datos['empresa_lead'] ?? null,
            'telefono_lead' => $datos['telefono_lead'] ?? null,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => isset($datos['fecha_fin']) ? Carbon::parse($datos['fecha_fin']) : $fechaInicio->copy()->addMinutes(30),
            'titulo' => $datos['titulo'] ?? null,
            'notas' => $datos['notas'] ?? null,
            'estado' => $datos['estado'] ?? 'pendiente',
            'creado_por' => 'agente',
        ]);

        return $cita;
    }

    public function actualizar(ChatbotCita $cita, array $datos): ChatbotCita
    {
        $cita->update($datos);

        return $cita->fresh();
    }

    public function eliminar(ChatbotCita $cita): void
    {
        $cita->delete();
    }

    public function puedeGestionar(ChatbotCita $cita, User $user): bool
    {
        $esPrivilegiado = $user->role_id == RolEnum::ADMINISTRADOR->value || $user->esResponsableDeSuDepartamento();

        return $esPrivilegiado || $cita->user_id === $user->id;
    }

    private function notificarCitaAgendada(ChatbotCita $cita): void
    {
        $destinatarios = collect();

        if ($cita->user_id) {
            $ejecutivo = User::find($cita->user_id);
            if ($ejecutivo) {
                $destinatarios->push($ejecutivo);
            }
        } else {
            $destinatarios = User::where('role_id', RolEnum::ADMINISTRADOR->value)->get();
        }

        if ($destinatarios->isNotEmpty()) {
            Notification::send($destinatarios, new ChatbotCitaAgendadaNotificacion($cita));
        }
    }
}
