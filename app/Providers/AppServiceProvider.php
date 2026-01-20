<?php

namespace App\Providers;

use App\Models\Crm\MovimientoStock;
use App\Observers\MovimientoStockObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider

{
    /**
     * Register any application services.
     */

  
    
    public function register(): void
    {
        //

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        MovimientoStock::observe(MovimientoStockObserver::class);
    }
}
