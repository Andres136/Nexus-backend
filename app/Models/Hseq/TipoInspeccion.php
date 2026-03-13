<?php

namespace App\Models\Hseq;

use Illuminate\Database\Eloquent\Model;

class TipoInspeccion extends Model
{
    protected $table = 'tipo_inspecciones';

    protected $fillable = [
        'nombre',
   
    ];

    public function preguntas()
    {
        return $this->hasMany(PreguntaInspeccion::class, 'tipo_inspeccion_id');
    }
}
