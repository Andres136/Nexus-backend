<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;

class Inventario extends Model
{
    protected $table = 'inventories';

    protected $fillable = [
        'producto_id',
        'empresa_id',
        'user_id',
        'sede_id',
        'bodega_id',
        'stock',
        'precio',
        'min_stock',
        'max_stock',
        'fecha_vencimiento',
    ];

    protected $casts = [
        'fecha_vencimiento' => 'date',
    ];

    // Relaciones con otros modelos (si es necesario)
    public function producto()
    {
        return $this->belongsTo(product::class, 'producto_id');
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
protected static function booted()
{
    static::creating(function ($inventario) {
        logger()->error('🚨 INVENTARIO CREADO FUERA DEL IMPORT', [
            'attributes' => $inventario->getAttributes(),
            'trace' => collect(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10))
                ->pluck('file')
                ->filter()
                ->values(),
        ]);
    });
}



}
