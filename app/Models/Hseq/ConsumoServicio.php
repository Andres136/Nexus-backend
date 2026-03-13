<?php

namespace App\Models\Hseq;

use App\Models\Crm\Sede;
use Illuminate\Database\Eloquent\Model;

class ConsumoServicio extends Model
{
    protected $fillable = [
        'sede_id',
        'tipo_servicio_id',
        'valor_factura',
        'consumo',
        'fecha_consumo',
        'fecha_pago',
        'estado',
        'consumo_percapita'
    ];

    public function sede()
    {
        return $this->belongsTo(Sede::class);
    }

    public function tipoServicio()
    {
        return $this->belongsTo(TipoServicio::class);
    }
}
