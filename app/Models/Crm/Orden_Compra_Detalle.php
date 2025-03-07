<?php

namespace App\Models\Crm;

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
        return $this->belongsTo(Orden_Compra::class, 'orden_compra_id');
    }
    
}
