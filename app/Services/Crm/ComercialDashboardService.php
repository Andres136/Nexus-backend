<?php

namespace App\Services\Crm;

use Illuminate\Support\Facades\DB;

class ComercialDashboardService
{
    public function getMesAMes(?int $userId, string $inicio): array
    {
        // 1️⃣ Gestiones
        $gestiones = DB::table('seguimiento_clientes')
            ->join('users', 'seguimiento_clientes.user_id', '=', 'users.id')
            ->select(
                'users.id as user_id',
                'users.name as usuario',
                DB::raw('DATE_FORMAT(seguimiento_clientes.created_at, "%Y-%m") as mes'),
                DB::raw('COUNT(*) as gestiones'),
                DB::raw('COUNT(DISTINCT seguimiento_clientes.cliente_id) as clientes_gestionados')
            )
            ->where('seguimiento_clientes.created_at', '>=', $inicio)
            ->when($userId, fn ($q) => $q->where('users.id', $userId))
            ->groupBy('users.id', 'users.name', 'mes')
            ->get();

        // 2️⃣ Cotizaciones
        $cotizaciones = DB::table('cotizaciones')
            ->join('users', 'cotizaciones.user_id', '=', 'users.id')
            ->select(
                'users.id as user_id',
                'users.name as usuario',
                DB::raw('DATE_FORMAT(cotizaciones.created_at, "%Y-%m") as mes'),
                DB::raw('COUNT(*) as cotizaciones')
            )
            ->where('cotizaciones.created_at', '>=', $inicio)
            ->when($userId, fn ($q) => $q->where('users.id', $userId))
            ->groupBy('users.id', 'users.name', 'mes')
            ->get();

        // 3️⃣ Órdenes
        $ordenes = DB::table('orden__compras')
            ->join('users', 'orden__compras.user_id', '=', 'users.id')
            ->select(
                'users.id as user_id',
                'users.name as usuario',
                DB::raw('DATE_FORMAT(orden__compras.created_at, "%Y-%m") as mes'),
                DB::raw('COUNT(*) as ordenes'),
                DB::raw('SUM(orden__compras.valor_total) as valor'),
                DB::raw('COUNT(DISTINCT orden__compras.cliente_id) as clientes_con_orden')
            )
            ->where('orden__compras.created_at', '>=', $inicio)
            ->when($userId, fn ($q) => $q->where('users.id', $userId))
            ->groupBy('users.id', 'users.name', 'mes')
            ->get();

        // 4️⃣ Fidelización
        $fielesMensual = DB::table('orden__compras as o')
            ->join('users', 'o.user_id', '=', 'users.id')
            ->joinSub(
                DB::table('orden__compras')
                    ->select('user_id', 'cliente_id')
                    ->where('created_at', '>=', $inicio)
                    ->when($userId, fn ($q) => $q->where('user_id', $userId))
                    ->groupBy('user_id', 'cliente_id')
                    ->havingRaw('COUNT(*) >= 2'),
                'fieles',
                fn ($join) => $join->on('o.user_id', '=', 'fieles.user_id')
                                   ->on('o.cliente_id', '=', 'fieles.cliente_id')
            )
            ->select(
                'users.id as user_id',
                DB::raw('DATE_FORMAT(o.created_at, "%Y-%m") as mes'),
                DB::raw('COUNT(DISTINCT o.cliente_id) as clientes_fieles')
            )
            ->where('o.created_at', '>=', $inicio)
            ->when($userId, fn ($q) => $q->where('o.user_id', $userId))
            ->groupBy('users.id', 'mes')
            ->get();

        // 5️⃣ Cartera: totales globales por usuario (no por mes de vencimiento)
        //    Se adjunta a todos los meses del usuario como dato de estado actual
   // 5️⃣ Cartera por usuario
// Denominador FIJO — vencidas por usuario hasta hoy
$carteraPorUsuario = DB::table('gestion_cartera as gc')
    ->join('users', 'gc.user_comercial_id', '=', 'users.id')
    ->select(
        'users.id as user_id',
        DB::raw('COUNT(DISTINCT gc.id) as cartera_vencidas')
    )
    ->where('gc.estado', '!=', 'cancelado')
    ->whereDate('gc.fecha_vencimiento', '<', now())
    ->when($userId, fn ($q) => $q->where('gc.user_comercial_id', $userId))
    ->groupBy('users.id')
    ->get()
    ->keyBy('user_id');

// Gestionadas POR MES por usuario — cuándo gestionó cada uno
$carteraGestionadasPorUsuarioMes = DB::table('gestion_cartera as gc')
    ->join('gestion_cartera_historial as gh', 'gc.id', '=', 'gh.gestion_cartera_id')
    ->join('users', 'gc.user_comercial_id', '=', 'users.id')
    ->selectRaw('users.id as user_id, DATE_FORMAT(gh.created_at, "%Y-%m") as mes, COUNT(DISTINCT gc.id) as gestionadas')
    ->where('gc.estado', '!=', 'cancelado')
    ->whereDate('gc.fecha_vencimiento', '<', now()) // solo sobre vencidas
    ->when($userId, fn ($q) => $q->where('gc.user_comercial_id', $userId))
    ->groupBy('users.id', 'mes')
    ->get()
    ->groupBy('user_id');

        // 6️⃣ Metas mensuales
        $metas = DB::table('meta_mensuals')
            ->select(
                DB::raw("CONCAT(anio, '-', LPAD(mes, 2, '0')) as periodo"),
                'valor_meta'
            )
            ->get()
            ->keyBy('periodo');

        // 7️⃣ Usuarios comerciales activos por mes (solo rol Comercial / Ejecutivo Comercial)
   // 7️⃣a - Comerciales con ventas ese mes (para dividir la meta)
// 7️⃣ - Contar TODOS los usuarios con ventas ese mes (sin importar rol)
$usuariosConVentasPorMes = DB::table('orden__compras')
    ->select(
        DB::raw('DATE_FORMAT(created_at, "%Y-%m") as mes'),
        DB::raw('COUNT(DISTINCT user_id) as total_usuarios')
    )
    ->where('created_at', '>=', $inicio)
    ->where('valor_total', '>', 0)
    ->groupBy('mes')
    ->get()
    ->keyBy('mes');

        // 8️⃣ Consolidar
        $resultado = [];

        foreach ([$gestiones, $cotizaciones, $ordenes, $fielesMensual] as $coleccion) {
            foreach ($coleccion as $r) {
                $key = $r->user_id . '_' . $r->mes;

                if (!isset($resultado[$key])) {
                    $resultado[$key] = [
                        'user_id'              => $r->user_id,
                        'usuario'              => $r->usuario ?? '',
                        'mes'                  => $r->mes,
                        'gestiones'            => 0,
                        'clientes_gestionados' => 0,
                        'cotizaciones'         => 0,
                        'ordenes'              => 0,
                        'valor_ventas'         => 0,
                        'clientes_con_orden'   => 0,
                        'clientes_fieles'      => 0,
                        'cartera_vencidas'     => 0,
                        'cartera_gestionadas'  => 0,
                        'conversion_pct'       => 0,
                        'fidelizacion_pct'     => 0,
                        'cartera_pct_gestion'  => 0,
                        'meta_individual'      => 0,
                        'cumplimiento_pct'     => 0,
                    ];
                }

                if (!empty($r->usuario)) {
                    $resultado[$key]['usuario'] = $r->usuario;
                }

                if (isset($r->gestiones))            $resultado[$key]['gestiones']              = (int)   $r->gestiones;
                if (isset($r->clientes_gestionados)) $resultado[$key]['clientes_gestionados']   = (int)   $r->clientes_gestionados;
                if (isset($r->cotizaciones))         $resultado[$key]['cotizaciones']           = (int)   $r->cotizaciones;
                if (isset($r->ordenes)) {
                    $resultado[$key]['ordenes']            = (int)   $r->ordenes;
                    $resultado[$key]['valor_ventas']       = (float) $r->valor;
                    $resultado[$key]['clientes_con_orden'] = (int)   $r->clientes_con_orden;
                }
                if (isset($r->clientes_fieles)) $resultado[$key]['clientes_fieles'] = (int) $r->clientes_fieles;
            }
        }

        // 9️⃣ Adjuntar cartera + meta + KPIs a cada entrada
foreach ($resultado as &$r) {
    // Cartera

        // Vencidas fijas del usuario
    $c = $carteraPorUsuario[$r['user_id']] ?? null;
    $vencidas = $c ? (int) $c->cartera_vencidas : 0;

    // Gestionadas de ese usuario en ese mes específico
    $gestionadasMes = collect($carteraGestionadasPorUsuarioMes[$r['user_id']] ?? [])
        ->firstWhere('mes', $r['mes']);
    $gestionadas = $gestionadasMes ? (int) $gestionadasMes->gestionadas : 0;
   $r['cartera_vencidas']    = $vencidas;
    $r['cartera_gestionadas'] = $gestionadas;
    $r['cartera_pct_gestion'] = $vencidas > 0
        ? round(($gestionadas / $vencidas) * 100, 2)
        : 0;


    // ✅ Meta dividida entre TODOS los que vendieron ese mes
    $metaMes        = (float) ($metas[$r['mes']]->valor_meta ?? 0);
    $totalUsuarios  = max(1, (int) ($usuariosConVentasPorMes[$r['mes']]->total_usuarios ?? 1));
    $metaIndividual = round($metaMes / $totalUsuarios, 2);
    $r['meta_mes']       = $metaMes;
    $r['meta_individual']  = $metaIndividual;
    $r['cumplimiento_pct'] = $metaIndividual > 0
        ? round(($r['valor_ventas'] / $metaIndividual) * 100, 2)
        : 0;

    // KPIs
    $r['conversion_pct']   = $r['cotizaciones'] > 0
        ? round(($r['ordenes'] / $r['cotizaciones']) * 100, 2)
        : 0;

    $r['fidelizacion_pct'] = $r['clientes_con_orden'] > 0
        ? round(($r['clientes_fieles'] / $r['clientes_con_orden']) * 100, 2)
        : 0;
}

        return collect($resultado)->sortBy('mes')->values()->all();
    }
}