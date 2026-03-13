<?php

namespace App\Models\Hseq;

use Illuminate\Database\Eloquent\Model;

class InspeccionHseq extends Model
{
    protected $table = "inspecciones_hseq";
    protected $fillable = [
        'sede_id',
        'tipo_inspeccion_id',
        'fecha',
        'responsable_id',
        'estado',
        'observaciones',

    ];
}
