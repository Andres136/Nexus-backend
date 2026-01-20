<?php

namespace App\Providers;

use App\Events\Traslados\TrasladoAprobadorPorBodega;
use App\Events\Traslados\TrasladoCreado;
use App\Listeners\Traslados\NotificarResponsable;
use App\Listeners\Traslados\NotificarResponsableInventario;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */


    protected $listen = [
        TrasladoCreado::class => [
            NotificarResponsable::class,
        ],
        TrasladoAprobadorPorBodega::class => [
            NotificarResponsableInventario::class,
        ],
    ];

    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        parent::boot();
    }
}
