<?php

namespace App\Models\Crm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ChatbotMensaje extends Model
{
    protected $table = 'chatbot_mensajes';
    protected $fillable = [
        'chatbot_conversacion_id',
        'remitente',
        'user_id',
        'contenido',
        'metadata',
    ];
    protected $casts = [
        'metadata' => 'array',
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
