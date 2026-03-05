<?php

namespace App\Services\Crm;

use App\Models\Crm\Cliente;
use App\Models\Crm\Cotizacion;
use App\Models\Crm\Orden_Compra;
use App\Models\Crm\SeguimientoCliente;
use Illuminate\Support\Carbon;


class KpiService
{
    public function getDashboardKpisYearly(?int $year = null): array
    {
        $year = $year ?? now()->year;

        $inicio = Carbon::create($year, 1, 1)->startOfDay();
        $fin    = Carbon::create($year, 12, 31)->endOfDay();

        $months = collect(range(1, 12))->map(function ($m) use ($year) {
            return [
                'month' => $m,
                'label' => Carbon::create($year, $m, 1)->translatedFormat('M'),
            ];
        });

        // =========================
        // BASE: Clientes totales (base de cálculo)
        // =========================
        $clientesTotales = (int) Cliente::count();

        // =========================
        // ORDENES: agregados mensuales + compradores mensuales
        // =========================
        $ordenesAggByMonth = Orden_Compra::selectRaw("
                MONTH(created_at) as mes,
                COUNT(*) as ordenes,
                COALESCE(SUM(valor_total),0) as ventas,
                COUNT(DISTINCT cliente_id) as clientes_con_orden
            ")
            ->whereBetween('created_at', [$inicio, $fin])
            ->groupBy('mes')
            ->get()
            ->keyBy('mes');

        // =========================
        // CLIENTES NUEVOS (por primera compra del cliente)
        // "Nuevo" = mes en el que ocurrió su PRIMERA orden
        // =========================
        $clientesNuevosByMonth = Orden_Compra::selectRaw("cliente_id, MONTH(MIN(created_at)) as mes_primera_compra")
            ->whereBetween('created_at', [$inicio, $fin])
            ->groupBy('cliente_id')
            ->get()
            ->groupBy('mes_primera_compra')
            ->map(fn ($items) => $items->count());

        // =========================
        // COTIZACIONES: mensual
        // =========================
        $cotizacionesByMonth = Cotizacion::selectRaw("MONTH(created_at) as mes, COUNT(*) as total")
            ->whereBetween('created_at', [$inicio, $fin])
            ->groupBy('mes')
            ->pluck('total', 'mes');

        // =========================
        // SEGUIMIENTOS: gestión + perdidos (mensual)
        // Gestionado = cliente con al menos 1 seguimiento en el año / mes
        // Perdido = seguimientos estado=perdido (conteo de eventos)
        // =========================
        $gestionByMonth = SeguimientoCliente::selectRaw("MONTH(created_at) as mes, COUNT(DISTINCT cliente_id) as clientes_gestionados")
            ->whereBetween('created_at', [$inicio, $fin])
            ->groupBy('mes')
            ->pluck('clientes_gestionados', 'mes');

        $perdidosByMonth = SeguimientoCliente::selectRaw("MONTH(created_at) as mes, COUNT(*) as total")
            ->whereBetween('created_at', [$inicio, $fin])
            ->where('estado', 'perdido')
            ->groupBy('mes')
            ->pluck('total', 'mes');

        // =========================
        // FIDELIZACION: clientes con 2+ ordenes en el año
        // - clientes_fieles_anio: cantidad de clientes con >=2 ordenes en el año
        // - fieles_por_mes: clientes que compraron en el mes y que en el año tienen >=2 ordenes
        // =========================
        $clientesFielesAnio = (int) Orden_Compra::selectRaw("cliente_id")
            ->whereBetween('created_at', [$inicio, $fin])
            ->groupBy('cliente_id')
            ->havingRaw('COUNT(*) >= 2')
            ->count();

        $fielesPorMes = Orden_Compra::selectRaw("MONTH(created_at) as mes, cliente_id")
            ->whereBetween('created_at', [$inicio, $fin])
            ->whereIn('cliente_id', function ($q) use ($inicio, $fin) {
                $q->select('cliente_id')
                    ->from((new Orden_Compra)->getTable())
                    ->whereBetween('created_at', [$inicio, $fin])
                    ->groupBy('cliente_id')
                    ->havingRaw('COUNT(*) >= 2');
            })
            ->groupBy('mes', 'cliente_id')
            ->get()
            ->groupBy('mes')
            ->map(fn ($items) => $items->count()); // count distinct cliente_id por mes

        // =========================
        // KPI POR USUARIO (mensual)
        // =========================
        $ventasPorUsuarioMensual = Orden_Compra::selectRaw("
                MONTH(created_at) as mes,
                user_id,
                COUNT(*) as total_ordenes,
                COALESCE(SUM(valor_total),0) as ventas
            ")
            ->whereBetween('created_at', [$inicio, $fin])
            ->groupBy('mes', 'user_id')
            ->with('user:id,name')
            ->get()
            ->groupBy('mes');

        // =========================
        // Totales anuales “CRM”
        // =========================
        $clientesConOrdenAnio = (int) Orden_Compra::whereBetween('created_at', [$inicio, $fin])
            ->distinct('cliente_id')
            ->count('cliente_id');

        $clientesGestionadosAnio = (int) SeguimientoCliente::whereBetween('created_at', [$inicio, $fin])
            ->distinct('cliente_id')
            ->count('cliente_id');

        $conversionClientesPct = $clientesTotales > 0
            ? ($clientesConOrdenAnio / $clientesTotales) * 100
            : 0;

        $gestionPct = $clientesTotales > 0
            ? ($clientesGestionadosAnio / $clientesTotales) * 100
            : 0;

        $fidelizacionPct = $clientesConOrdenAnio > 0
            ? ($clientesFielesAnio / $clientesConOrdenAnio) * 100
            : 0;

        // =========================
        // Serie mensual (12 meses siempre)
        // =========================
        $series = $months->map(function ($m) use (
            $clientesTotales,
            $ordenesAggByMonth,
            $clientesNuevosByMonth,
            $cotizacionesByMonth,
            $gestionByMonth,
            $perdidosByMonth,
            $fielesPorMes,
            $ventasPorUsuarioMensual
        ) {
            $mes = $m['month'];

            $ordenAgg = $ordenesAggByMonth->get($mes);

            $ordenes  = (int) ($ordenAgg->ordenes ?? 0);
            $ventas   = (float) ($ordenAgg->ventas ?? 0);
            $compradoresMes = (int) ($ordenAgg->clientes_con_orden ?? 0);

            $clientesNuevos = (int) ($clientesNuevosByMonth[$mes] ?? 0);
            $cotizaciones   = (int) ($cotizacionesByMonth[$mes] ?? 0);

            $clientesGestionadosMes = (int) ($gestionByMonth[$mes] ?? 0);
            $perdidos = (int) ($perdidosByMonth[$mes] ?? 0);

            $clientesFielesMes = (int) ($fielesPorMes[$mes] ?? 0);

            // Conversión “a compra” mensual (por clientes, no por cotizaciones)
            $conversionClientesMes = $clientesTotales > 0
                ? ($compradoresMes / $clientesTotales) * 100
                : 0;

            // Gestión mensual
            $gestionMes = $clientesTotales > 0
                ? ($clientesGestionadosMes / $clientesTotales) * 100
                : 0;

            // Fidelización mensual (de compradores del mes, cuántos son fieles)
            $fidelizacionMes = $compradoresMes > 0
                ? ($clientesFielesMes / $compradoresMes) * 100
                : 0;

            // Conversión “clásica” por funnel (cotizaciones -> órdenes) (la dejo también)
            $conversionCotizacionesMes = $cotizaciones > 0
                ? ($ordenes / $cotizaciones) * 100
                : 0;

            return [
                'month' => $mes,
                'label' => $m['label'],

                // Operación
                'cotizaciones' => $cotizaciones,
                'ordenes'      => $ordenes,
                'ventas'       => $ventas,

                // Clientes
                'clientes_nuevos'        => $clientesNuevos,
                'clientes_con_orden'     => $compradoresMes,
                'clientes_gestionados'   => $clientesGestionadosMes,
                'clientes_fieles'        => $clientesFielesMes,
                'clientes_perdidos'      => $perdidos,

                // KPIs (%)
                'conversion_clientes_pct'     => round($conversionClientesMes, 2),
                'gestion_clientes_pct'        => round($gestionMes, 2),
                'fidelizacion_clientes_pct'   => round($fidelizacionMes, 2),

                // Funnel clásico (si lo quieres mostrar en el dashboard)
                'conversion_cotizaciones_pct' => round($conversionCotizacionesMes, 2),

                // Ranking mensual
                'ventas_por_usuario' => ($ventasPorUsuarioMensual[$mes] ?? collect())->values(),
            ];
        })->values();

        // =========================
        // Totales anuales numéricos (sumas)
        // =========================
        $totalesAnio = [
            'clientes_nuevos' => (int) $series->sum('clientes_nuevos'),
            'cotizaciones'    => (int) $series->sum('cotizaciones'),
            'ordenes'         => (int) $series->sum('ordenes'),
            'ventas'          => (float) $series->sum('ventas'),
            'clientes_perdidos' => (int) $series->sum('clientes_perdidos'),
        ];

        // Ticket promedio anual (ventas / ordenes)
        $ticketPromedio = $totalesAnio['ordenes'] > 0
            ? $totalesAnio['ventas'] / $totalesAnio['ordenes']
            : 0;

        return [
            'year' => $year,

            'totales' => [
                ...$totalesAnio,

                // Base
                'clientes_totales' => $clientesTotales,

                // Conteos CRM
                'clientes_gestionados' => $clientesGestionadosAnio,
                'clientes_con_orden'   => $clientesConOrdenAnio,
                'clientes_fieles'      => $clientesFielesAnio,

                // KPIs CRM
                'gestion_clientes_pct'      => round($gestionPct, 2),
                'conversion_clientes_pct'   => round($conversionClientesPct, 2),
                'fidelizacion_clientes_pct' => round($fidelizacionPct, 2),

                // KPI financiero
                'ticket_promedio' => round($ticketPromedio, 2),

                // Si aún necesitas el KPI viejo por cotizaciones:
                'conversion_cotizaciones_pct' => $totalesAnio['cotizaciones'] > 0
                    ? round(($totalesAnio['ordenes'] / $totalesAnio['cotizaciones']) * 100, 2)
                    : 0,
            ],

            'series_mensual' => $series,
        ];
    }
}