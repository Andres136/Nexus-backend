<?php

namespace App\Services\Crm;

use App\Models\Crm\Cliente;
use App\Models\Crm\Cotizacion;
use App\Models\Crm\Orden_Compra;
use App\Models\Crm\SeguimientoCliente;
use Illuminate\Support\Carbon;

class KpiService
{
    public function getDashboardKpisYearly(?int $year = null)
    {
        $year = $year ?? now()->year;

        $inicio = Carbon::create($year, 1, 1)->startOfDay();
        $fin    = Carbon::create($year, 12, 31)->endOfDay();

        // Helpers
        $months = collect(range(1, 12))->map(function ($m) use ($year) {
            return [
                'month' => $m,
                'label' => Carbon::create($year, $m, 1)->translatedFormat('M'), // Ene, Feb...
                'key'   => str_pad($m, 2, '0', STR_PAD_LEFT),                    // "01".."12"
            ];
        });

        // =========================
        // 1) CLIENTES NUEVOS (mensual)
        // =========================
     $clientesNuevosByMonth = Orden_Compra::selectRaw('MONTH(MIN(created_at)) as mes, COUNT(DISTINCT cliente_id) as total')
    ->whereBetween('created_at', [$inicio, $fin])
    ->groupBy('cliente_id')
    ->get()
    ->groupBy('mes')
    ->map(function ($items) {
        return $items->count();
    });

        // Total clientes acumulado (para tasas globales si lo necesitas)
        $clientesTotales = Cliente::count();

        // =========================
        // 2) COTIZACIONES (mensual)
        // =========================
        $cotizacionesByMonth = Cotizacion::selectRaw('MONTH(created_at) as mes, COUNT(*) as total')
            ->whereBetween('created_at', [$inicio, $fin])
            ->groupBy('mes')
            ->pluck('total', 'mes');

        // =========================
        // 3) ORDENES + VENTAS (mensual)
        // =========================
        $ordenesByMonth = Orden_Compra::selectRaw('MONTH(created_at) as mes, COUNT(*) as total')
            ->whereBetween('created_at', [$inicio, $fin])
            ->groupBy('mes')
            ->pluck('total', 'mes');

        $ventasByMonth = Orden_Compra::selectRaw('MONTH(created_at) as mes, COALESCE(SUM(valor_total),0) as total')
            ->whereBetween('created_at', [$inicio, $fin])
            ->groupBy('mes')
            ->pluck('total', 'mes');

        // =========================
        // 4) RETENCION (mensual, con lo que tienes)
        //    -> perdidos por mes desde seguimiento_clientes (si tiene created_at)
        // =========================
        $perdidosByMonth = SeguimientoCliente::selectRaw('MONTH(created_at) as mes, COUNT(*) as total')
            ->whereBetween('created_at', [$inicio, $fin])
            ->where('estado', 'perdido')
            ->groupBy('mes')
            ->pluck('total', 'mes');

        // =========================
        // 5) KPI POR USUARIO (mensual)
        // =========================
        $ventasPorUsuarioMensual = Orden_Compra::selectRaw('MONTH(created_at) as mes, user_id, COUNT(*) as total_ordenes, COALESCE(SUM(valor_total),0) as ventas')
            ->whereBetween('created_at', [$inicio, $fin])
            ->groupBy('mes', 'user_id')
            ->with('user:id,name')
            ->get()
            ->groupBy('mes'); // agrupa por mes para devolverlo fácil

        // =========================
        // Armado final (12 meses siempre)
        // =========================
        $series = $months->map(function ($m) use (
            $clientesNuevosByMonth,
            $cotizacionesByMonth,
            $ordenesByMonth,
            $ventasByMonth,
            $perdidosByMonth,
            $ventasPorUsuarioMensual
        ) {
            $mes = $m['month'];

            $clientesNuevos = (int) ($clientesNuevosByMonth[$mes] ?? 0);
            $cotizaciones   = (int) ($cotizacionesByMonth[$mes] ?? 0);
            $ordenes        = (int) ($ordenesByMonth[$mes] ?? 0);
            $ventas         = (float) ($ventasByMonth[$mes] ?? 0);
            $perdidos       = (int) ($perdidosByMonth[$mes] ?? 0);

            // Conversión mensual: ordenes / cotizaciones
            $conversion = $cotizaciones > 0 ? ($ordenes / $cotizaciones) * 100 : 0;

            // Retención mensual: aquí es aproximada con "perdidos" (lo más coherente con tu modelo actual)
            // Si luego agregas "clientes_activos_mes" o "estado=activo", se mejora.
            $retencion = null; // si quieres mostrarla, se puede poner fórmula diferente

            return [
                'month' => $mes,
                'label' => $m['label'],
                'clientes_nuevos' => $clientesNuevos,
                'cotizaciones'    => $cotizaciones,
                'ordenes'         => $ordenes,
                'ventas'          => $ventas,
                'conversion_pct'  => round($conversion, 2),
                'clientes_perdidos' => $perdidos,

                // Ranking por usuario en ese mes (si no hay, [])
                'ventas_por_usuario' => ($ventasPorUsuarioMensual[$mes] ?? collect())->values(),
            ];
        })->values();

        // Totales del año
        $totalesAnio = [
            'clientes_nuevos' => $series->sum('clientes_nuevos'),
            'cotizaciones'    => $series->sum('cotizaciones'),
            'ordenes'         => $series->sum('ordenes'),
            'ventas'          => $series->sum('ventas'),
            'clientes_perdidos' => $series->sum('clientes_perdidos'),
        ];

        $conversionAnual = $totalesAnio['cotizaciones'] > 0
            ? ($totalesAnio['ordenes'] / $totalesAnio['cotizaciones']) * 100
            : 0;

        return [
            'year' => $year,

            'totales' => [
                ...$totalesAnio,
                'conversion_pct' => round($conversionAnual, 2),
                'clientes_totales' => $clientesTotales,
            ],

            // Serie lista para charts (12 puntos)
            'series_mensual' => $series,
        ];
    }
}