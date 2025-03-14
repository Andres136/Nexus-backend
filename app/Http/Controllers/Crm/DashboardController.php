<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\Crm\Cliente;
use App\Models\Crm\Orden_Compra;
use App\Models\Crm\OrdenDeTrabajo;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function getDashboardData()
    {
        // Contar órdenes de compra del mes actual
        $numUltimasOrdenes = Orden_Compra::whereMonth('fecha_entrega', now()->month)
            ->whereYear('fecha_entrega', now()->year)
            ->count();

        // Contar órdenes vencidas del mes actual
        $numOrdenesVencidas = Orden_Compra::where('fecha_entrega', '<', now())
            ->whereMonth('fecha_entrega', now()->month)
            ->whereYear('fecha_entrega', now()->year)
            ->count();

        // Contar órdenes de trabajo con faltantes
        $numOrdenesTrabajoConFaltantes = DB::table('orden_de_trabajos')
            ->where('faltantes', '>', 0)
            ->count();

        // Contar órdenes completadas
        $numOrdenesCompletadas = Orden_Compra::where('estado_id', 2) // Suponiendo que 3 significa "Completado"
            ->whereMonth('fecha_entrega', now()->month)
            ->whereYear('fecha_entrega', now()->year)
            ->count();
               // ✅ Agregar consulta para traer órdenes por mes
    $ordenesPorMes = Orden_Compra::select(
        DB::raw("DATE_FORMAT(fecha_entrega, '%Y-%m') as mes"),
        DB::raw("COUNT(id) as total_ordenes")
    )
        ->groupBy('mes')
        ->orderBy('mes', 'asc')
        ->get();

        return response()->json([
            'num_ultimas_ordenes' => $numUltimasOrdenes,
            'num_ordenes_vencidas' => $numOrdenesVencidas,
            'num_ordenes_trabajo_faltantes' => $numOrdenesTrabajoConFaltantes,
            'num_ordenes_completadas' => $numOrdenesCompletadas,
            'ordenes_por_mes' => $ordenesPorMes,
        ]);
    }
}
