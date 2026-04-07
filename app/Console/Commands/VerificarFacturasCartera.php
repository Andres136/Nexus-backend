<?php

namespace App\Console\Commands;

use App\Models\Crm\GestionCartera;
use App\Models\User;
use App\Notifications\Crm\FacturaCarteraNotification;
use Illuminate\Console\Command;

class VerificarFacturasCartera extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:verificar-facturas-cartera';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
 public function handle()
{
    $hoy = now();

    // 🟥 VENCIDAS
    $vencidas = GestionCartera::with('comercial')
        ->where('estado', 'pendiente')
        ->whereDate('fecha_vencimiento', '<', $hoy)
        ->get();

    // 🟡 PRÓXIMAS
    $proximas = GestionCartera::with('comercial')
        ->where('estado', 'pendiente')
        ->whereBetween('fecha_vencimiento', [
            $hoy,
            $hoy->copy()->addDays(15)
        ])
        ->get();

    // 🔥 VENCIDAS
    foreach ($vencidas as $factura) {

        if ($factura->comercial) {
            $factura->comercial->notify(
                new FacturaCarteraNotification($factura, 'vencida')
            );
        }
    }

    // 🔥 PRÓXIMAS
    foreach ($proximas as $factura) {

        if ($factura->comercial) {
            $factura->comercial->notify(
                new FacturaCarteraNotification($factura, 'proxima')
            );
        }
    }

    return 0;
}
}
