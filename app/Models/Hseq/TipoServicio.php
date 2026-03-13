<?php

namespace App\Models\Hseq;

use Illuminate\Database\Eloquent\Model;

class TipoServicio extends Model
{
    protected $fillable = [
        'nombre',
        'descripcion',
        'unidad_medida'
    ];
}
