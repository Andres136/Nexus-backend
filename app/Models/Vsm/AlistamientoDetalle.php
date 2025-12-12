<?php

namespace App\Models\Vsm;

use Illuminate\Database\Eloquent\Model;

class AlistamientoDetalle extends Model
{
    protected $table = 'alistamiento_detalles';
    protected $fillable = [
        'alistamiento_id',
        'product_id',
       'cantidad_programada',
        'cantidad_alistada',
        'cantidad_faltante',
    ];

    // RELACIONES
    public function alistamiento()
    {
        return $this->belongsTo(Alistamiento::class);
    }


    public function product()
    {
        return $this->belongsTo(\App\Models\Crm\product::class, 'product_id');
    }
}
