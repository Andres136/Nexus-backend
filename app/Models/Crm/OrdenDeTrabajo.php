<?php

namespace App\Models\Crm;

use App\Models\Estados;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use App\Models\Crm\Cliente;


class OrdenDeTrabajo extends Model
{
    //
    protected $table = 'orden_de_trabajos';
    protected $fillable = [
        'orden_compra_id',
        'cliente_id',
        'fecha_entrega',
        'observaciones',
        'valor_total',
        'faltantes',
        'estado_id',
        'user_id'

    ];

    //Relacion con la tabla orden_compras
    public function ordenCompra()
    {
        return $this->belongsTo(Orden_Compra::class, 'orden_compra_id');
    }

    //Relacion con la tabla clientes
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    //Relacion con la tabla estados
    public function estado()
    {
        return $this->belongsTo(Estados::class, 'estado_id');
    }
    //Relacion con la tabla users
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }




    //Relacion con la tabla orden_trabajo_entregas

    public function entregas()
    {
        return $this->hasMany(OrdenTrabajoEntrega::class, 'orden_trabajo_id', 'id')
                    ->with('usuario:id,name') // ✅ Incluimos usuario en la misma relación
                    ->orderBy('created_at', 'asc');
    }
    public function detalles() {
    return $this->hasMany(Orden_Compra_Detalle::class, 'orden_compra_id', 'orden_compra_id')
        ->with(['entregas' => fn($q) => $q->with('usuario:id,name')->orderBy('created_at','asc')]);
}
//Relacion con movimientos stock
    public function movimientosStock()
    {
        return $this->hasMany(MovimientoStock::class, 'orden_trabajo_id', 'id');
    }   


}
