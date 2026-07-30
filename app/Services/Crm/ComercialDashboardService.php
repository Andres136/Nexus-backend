<?php

namespace App\Services\Crm;

use App\EstadoEnum;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ComercialDashboardService
{
    public function getResumenMesActual(?int $userId = null): array
    {
        $mesActual = now()->format('Y-m');
        $metricas = collect($this->getMesAMes($userId, now()->startOfMonth()->toDateTimeString()))
            ->where('mes', $mesActual)
            ->keyBy('user_id');

        $usuarios = DB::table('users')
            ->join('clientes', 'clientes.user_id', '=', 'users.id')
            ->when($userId, fn ($query) => $query->where('users.id', $userId))
            ->select('users.id', 'users.name')
            ->distinct()
            ->orderBy('users.name')
            ->get();

        $clientesActivos = DB::table('clientes')
            ->where('estado_id', EstadoEnum::ACTIVO->value)
            ->when($userId, fn ($query) => $query->where('user_id', $userId))
            ->select('user_id', DB::raw('COUNT(*) as total'))
            ->groupBy('user_id')
            ->pluck('total', 'user_id');

        return $usuarios->map(function ($usuario) use ($metricas, $clientesActivos, $mesActual) {
            $metrica = $metricas->get($usuario->id, []);
            $asignados = (int) ($clientesActivos[$usuario->id] ?? 0);
            $gestionados = (int) ($metrica['clientes_gestionados'] ?? 0);

            return [
                'user_id' => $usuario->id,
                'usuario' => $usuario->name,
                'mes' => $mesActual,
                'clientes_activos' => $asignados,
                'gestiones' => (int) ($metrica['gestiones'] ?? 0),
                'clientes_gestionados' => $gestionados,
                'cobertura_clientes_pct' => $asignados > 0
                    ? round(($gestionados / $asignados) * 100, 2)
                    : 0,
                'cotizaciones' => (int) ($metrica['cotizaciones'] ?? 0),
                'ordenes' => (int) ($metrica['ordenes'] ?? 0),
                'valor_ventas' => (float) ($metrica['valor_ventas'] ?? 0),
                'conversion_pct' => (float) ($metrica['conversion_pct'] ?? 0),
                'cartera_vencidas' => (int) ($metrica['cartera_vencidas'] ?? 0),
                'cartera_gestionadas' => (int) ($metrica['cartera_gestionadas'] ?? 0),
                'cartera_pct_gestion' => (float) ($metrica['cartera_pct_gestion'] ?? 0),
                'clientes_con_orden' => (int) ($metrica['clientes_con_orden'] ?? 0),
                'clientes_fieles' => (int) ($metrica['clientes_fieles'] ?? 0),
                'fidelizacion_pct' => (float) ($metrica['fidelizacion_pct'] ?? 0),
                'meta_individual' => (float) ($metrica['meta_individual'] ?? 0),
                'cumplimiento_pct' => (float) ($metrica['cumplimiento_pct'] ?? 0),
            ];
        })->values()->all();
    }

    public function getMesAMes(?int $userId, string $inicio): array
    {
        $inactivo = EstadoEnum::INACTIVO->value;

        // 1️⃣ Gestiones — solo sobre clientes activos
        $gestiones = DB::table('seguimiento_clientes')
            ->join('users', 'seguimiento_clientes.user_id', '=', 'users.id')
            ->join('clientes', function ($join) use ($inactivo) {
                $join->on('seguimiento_clientes.cliente_id', '=', 'clientes.id')
                     ->where('clientes.estado_id', '!=', $inactivo);
            })
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

        // 3️⃣ Órdenes — clientes_con_orden solo activos
        $ordenes = DB::table('orden__compras')
            ->join('users', 'orden__compras.user_id', '=', 'users.id')
            ->join('clientes', function ($join) use ($inactivo) {
                $join->on('orden__compras.cliente_id', '=', 'clientes.id')
                     ->where('clientes.estado_id', '!=', $inactivo);
            })
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

        // 4️⃣ Fidelización — solo clientes activos con 2+ órdenes
        $fielesMensual = DB::table('orden__compras as o')
            ->join('users', 'o.user_id', '=', 'users.id')
            ->joinSub(
                DB::table('orden__compras')
                    ->join('clientes', function ($join) use ($inactivo) {
                        $join->on('orden__compras.cliente_id', '=', 'clientes.id')
                             ->where('clientes.estado_id', '!=', $inactivo);
                    })
                    ->select('orden__compras.user_id', 'orden__compras.cliente_id')
                    ->where('orden__compras.created_at', '>=', $inicio)
                    ->when($userId, fn ($q) => $q->where('orden__compras.user_id', $userId))
                    ->groupBy('orden__compras.user_id', 'orden__compras.cliente_id')
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

        // 5️⃣ Cartera: vencidas por usuario
        $carteraPorUsuario = DB::table('gestion_cartera as gc')
            ->join('users', 'gc.user_comercial_id', '=', 'users.id')
            ->select(
                'users.id as user_id',
                DB::raw('COUNT(DISTINCT gc.id) as cartera_vencidas')
            )
            ->where('gc.estado', 'pendiente')
            ->whereDate('gc.fecha_vencimiento', '<', now())
            ->when($userId, fn ($q) => $q->where('gc.user_comercial_id', $userId))
            ->groupBy('users.id')
            ->get()
            ->keyBy('user_id');

        // Gestionadas por mes
        $carteraGestionadasPorUsuarioMes = DB::table('gestion_cartera as gc')
            ->join('gestion_cartera_historial as gh', 'gc.id', '=', 'gh.gestion_cartera_id')
            ->join('users', 'gc.user_comercial_id', '=', 'users.id')
            ->selectRaw('users.id as user_id, DATE_FORMAT(gh.created_at, "%Y-%m") as mes, COUNT(DISTINCT gc.id) as gestionadas')
            ->where('gc.estado', 'pendiente')
            ->whereDate('gc.fecha_vencimiento', '<', now())
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

        // 7️⃣ Usuarios con ventas por mes
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
                        'user_id'                   => $r->user_id,
                        'usuario'                   => $r->usuario ?? '',
                        'mes'                       => $r->mes,
                        'gestiones'                 => 0,
                        'clientes_gestionados'      => 0,
                        'cotizaciones'              => 0,
                        'ordenes'                   => 0,
                        'valor_ventas'              => 0,
                        'clientes_con_orden'        => 0,
                        'clientes_fieles'           => 0,
                        'cartera_vencidas'          => 0,
                        'cartera_gestionadas'       => 0,
                        'conversion_pct'            => 0,
                        'conversion_trimestral_pct' => 0,
                        'trimestre'                 => '',
                        'fidelizacion_pct'          => 0,
                        'cartera_pct_gestion'       => 0,
                        'meta_individual'           => 0,
                        'cumplimiento_pct'          => 0,
                    ];
                }

                if (!empty($r->usuario)) {
                    $resultado[$key]['usuario'] = $r->usuario;
                }

                if (isset($r->gestiones))            $resultado[$key]['gestiones']            = (int)   $r->gestiones;
                if (isset($r->clientes_gestionados)) $resultado[$key]['clientes_gestionados'] = (int)   $r->clientes_gestionados;
                if (isset($r->cotizaciones))         $resultado[$key]['cotizaciones']         = (int)   $r->cotizaciones;
                if (isset($r->ordenes)) {
                    $resultado[$key]['ordenes']            = (int)   $r->ordenes;
                    $resultado[$key]['valor_ventas']       = (float) $r->valor;
                    $resultado[$key]['clientes_con_orden'] = (int)   $r->clientes_con_orden;
                }
                if (isset($r->clientes_fieles)) $resultado[$key]['clientes_fieles'] = (int) $r->clientes_fieles;
            }
        }

        // 9️⃣ Cartera + meta + KPIs mensuales
        foreach ($resultado as &$r) {
            $c        = $carteraPorUsuario[$r['user_id']] ?? null;
            $vencidas = $c ? (int) $c->cartera_vencidas : 0;

            $gestionadasMes = collect($carteraGestionadasPorUsuarioMes[$r['user_id']] ?? [])
                ->firstWhere('mes', $r['mes']);
            $gestionadas = $gestionadasMes ? (int) $gestionadasMes->gestionadas : 0;

            $r['cartera_vencidas']    = $vencidas;
            $r['cartera_gestionadas'] = $gestionadas;
            $r['cartera_pct_gestion'] = $vencidas > 0
                ? round(($gestionadas / $vencidas) * 100, 2)
                : 0;

            $metaMes        = (float) ($metas[$r['mes']]->valor_meta ?? 0);
            $totalUsuarios  = max(1, (int) ($usuariosConVentasPorMes[$r['mes']]->total_usuarios ?? 1));
            $metaIndividual = round($metaMes / $totalUsuarios, 2);
            $r['meta_mes']         = $metaMes;
            $r['meta_individual']  = $metaIndividual;
            $r['cumplimiento_pct'] = $metaIndividual > 0
                ? round(($r['valor_ventas'] / $metaIndividual) * 100, 2)
                : 0;

            $r['conversion_pct'] = $r['cotizaciones'] > 0
                ? round(($r['ordenes'] / $r['cotizaciones']) * 100, 2)
                : 0;

            $r['fidelizacion_pct'] = $r['clientes_con_orden'] > 0
                ? round(($r['clientes_fieles'] / $r['clientes_con_orden']) * 100, 2)
                : 0;

            // Calcular trimestre natural (Q1–Q4)
            [$anio, $mes] = explode('-', $r['mes']);
            $q = (int) ceil((int) $mes / 3);
            $r['trimestre'] = "{$anio}-Q{$q}";
        }
        unset($r);

        // 🔟 Conversión trimestral: acumular cotizaciones y órdenes por usuario+trimestre
   $agrupadoTrimestre = [];

foreach ($resultado as $r) {

    $key = $r['user_id'] . '_' . $r['trimestre'];

    if (!isset($agrupadoTrimestre[$key])) {
        $agrupadoTrimestre[$key] = [
            'cotizaciones' => 0,
            'ordenes' => 0,
        ];
    }

    $agrupadoTrimestre[$key]['cotizaciones'] += (int) $r['cotizaciones'];
    $agrupadoTrimestre[$key]['ordenes'] += (int) $r['ordenes'];
}

       foreach ($resultado as &$r) {

    $key = $r['user_id'] . '_' . $r['trimestre'];

    $agg = $agrupadoTrimestre[$key];

    // NUEVO → para debug/frontend
    $r['cotizaciones_trimestre'] = $agg['cotizaciones'];
    $r['ordenes_trimestre']      = $agg['ordenes'];

    $r['conversion_trimestral_pct'] =
        $agg['cotizaciones'] > 0
            ? round(
                ($agg['ordenes'] / $agg['cotizaciones']) * 100,
                2
            )
            : 0;
}
unset($r);

        return collect($resultado)->sortBy('mes')->values()->all();
    }

    // Desglose del mes elegido en semanas (1..5, semana de calendario dentro
    // del mes, no semana ISO del año — evita que una semana cruce el límite
    // de mes, que sería confuso para "las semanas de junio"). Agregado sobre
    // todos los vendedores (o uno solo si se pasa $userId), sin cartera ni
    // metas: esas métricas no tienen una lectura semanal con sentido.
    public function getSemanasDelMes(?int $userId, string $mes): array
    {
        $inactivo = EstadoEnum::INACTIVO->value;
        $inicio = Carbon::createFromFormat('Y-m-d', $mes.'-01')->startOfMonth();
        $fin = $inicio->copy()->endOfMonth();
        $totalSemanas = (int) ceil($inicio->daysInMonth / 7);

        // Mismos filtros que getMesAMes: solo clientes activos cuentan para
        // gestiones y órdenes, para que la suma de las semanas cuadre con el
        // total mensual que ya se muestra en el dashboard.
        $gestiones = DB::table('seguimiento_clientes')
            ->join('clientes', function ($join) use ($inactivo) {
                $join->on('seguimiento_clientes.cliente_id', '=', 'clientes.id')
                     ->where('clientes.estado_id', '!=', $inactivo);
            })
            ->select(
                DB::raw('CEIL(DAY(seguimiento_clientes.created_at) / 7) as semana_mes'),
                DB::raw('COUNT(*) as total')
            )
            ->whereBetween('seguimiento_clientes.created_at', [$inicio, $fin])
            ->when($userId, fn ($q) => $q->where('seguimiento_clientes.user_id', $userId))
            ->groupBy('semana_mes')
            ->pluck('total', 'semana_mes');

        $cotizaciones = DB::table('cotizaciones')
            ->select(
                DB::raw('CEIL(DAY(created_at) / 7) as semana_mes'),
                DB::raw('COUNT(*) as total')
            )
            ->whereBetween('created_at', [$inicio, $fin])
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->groupBy('semana_mes')
            ->pluck('total', 'semana_mes');

        $ordenes = DB::table('orden__compras')
            ->join('clientes', function ($join) use ($inactivo) {
                $join->on('orden__compras.cliente_id', '=', 'clientes.id')
                     ->where('clientes.estado_id', '!=', $inactivo);
            })
            ->select(
                DB::raw('CEIL(DAY(orden__compras.created_at) / 7) as semana_mes'),
                DB::raw('COUNT(*) as total'),
                DB::raw('COALESCE(SUM(valor_total),0) as ventas'),
                DB::raw('COUNT(DISTINCT orden__compras.cliente_id) as clientes_con_orden')
            )
            ->whereBetween('orden__compras.created_at', [$inicio, $fin])
            ->when($userId, fn ($q) => $q->where('orden__compras.user_id', $userId))
            ->groupBy('semana_mes')
            ->get()
            ->keyBy('semana_mes');

        $semanas = collect(range(1, $totalSemanas))->map(function ($semana) use ($gestiones, $cotizaciones, $ordenes) {
            $orden = $ordenes->get($semana);

            return [
                'semana' => $semana,
                'label' => "Sem {$semana}",
                'gestiones' => (int) ($gestiones[$semana] ?? 0),
                'cotizaciones' => (int) ($cotizaciones[$semana] ?? 0),
                'ordenes' => (int) ($orden->total ?? 0),
                'valor_ventas' => (float) ($orden->ventas ?? 0),
                'clientes_con_orden' => (int) ($orden->clientes_con_orden ?? 0),
            ];
        })->values();

        return [
            'mes' => $mes,
            'semanas' => $semanas,
        ];
    }

    public function getTrimestral(?int $userId, string $inicio): array
    {
        $inactivo = EstadoEnum::INACTIVO->value;
        $periodoExpr = DB::raw('CONCAT(YEAR(created_at), "-Q", QUARTER(created_at)) as trimestre');

        // 1️⃣ Gestiones
        $gestiones = DB::table('seguimiento_clientes')
            ->join('users', 'seguimiento_clientes.user_id', '=', 'users.id')
            ->join('clientes', function ($join) use ($inactivo) {
                $join->on('seguimiento_clientes.cliente_id', '=', 'clientes.id')
                     ->where('clientes.estado_id', '!=', $inactivo);
            })
            ->select(
                'users.id as user_id',
                'users.name as usuario',
                DB::raw('CONCAT(YEAR(seguimiento_clientes.created_at), "-Q", QUARTER(seguimiento_clientes.created_at)) as trimestre'),
                DB::raw('COUNT(*) as gestiones'),
                DB::raw('COUNT(DISTINCT seguimiento_clientes.cliente_id) as clientes_gestionados')
            )
            ->where('seguimiento_clientes.created_at', '>=', $inicio)
            ->when($userId, fn ($q) => $q->where('users.id', $userId))
            ->groupBy('users.id', 'users.name', 'trimestre')
            ->get();

        // 2️⃣ Cotizaciones
        $cotizaciones = DB::table('cotizaciones')
            ->join('users', 'cotizaciones.user_id', '=', 'users.id')
            ->select(
                'users.id as user_id',
                'users.name as usuario',
                DB::raw('CONCAT(YEAR(cotizaciones.created_at), "-Q", QUARTER(cotizaciones.created_at)) as trimestre'),
                DB::raw('COUNT(*) as cotizaciones')
            )
            ->where('cotizaciones.created_at', '>=', $inicio)
            ->when($userId, fn ($q) => $q->where('users.id', $userId))
            ->groupBy('users.id', 'users.name', 'trimestre')
            ->get();

        // 3️⃣ Órdenes
        $ordenes = DB::table('orden__compras')
            ->join('users', 'orden__compras.user_id', '=', 'users.id')
            ->join('clientes', function ($join) use ($inactivo) {
                $join->on('orden__compras.cliente_id', '=', 'clientes.id')
                     ->where('clientes.estado_id', '!=', $inactivo);
            })
            ->select(
                'users.id as user_id',
                'users.name as usuario',
                DB::raw('CONCAT(YEAR(orden__compras.created_at), "-Q", QUARTER(orden__compras.created_at)) as trimestre'),
                DB::raw('COUNT(*) as ordenes'),
                DB::raw('SUM(orden__compras.valor_total) as valor'),
                DB::raw('COUNT(DISTINCT orden__compras.cliente_id) as clientes_con_orden')
            )
            ->where('orden__compras.created_at', '>=', $inicio)
            ->when($userId, fn ($q) => $q->where('users.id', $userId))
            ->groupBy('users.id', 'users.name', 'trimestre')
            ->get();

        // 4️⃣ Fidelización — clientes activos con 2+ órdenes en el trimestre
        $fielesTrimestral = DB::table('orden__compras as o')
            ->join('users', 'o.user_id', '=', 'users.id')
            ->joinSub(
                DB::table('orden__compras')
                    ->join('clientes', function ($join) use ($inactivo) {
                        $join->on('orden__compras.cliente_id', '=', 'clientes.id')
                             ->where('clientes.estado_id', '!=', $inactivo);
                    })
                    ->select(
                        'orden__compras.user_id',
                        'orden__compras.cliente_id',
                        DB::raw('CONCAT(YEAR(orden__compras.created_at), "-Q", QUARTER(orden__compras.created_at)) as trimestre')
                    )
                    ->where('orden__compras.created_at', '>=', $inicio)
                    ->when($userId, fn ($q) => $q->where('orden__compras.user_id', $userId))
                    ->groupBy('orden__compras.user_id', 'orden__compras.cliente_id', 'trimestre')
                    ->havingRaw('COUNT(*) >= 2'),
                'fieles',
                fn ($join) => $join->on('o.user_id', '=', 'fieles.user_id')
                                   ->on('o.cliente_id', '=', 'fieles.cliente_id')
                                   ->on(DB::raw('CONCAT(YEAR(o.created_at), "-Q", QUARTER(o.created_at))'), '=', 'fieles.trimestre')
            )
            ->select(
                'users.id as user_id',
                DB::raw('CONCAT(YEAR(o.created_at), "-Q", QUARTER(o.created_at)) as trimestre'),
                DB::raw('COUNT(DISTINCT o.cliente_id) as clientes_fieles')
            )
            ->where('o.created_at', '>=', $inicio)
            ->when($userId, fn ($q) => $q->where('o.user_id', $userId))
            ->groupBy('users.id', 'trimestre')
            ->get();

        // 5️⃣ Cartera vencidas por usuario
        $carteraPorUsuario = DB::table('gestion_cartera as gc')
            ->join('users', 'gc.user_comercial_id', '=', 'users.id')
            ->select('users.id as user_id', DB::raw('COUNT(DISTINCT gc.id) as cartera_vencidas'))
            ->where('gc.estado', 'pendiente')
            ->whereDate('gc.fecha_vencimiento', '<', now())
            ->when($userId, fn ($q) => $q->where('gc.user_comercial_id', $userId))
            ->groupBy('users.id')
            ->get()
            ->keyBy('user_id');

        // Gestionadas por trimestre
        $carteraGestionadasPorUsuarioTrimestre = DB::table('gestion_cartera as gc')
            ->join('gestion_cartera_historial as gh', 'gc.id', '=', 'gh.gestion_cartera_id')
            ->join('users', 'gc.user_comercial_id', '=', 'users.id')
            ->selectRaw('users.id as user_id, CONCAT(YEAR(gh.created_at), "-Q", QUARTER(gh.created_at)) as trimestre, COUNT(DISTINCT gc.id) as gestionadas')
            ->where('gc.estado', 'pendiente')
            ->whereDate('gc.fecha_vencimiento', '<', now())
            ->when($userId, fn ($q) => $q->where('gc.user_comercial_id', $userId))
            ->groupBy('users.id', 'trimestre')
            ->get()
            ->groupBy('user_id');

        // 6️⃣ Metas: sumar los meses de cada trimestre
        $metasPorTrimestre = DB::table('meta_mensuals')
            ->select(
                DB::raw("CONCAT(anio, '-Q', CEIL(mes / 3)) as trimestre"),
                DB::raw('SUM(valor_meta) as valor_meta')
            )
            ->groupBy('trimestre')
            ->get()
            ->keyBy('trimestre');

        // 7️⃣ Usuarios con ventas por trimestre
        $usuariosConVentasPorTrimestre = DB::table('orden__compras')
            ->select(
                DB::raw('CONCAT(YEAR(created_at), "-Q", QUARTER(created_at)) as trimestre'),
                DB::raw('COUNT(DISTINCT user_id) as total_usuarios')
            )
            ->where('created_at', '>=', $inicio)
            ->where('valor_total', '>', 0)
            ->groupBy('trimestre')
            ->get()
            ->keyBy('trimestre');

        // 8️⃣ Consolidar
        $resultado = [];

        foreach ([$gestiones, $cotizaciones, $ordenes, $fielesTrimestral] as $coleccion) {
            foreach ($coleccion as $r) {
                $key = $r->user_id . '_' . $r->trimestre;

                if (!isset($resultado[$key])) {
                    $resultado[$key] = [
                        'user_id'              => $r->user_id,
                        'usuario'              => $r->usuario ?? '',
                        'trimestre'            => $r->trimestre,
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
                        'meta_trimestre'       => 0,
                        'meta_individual'      => 0,
                        'cumplimiento_pct'     => 0,
                    ];
                }

                if (!empty($r->usuario)) {
                    $resultado[$key]['usuario'] = $r->usuario;
                }

                if (isset($r->gestiones))            $resultado[$key]['gestiones']            = (int)   $r->gestiones;
                if (isset($r->clientes_gestionados)) $resultado[$key]['clientes_gestionados'] = (int)   $r->clientes_gestionados;
                if (isset($r->cotizaciones))         $resultado[$key]['cotizaciones']         = (int)   $r->cotizaciones;
                if (isset($r->ordenes)) {
                    $resultado[$key]['ordenes']            = (int)   $r->ordenes;
                    $resultado[$key]['valor_ventas']       = (float) $r->valor;
                    $resultado[$key]['clientes_con_orden'] = (int)   $r->clientes_con_orden;
                }
                if (isset($r->clientes_fieles)) $resultado[$key]['clientes_fieles'] = (int) $r->clientes_fieles;
            }
        }

        // 9️⃣ Cartera + meta + KPIs trimestrales
        foreach ($resultado as &$r) {
            $c        = $carteraPorUsuario[$r['user_id']] ?? null;
            $vencidas = $c ? (int) $c->cartera_vencidas : 0;

            $gestionadasTrim = collect($carteraGestionadasPorUsuarioTrimestre[$r['user_id']] ?? [])
                ->firstWhere('trimestre', $r['trimestre']);
            $gestionadas = $gestionadasTrim ? (int) $gestionadasTrim->gestionadas : 0;

            $r['cartera_vencidas']    = $vencidas;
            $r['cartera_gestionadas'] = $gestionadas;
            $r['cartera_pct_gestion'] = $vencidas > 0
                ? round(($gestionadas / $vencidas) * 100, 2)
                : 0;

            $metaTrimestre  = (float) ($metasPorTrimestre[$r['trimestre']]->valor_meta ?? 0);
            $totalUsuarios  = max(1, (int) ($usuariosConVentasPorTrimestre[$r['trimestre']]->total_usuarios ?? 1));
            $metaIndividual = round($metaTrimestre / $totalUsuarios, 2);
            $r['meta_trimestre']   = $metaTrimestre;
            $r['meta_individual']  = $metaIndividual;
            $r['cumplimiento_pct'] = $metaIndividual > 0
                ? round(($r['valor_ventas'] / $metaIndividual) * 100, 2)
                : 0;

            $r['conversion_pct'] = $r['cotizaciones'] > 0
                ? round(($r['ordenes'] / $r['cotizaciones']) * 100, 2)
                : 0;

            $r['fidelizacion_pct'] = $r['clientes_con_orden'] > 0
                ? round(($r['clientes_fieles'] / $r['clientes_con_orden']) * 100, 2)
                : 0;
        }
        unset($r);

        return collect($resultado)->sortBy('trimestre')->values()->all();
    }
}
