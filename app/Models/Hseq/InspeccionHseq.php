<?php

namespace App\Models\Hseq;

use App\Models\Crm\Sede;
use App\Models\User;
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


    public function sede()
    {
        return $this->belongsTo(Sede::class);
    }

    public function tipoInspeccion()
    {
        return $this->belongsTo(TipoInspeccion::class);
    }

    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }
}
