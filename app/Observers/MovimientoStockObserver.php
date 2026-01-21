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
