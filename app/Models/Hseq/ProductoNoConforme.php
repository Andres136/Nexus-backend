<?php

namespace App\Models\Hseq;

use App\Models\Crm\Cliente;
use App\Models\Crm\Orden_Compra;
use App\Models\Crm\OrdenCompraProveedor;
use App\Models\Crm\Proveedor;
use App\Models\Estados;
use App\Models\Procesos;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ProductoNoConforme extends Model
{
    protected $table = 'productos_no_conformes';

    const ORIGEN_CLIENTE = 'cliente';
    const ORIGEN_PROVEEDOR = 'proveedor';
    const ORIGEN_INTERNO = 'interno';

    protected $fillable = [
        'origen',
        'cliente_id',
        'proveedor_id',
        'comercial_id',
        'proceso_id',
        'orden_compra_id',
        'orden_compra_proveedor_id',
        'fecha_reporte',
        'descripcion_inicial',
        'tipo_falla',
        'estado_id'
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function comercial()
    {
        return $this->belongsTo(User::class, 'comercial_id');
    }

    public function proceso()
    {
        return $this->belongsTo(Procesos::class, 'proceso_id');
    }

    public function items()
    {
        return $this->hasMany(ProductoNoConformeItem::class, 'producto_no_conforme_id');
    }

    public function ordenCompra()
    {
        return $this->belongsTo(Orden_Compra::class, 'orden_compra_id');
    }

    public function ordenCompraProveedor()
    {
        return $this->belongsTo(OrdenCompraProveedor::class, 'orden_compra_proveedor_id');
    }

    public function estado()
    {
        return $this->belongsTo(Estados::class, 'estado_id');
    }

    public function analisis()
    {
        return $this->hasOne(AnalisisProductoNoConforme::class, 'producto_no_conforme_id');
    }
}
