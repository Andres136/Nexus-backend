<?php

namespace App\Models\Crm;

use App\Models\Estados;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use App\Models\Crm\Cliente;
use App\Models\Vsm\Alistamiento;


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
        'user_id',
        'documento_revisado_at',
        'documento_revisado_por',
        'revisada',
        'revisada_por',
        'revisada_at',
        'despacho_revisado_at',
        'despacho_revisado_por',

    ];

    //Relacion con la tabla orden_compras
    public function ordenCompra()
    {
        return $this->belongsTo(Orden_Compra::class, 'orden_compra_id');
    }

    //Relacion con la tabla clientes
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id', 'id');
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



    //Relacion con la tabla usuarios que revisaron la orden de trabajo
    public function usuarioReviso()
    {
        return $this->belongsTo(User::class, 'revisada_por');
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

    public function alistamientos()
    {
        return $this->hasMany(Alistamiento::class, 'orden_trabajo_id');
    }
    //Relacion de usuario quien reviso la orden de trabajo
public function usuarioRevisor()

{
    return $this->belongsTo(User::class, 'documento_revisado_por');
}

    //Relacion de usuario que revisó la orden de trabajo al momento del despacho
    public function usuarioDespachoRevisor()
    {
        return $this->belongsTo(User::class, 'despacho_revisado_por');
    }

    // Carga lo necesario para poder calcular el estado del descuento de stock (ver calcularStockDescontadoCompleto)
    public function scopeConEstadoStock($query)
    {
        return $query
            ->with(['movimientosStock' => fn ($q) => $q
                ->where('anulado', false)
                ->whereIn('tipo', ['descuento', 'descuento_masivo'])
                ->select('id', 'orden_trabajo_id', 'created_at', 'usuario_id')])
            ->withCount([
                'detalles as detalles_a_descontar_count' => fn ($q) => $q
                    ->where('cantidad_requerida_kg', '>', 0),
                'detalles as detalles_pendientes_descuento_count' => fn ($q) => $q
                    ->where('cantidad_requerida_kg', '>', 0)
                    ->whereRaw('COALESCE(cantidad_ejecutada_kg, 0) < cantidad_requerida_kg'),
            ]);
    }

    // Requiere que se haya cargado con el scope conEstadoStock (movimientosStock filtrado + los withCount de detalles)
    public function calcularStockDescontadoCompleto(): bool
    {
        return $this->movimientosStock->isNotEmpty()
            && $this->detalles_a_descontar_count > 0
            && $this->detalles_pendientes_descuento_count === 0;
    }

}
