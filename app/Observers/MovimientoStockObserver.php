<?php

namespace App\Observers;

use App\Models\Crm\Inventario;
use App\Models\Crm\MovimientoStock;

class MovimientoStockObserver
{
    /**
     * Handle the MovimientoStock "created" event.
     */
    public function created(MovimientoStock $movimientoStock): void
    {
     $inventario = Inventario::firstOrCreate(
            [
                'empresa_id'  => $movimientoStock->empresa_id,
                'producto_id' => $movimientoStock->producto_id,
                'bodega_id'   => $movimientoStock->bodega_id,
            ],
            [
                'stock' => 0,
            ]
        );

        if ($movimientoStock->tipo === 'SALIDA') {
            $inventario->decrement('stock', $movimientoStock->cantidad);
        }

        if ($movimientoStock->tipo === 'ENTRADA') {
            $inventario->increment('stock', $movimientoStock->cantidad);
        }
    }

    /**
     * Handle the MovimientoStock "updated" event.
     */
    public function updated(MovimientoStock $movimientoStock): void
    {
        //
    }

    /**
     * Handle the MovimientoStock "deleted" event.
     */
    public function deleted(MovimientoStock $movimientoStock): void
    {
        //
    }

    /**
     * Handle the MovimientoStock "restored" event.
     */
    public function restored(MovimientoStock $movimientoStock): void
    {
        //
    }

    /**
     * Handle the MovimientoStock "force deleted" event.
     */
    public function forceDeleted(MovimientoStock $movimientoStock): void
    {
        //
    }
}
