<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;

class PrestamoStockEmpresa extends Model
{
    protected $table = 'prestamos_stock_empresas';

    protected $fillable = [
        'movimiento_stock_id',
        'orden_compra_id',
        'producto_id',
        'bodega_id',
        'inventario_id',
        'empresa_prestamista_id',
        'empresa_prestataria_id',
        'cantidad',
        'compensado',
        'compensado_at',
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'compensado' => 'boolean',
        'compensado_at' => 'datetime',
    ];

    public function movimientoStock()
    {
        return $this->belongsTo(MovimientoStock::class, 'movimiento_stock_id');
    }

    public function ordenCompra()
    {
        return $this->belongsTo(Orden_Compra::class, 'orden_compra_id');
    }

    public function producto()
    {
        return $this->belongsTo(product::class, 'producto_id');
    }

    public function bodega()
    {
        return $this->belongsTo(bodega::class, 'bodega_id');
    }

    public function inventario()
    {
        return $this->belongsTo(Inventario::class, 'inventario_id');
    }

    public function empresaPrestamista()
    {
        return $this->belongsTo(empresa::class, 'empresa_prestamista_id');
    }

    public function empresaPrestataria()
    {
        return $this->belongsTo(empresa::class, 'empresa_prestataria_id');
    }
}
