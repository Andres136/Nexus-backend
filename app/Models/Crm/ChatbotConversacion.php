<?php

namespace App\Models\Crm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ChatbotConversacion extends Model
{
    protected $table = 'chatbot_conversaciones';
    protected $fillable = [
        'token',
        'nombre_lead',
        'email_lead',
        'empresa_lead',
        'telefono_lead',
        'nit_lead',
        'direccion_lead',
        'cliente_id',
        'estado',
        'user_id',
        'asignado_por',
        'asignado_at',
        'origen_url',
        'dominio',
        'ip',
        'ultima_actividad_at',
        'cerrada_at',
    ];
    protected $casts = [
        'asignado_at' => 'datetime',
        'ultima_actividad_at' => 'datetime',
        'cerrada_at' => 'datetime',
    ];

    public function mensajes()
    {
        return $this->hasMany(ChatbotMensaje::class, 'chatbot_conversacion_id')->orderBy('id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function asignadoPor()
    {
        return $this->belongsTo(User::class, 'asignado_por');
    }

    public function citas()
    {
        return $this->hasMany(ChatbotCita::class, 'chatbot_conversacion_id');
    }

    public function cotizaciones()
    {
        return $this->hasMany(Cotizacion::class, 'chatbot_conversacion_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }
}
