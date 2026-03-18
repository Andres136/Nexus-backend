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


public static function getStockOrSimilarFromCollection($producto, $inventarios, $empresaId = null, $sedeId = null)
{
    // 🔹 1. STOCK REAL (filtrado)
    $items = $inventarios->where('producto_id', $producto->id);

    if ($empresaId) $items = $items->where('empresa_id', $empresaId);
    if ($sedeId) $items = $items->where('sede_id', $sedeId);

    $stock = $items->sum('stock');

    // ✅ SI HAY STOCK → mostrar con ubicación
    if ($stock > 0) {
        return $items->map(function ($item) {
            $bodega = $item->bodega->nombre ?? 'Sin bodega';
            $sede   = $item->sede->nombre ?? 'Sin sede';

            return $item->producto->code .           // ✅ CORREGIDO
                   ' (' . $item->stock . ' - ' . $bodega . ' / ' . $sede . ')';
        })->implode(', ');
    }

    // 🔹 2. BUSCAR SIMILARES (SIN filtrar empresa/sede)
    $nombreBase = strtolower($producto->name ?? '');       // ✅ CORREGIDO
    $codigoBase = strtolower($producto->code ?? '');       // ✅ CORREGIDO
    $descBase   = strtolower($producto->description ?? '');

    $palabras = array_filter(explode(' ', $nombreBase));

    $alternativas = $inventarios->filter(function ($item) use ($producto, $palabras, $codigoBase, $descBase) {

        if ($item->producto_id == $producto->id) return false;
        if ($item->stock <= 0) return false;

        $nombreItem = strtolower($item->producto->name ?? '');        // ✅ CORREGIDO
        $codigoItem = strtolower($item->producto->code ?? '');        // ✅ CORREGIDO
        $descItem   = strtolower($item->producto->description ?? '');

        foreach ($palabras as $palabra) {
            if (
                str_contains($nombreItem, $palabra) ||
                str_contains($codigoItem, $palabra) ||
                str_contains($descItem, $palabra)
            ) {
                return true;
            }
        }

        return str_contains($codigoItem, $codigoBase);
    })
    ->sortByDesc('stock')
    ->take(3);

    // ❌ SI NO HAY NADA
    if ($alternativas->isEmpty()) {
        return 'Sin stock | Sin sugerencias';
    }

    // ✅ MOSTRAR SUGERENCIAS CON UBICACIÓN
    return 'Sin stock | Sugerencias: ' . $alternativas->map(function ($item) {
        $bodega = $item->bodega->nombre ?? 'Sin bodega';
        $sede   = $item->sede->nombre ?? 'Sin sede';

        return $item->producto->code .               // ✅ CORREGIDO
               ' (' . $item->stock . ' - ' . $bodega . ' / ' . $sede . ')';
    })->implode(', ');
}

}
