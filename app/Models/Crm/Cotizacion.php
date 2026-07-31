<?php

namespace App\Models\Crm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Cotizacion extends Model
{
    protected $table = 'cotizaciones';
    protected $fillable = [
        'cliente_id',
        'empresa_id',
        'empresa',
        'observaciones',
        'user_id',
        'valor_total',
        'chatbot_conversacion_id',
        'responsable_id',
        'estado_aprobacion',
        'aprobado_por',
        'aprobado_at',
        'motivo_rechazo',
        'enviada_cliente_at',
        'envio_cliente_error',
    ];

    protected $casts = ['aprobado_at' => 'datetime', 'enviada_cliente_at' => 'datetime'];


    
    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function empresaReal()
    {
        return $this->belongsTo(empresa::class, 'empresa_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function detalles()
    {
        return $this->hasMany(CotizacionDetalles::class);
    }

    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function aprobador()
    {
        return $this->belongsTo(User::class, 'aprobado_por');
    }

    public function conversacionChatbot()
    {
        return $this->belongsTo(ChatbotConversacion::class, 'chatbot_conversacion_id');
    }


}
