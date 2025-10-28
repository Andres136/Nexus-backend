<?php

namespace App\Models\Traslados;

use App\Models\Crm\OrdenCompraProveedor;
use Illuminate\Database\Eloquent\Model;

class Envio_orden extends Model
{
    protected $table = 'envio_orden_compra';

    protected $fillable = [
        'envio_interno_id',
        'orden_compra_id',
    ];


    // Relaciones
    public function envioInterno()
    {
        return $this->belongsTo(Envio_internos::class, 'envio_interno_id');
    }
    public function ordenCompra()
    {
        return $this->belongsTo(OrdenCompraProveedor::class, 'orden_compra_id');
    }
}
