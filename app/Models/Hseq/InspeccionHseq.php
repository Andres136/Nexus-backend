<?php

namespace App\Models\Hseq;

use App\Models\Crm\Sede;
use App\Models\Crm\bodega;
use App\Models\Crm\empresa;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class InspeccionHseq extends Model
{
    protected $table = "inspecciones_hseq";
    protected $fillable = [
        'sede_id',
        'empresa_id',
        'bodega_id',
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

    public function empresa()
    {
        return $this->belongsTo(empresa::class);
    }

    public function bodega()
    {
        return $this->belongsTo(bodega::class);
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
