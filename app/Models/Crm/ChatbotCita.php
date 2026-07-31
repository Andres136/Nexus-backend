<?php

namespace App\Models\Crm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ChatbotCita extends Model
{
    protected $table = 'chatbot_citas';
    protected $fillable = [
        'chatbot_conversacion_id',
        'user_id',
        'nombre_lead',
        'email_lead',
        'empresa_lead',
        'telefono_lead',
        'fecha_inicio',
        'fecha_fin',
        'titulo',
        'notas',
        'estado',
        'creado_por',
    ];
    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
    ];

    public function conversacion()
    {
        return $this->belongsTo(ChatbotConversacion::class, 'chatbot_conversacion_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
