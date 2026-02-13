<?php

namespace App\Models\RegistroDiario;

use App\Models\Departamentos;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class RegistroDiarios extends Model
{
    protected $table = 'registro_diario';

    protected $fillable = [
        'usuario_id',
        'departamento_id',
        'pregunta_id',
        'respuesta',
        'observaciones',
        'fecha',
        'tipo',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function departamento()
    {
        return $this->belongsTo(Departamentos::class, 'departamento_id');
    }

    public function pregunta()
    {
        return $this->belongsTo(Preguntas::class, 'pregunta_id');
    }

    public function novedades()
    {
        return $this->hasMany(Novedades::class, 'registro_diario_id');
    }

    public function verificaciones()
    {
        return $this->hasMany(Verificaciones::class, 'registro_diario_id');
    }
}
