<?php

namespace App\Models\Crm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ChatbotCorreoCliente extends Model
{
    protected $table = 'chatbot_correos_clientes';
    protected $fillable = ['cliente_id', 'user_id', 'destinatario', 'asunto', 'mensaje', 'estado', 'error', 'enviado_at'];
    protected $casts = ['enviado_at' => 'datetime'];

    public function cliente() { return $this->belongsTo(Cliente::class); }
    public function usuario() { return $this->belongsTo(User::class, 'user_id'); }
}
