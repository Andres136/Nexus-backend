<?php

namespace App\Models\Crm;

use App\Models\contabilidad\DetalleFacturaCompra;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class KardexMovimiento extends Model
{
    protected $table = 'kardex_movimientos';

    protected $fillable = [
        'inventario_id',
        'movimiento_stock_id',
        'factura_compra_detalle_id',
        'tipo',
        'cantidad',
        'costo_unitario',
        'costo_total',
        'saldo_cantidad',
        'saldo_costo_unitario',
        'saldo_costo_total',
        'usuario_id',
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'costo_unitario' => 'decimal:4',
        'costo_total' => 'decimal:2',
        'saldo_cantidad' => 'decimal:2',
        'saldo_costo_unitario' => 'decimal:4',
        'saldo_costo_total' => 'decimal:2',
    ];

    public function inventario()
    {
        return $this->belongsTo(Inventario::class, 'inventario_id');
    }

    public function movimientoStock()
    {
        return $this->belongsTo(MovimientoStock::class, 'movimiento_stock_id');
    }

    public function facturaCompraDetalle()
    {
        return $this->belongsTo(DetalleFacturaCompra::class, 'factura_compra_detalle_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
