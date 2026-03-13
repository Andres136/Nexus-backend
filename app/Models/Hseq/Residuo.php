<?php

namespace App\Models\Hseq;

use App\Models\Crm\Sede;
use Illuminate\Database\Eloquent\Model;

class Residuo extends Model
{
    protected $table = 'residuos';

    protected $fillable = [
        'tipo_residuo_id',
        'sede_id',
        'cantidad',
        'fecha',
        'unidad_medida',
        'observaciones'
    ];

    public function tipoResiduo()
    {
        return $this->belongsTo(TipoResiduo::class, 'tipo_residuo_id');
    }

    public function sede()
    {
        return $this->belongsTo(Sede::class, 'sede_id');
    }
}
