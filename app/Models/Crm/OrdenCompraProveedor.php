<?php

namespace App\Models\Crm;

use App\Models\Estados;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class OrdenCompraProveedor extends Model
{
    protected $table = 'orden_compra_proveedores';
    protected $fillable = [
        'proveedor_id',
        'fecha',
        'numero_orden',
        'estado_id',
        'usuario_id',
        'observaciones',
        'empresa_id',
        'bodega_id',
        'sede_id',
    ];


    protected $casts = [
        'fecha' => 'date',
    ];
//Relacion con el modelo Proveedor
    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }
//Relacion con el modelo Detalle
    public function detalles()
    {
        return $this->hasMany(OrdenCompraProveedorDetalle::class, 'orden_id');
    }
//Relacion con el modelo Estados
    public function estado()
    {
        return $this->belongsTo(Estados::class, 'estado_id');
    }
//Relacion con el modelo User
    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    //Relacion con el modelo Empresa
    public function empresa()
    {
        return $this->belongsTo(empresa::class, 'empresa_id');
    }

    //Relacion con el modelo Bodega
    public function bodega()
    {
        return $this->belongsTo(bodega::class, 'bodega_id');
    }

    //Relacion con el modelo Sede
    public function sede()
    {
        return $this->belongsTo(Sede::class, 'sede_id');
    }

}
