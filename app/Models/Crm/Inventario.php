<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;

class Inventario extends Model
{
    protected $table = 'inventories';

    protected $fillable = [
        'producto_id',
        'empresa_id',
        'sede_id',
        'bodega_id',
        'stock',
        'precio',
        'min_stock',
        'max_stock',
        'fecha_vencimiento',
    ];

    // Relaciones con otros modelos (si es necesario)
    public function producto()
    {
        return $this->belongsTo(Product::class, 'producto_id');
    }
    public function empresa()
    {
        return $this->belongsTo(empresa::class, 'empresa_id');
    }
    public function sede()
    {
        return $this->belongsTo(Sede::class, 'sede_id');
    }
    public function bodega()
    {
        return $this->belongsTo(bodega::class, 'bodega_id');
    }

}
