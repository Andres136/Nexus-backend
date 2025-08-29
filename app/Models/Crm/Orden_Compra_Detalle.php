<?php

namespace App\Models\Crm;
use App\Models\Crm\OrdenTrabajoEntrega;
use Illuminate\Database\Eloquent\Model;

class Orden_Compra_Detalle extends Model
{
    // 
    protected $table = 'orden__compra__detalles';
    protected $fillable = [
        'orden_compra_id',
        'largo_cm',
        'ancho_cm',
        'calibre',
        'cantidad',
        'cantidad_enviada',
        'faltantes',
        'valor_unitario',
        'peso_bolsa',
        'numero_bolsas',
        'cliente_clb',
        'cantidad_requerida_kg',
        'valor_total',
        'descripcion',
        'observaciones' 
    ];


    // funcion relacion con orden de compra

    public function orden(){
        return 
        $this->belongsTo(Orden_Compra::class, 'orden_compra_id');
    }

    //Relacion con la tabla orden_trabajo_entregas

public function entregas()
{
    return $this->hasMany(\App\Models\Crm\OrdenTrabajoEntrega::class, 'detalle_id', 'id')
        ->with('usuario:id,name')
        ->orderBy('created_at','asc');
}



}
