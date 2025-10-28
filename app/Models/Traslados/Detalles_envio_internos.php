<?php

namespace App\Models\Traslados;

use App\Models\Crm\bodega;
use App\Models\Crm\OrdenCompraProveedor;
use App\Models\Crm\product;
use Illuminate\Database\Eloquent\Model;

class Detalles_envio_internos extends Model
{
    protected $table = 'detalles_envio_internos';

    protected $fillable = [
        'envio_interno_id',
        'product_id',
        'code_id',
        'descripcion',
        'cantidad',
        'bodega_origen_id',
        'orden_compra_id',
        'item',
    ];


    // Relaciones
    public function envioInterno()
    {
        return $this->belongsTo(Envio_internos::class, 'envio_interno_id');
    }
    public function product()
    {
        return $this->belongsTo(product::class, 'product_id');
    }
    public function bodegaOrigen()
    {
        return $this->belongsTo(bodega::class, 'bodega_origen_id');
    }

    public function ordenCompra()
    {
        return $this->belongsTo(OrdenCompraProveedor::class, 'orden_compra_id');
    }
}
