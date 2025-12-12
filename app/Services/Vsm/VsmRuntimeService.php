<?php

namespace App\Services\Vsm;


use App\Models\Crm\OrdenDeTrabajo;
use App\Models\Vsm\Alistamiento;
use App\Models\Vsm\AlistamientoDetalle;
use Illuminate\Support\Facades\DB;

class VsmRuntimeService
{
    /**
     * Distribuir el tiempo total del alistamiento entre sus detalles proporcionalmente.
     */
        public function distribuirTiempoPorDetalles(Alistamiento $alist)
{
    $tiempoTotal = $alist->duracion_segundos ?? 0;
    $detalles = $alist->detalles;

    if ($tiempoTotal <= 0 || $detalles->isEmpty()) {
        return;
    }

    // Base: unidades alistadas o programadas
    $totalUnidades = $detalles->sum(function ($d) {
        return $d->cantidad_alistada > 0 
            ? $d->cantidad_alistada
            : $d->cantidad_programada;
    });

    if ($totalUnidades <= 0) {
        return;
    }

    $acumulado = 0;
    $ultimo = $detalles->count() - 1;

    foreach ($detalles as $index => $d) {

        $base = $d->cantidad_alistada > 0 
            ? $d->cantidad_alistada
            : $d->cantidad_programada;

        if ($base <= 0) {
            $d->tiempo_parcial_segundos = 0;
            $d->save();
            continue;
        }

        if ($index === $ultimo) {
            $tParcial = $tiempoTotal - $acumulado;  // corregir redondeo
        } else {
            $proporcion = $base / $totalUnidades;
            $tParcial = (int) round($tiempoTotal * $proporcion);
            $acumulado += $tParcial;
        }

        $d->tiempo_parcial_segundos = $tParcial;
        $d->save();
    }
}

}
