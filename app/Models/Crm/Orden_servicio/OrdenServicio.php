<?php

namespace App\Models\Crm\Orden_servicio;

use App\Models\Crm\empresa;
use App\Models\Crm\OrdenCompraProveedor;
use App\Models\Crm\Proveedor;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class OrdenServicio extends Model
{
    protected $table = 'ordenes_servicio';
    protected $fillable = [
        'numero_os',
        'empresa_id',
        'proveedor_id',
        'fecha',
        'estado',
        'usuario_id',
        'observaciones',
    ];

    //Relación con detalles de orden de servicio
    public function detalles()
    {
        return $this->hasMany(OrdenServicioDetalle::class, 'orden_servicio_id');
    }

    //Relación con proveedor
    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    //Relacion con orden de compra proveedor
    public function ordenCompraProveedor()
    {
        return $this->belongsTo(OrdenCompraProveedor::class, 'orden_compra_proveedor_id');
    }

    //Relación con usuario
    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    //Relación con empresa
    public function empresa()
    {
        return $this->belongsTo(empresa::class, 'empresa_id');
    }   
}
