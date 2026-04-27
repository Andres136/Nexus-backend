<?php

namespace App\Models\contabilidad;

use App\Models\Crm\empresa;
use App\Models\Crm\Proveedor;
use App\Models\Crm\Sede;
use App\Models\Estados;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FacturaCompra extends Model
{
    //

    protected $fillable = [
        'uuid',
        'proveedor_id',
        'empresa_id',
        'sede_id',
        'estado_id',
        'user_id',
        'numero_factura',
        'fecha_emision',
        'fecha_vencimiento',
        'observaciones',
        'subtotal',
        'total',
        'numero_factura_proveedor',
        'pdf_url'
    ];

protected $attributes = [
    'estado_id' => 1
];

    // Relaciones
    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function sede()
    {
        return $this->belongsTo(Sede::class, 'sede_id');
    }
    public function estado()
    {
        return $this->belongsTo(Estados::class, 'estado_id');
    }

     public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function detalles()
    {
        return $this->hasMany(DetalleFacturaCompra::class, 'factura_compra_id');
    }

    public function pagos()
    {
        return $this->hasMany(FacturaPago::class, 'factura_compras_id');
    }

    public function gastos()
    {
        return $this->hasMany(CompraGasto::class, 'factura_compras_id');
    }

    public function empresa()
    {
        return $this->belongsTo(empresa::class, 'empresa_id');
    }

 public function facturaImpuestos()
{
    return $this->hasMany(FacturaCompraImpuesto::class, 'factura_compras_id');
}

public function impuestos()
{
    return $this->belongsToMany(Impuesto::class, 'factura_compra_impuestos', 'factura_compras_id', 'impuestos_id')
                ->withPivot('monto');
}

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($facturaCompra) {
            if (empty($facturaCompra->uuid)) {
                $facturaCompra->uuid = (string) Str::uuid();
            }
        });
    }
}
