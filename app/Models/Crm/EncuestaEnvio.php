<?php

namespace App\Models\Crm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class EncuestaEnvio extends Model
{
    protected $table = 'encuesta_envios';

    protected $fillable = [
        'encuesta_id',
        'cliente_id',
        'user_id',
        'token',
        'estado',
        'sent_at',
        'responded_at',
    ];

    protected $casts = [
        'sent_at'      => 'datetime',
        'responded_at' => 'datetime',
    ];

    public function encuesta()
    {
        return $this->belongsTo(Encuesta::class, 'encuesta_id')->with('preguntas');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function respuestas()
    {
        return $this->hasMany(EncuestaRespuesta::class, 'envio_id');
    }
}
