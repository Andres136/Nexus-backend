<?php

namespace App\Models\Crm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Encuesta extends Model
{
    protected $table = 'encuestas';

    protected $fillable = [
        'user_id',
        'titulo',
        'descripcion',
        'estado',
    ];

    public function preguntas()
    {
        return $this->hasMany(EncuestaPregunta::class, 'encuesta_id')->orderBy('orden');
    }

    public function envios()
    {
        return $this->hasMany(EncuestaEnvio::class, 'encuesta_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
