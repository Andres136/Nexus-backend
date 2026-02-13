<?php

namespace App\Models\RegistroDiario;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;

class Verificaciones extends Model
{
    protected $table = 'verificacion_diaria';

    protected $fillable = [
        'registro_diario_id',
        'pregunta_id',
        'usuario_id',
        'observaciones',
        'estado',
        'fecha',
    ];

    public function registroDiario()
    {
        return $this->belongsTo(RegistroDiarios::class, 'registro_diario_id');
    }

    public function pregunta()
    {
        return $this->belongsTo(Preguntas::class, 'pregunta_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
