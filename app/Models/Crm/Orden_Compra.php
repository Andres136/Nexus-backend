<?php

namespace App\Models\Crm;

use App\Models\Estados;
use App\Models\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Crm\OrdenDeTrabajo; // Importar el modelo OrdenDeTrabajo
use App\Models\Crm\Cliente;
use App\Models\Departamentos;

class Orden_Compra extends Model
{
    //
    protected $table = 'orden__compras';
    protected $fillable = [
        'fecha_entrega',
        'cliente_id',
        'user_id',
        'estado_id',
        'ubicacion_entrega',
        'observaciones',
        'valor_total',
        'sede_id', // Agregar el campo sede_id
        'fecha_despacho' // Agregar el campo fecha_despacho
    ];

    // funcion relacion con detalles
    public function detalles():HasMany
    {
        return $this->hasMany(Orden_Compra_Detalle::class,'orden_compra_id');
    }
 

    // funcion relacion con cliente
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }
    // funcion relacion con usuario
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');

    }

    ///funcion para relacion con estado
    public function estado()
    {
        return $this->belongsTo(Estados::class, 'estado_id');
    }
 
    public function usuario() // Relación con el usuario que creó la orden
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    //relacion con ordenes de trabajo
 public function ordenesTrabajo()
 {
     return $this->hasMany(OrdenDeTrabajo::class,'orden_compra_id');
 }

 // funcion creador de orden de compra
    public function creador()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function ordenTrabajo()
{
    return $this->hasOne(OrdenDeTrabajo::class , 'orden_compra_id');
}
    public function sede()
    {
        return $this->belongsTo(Sede::class, 'sede_id');
    }


 
}
