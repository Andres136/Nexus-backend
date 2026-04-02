<?php

namespace App\Services\Vsm;


use App\Models\Crm\OrdenDeTrabajo;
use App\Models\Vsm\Alistamiento;
use App\Models\Vsm\AlistamientoDetalle;
use App\Models\Vsm\AlistamientoUsuario;
use App\Models\Vsm\AlistamientoUsuarioDetalle;
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


    public function getKpiProductividad($sedeId)
    {
        $usuarios = AlistamientoUsuario::with('usuario')
            ->whereHas('alistamiento.ordenTrabajo.ordenCompra', function ($q) use ($sedeId) {
                $q->where('sede_id', $sedeId);
            })
            ->get();

        $data = $usuarios->map(function ($pivot) {

            $produccion = AlistamientoUsuarioDetalle::where([
                'alistamiento_id' => $pivot->alistamiento_id,
                'usuario_id' => $pivot->usuario_id
            ])->sum('cantidad_alistada');

            $tiempo = $pivot->tiempo_segundos ?? 0;

            $bolsasHora = $tiempo > 0 ? ($produccion / $tiempo) * 3600 : 0;

            $metaHora = 705;

            $rendimiento = $metaHora > 0 ? ($bolsasHora / $metaHora) * 100 : 0;

            return [
                'usuario_id' => $pivot->usuario_id,
                'usuario' => $pivot->usuario->name,
                'produccion' => $produccion,
                'tiempo_segundos' => $tiempo,
                'bolsas_hora' => round($bolsasHora, 2),
                'rendimiento' => round($rendimiento, 1),
            ];
        });

        return [
            'usuarios' => $data,
            'total_produccion' => $data->sum('produccion'),
            'promedio_rendimiento' => round($data->avg('rendimiento'), 1),
        ];
    }

}
