<?php

namespace App\Models\RegistroDiario;

use App\Models\Departamentos;
use Illuminate\Database\Eloquent\Model;

class Preguntas extends Model
{
    protected $table = 'preguntas';

    protected $fillable = [
        'pregunta',
        'departamento_id',
    ];

    public function departamento()
    {
        return $this->belongsTo(Departamentos::class, 'departamento_id');
    }
}
