<?php

namespace App\Services\Crm;

use App\EstadoEnum;
use App\Models\Crm\CarteraGestionMensualUsuario;
use App\RolEnum;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ComercialDashboardService
{
    // Roles con meta individual real (excluye GERENTE_COMERCIAL, que tiene
    // vista global y no una cuota propia — mismo criterio que
    // SeguimientoController/ClienteService). Solo estos roles cuentan en el
    // divisor de la meta ("toca a X millones" se reparte solo entre ellos).
    private const ROLES_COMERCIALES = [
        RolEnum::COMERCIAL->value,
        RolEnum::EJECUTIVO_COMERCIAL->value,
    ];

    // Usuarios de otros roles (admin, gerente, etc.) sí pueden tener
    // órdenes/gestiones a su nombre y esa actividad no debe desaparecer del
    // dashboard. En vez de excluirlos, sus filas se consolidan en un único
    // pseudo-usuario "Otros" (id 0) que se trata como un vendedor más: entra
    // al reparto de la meta individual igual que cualquier ejecutivo (si
    // "Otros" tuvo ventas ese mes, la meta se divide entre N ejecutivos + 1).
    private const OTROS_USER_ID = 0;
    private const OTROS_USUARIO = 'Otros';

    // CASE SQL (sin alias) para agrupar cualquier usuario fuera de
    // ROLES_COMERCIALES en el pseudo-usuario "Otros", reutilizable tanto en
    // SELECT como dentro de COUNT(DISTINCT ...). Los ids de ROLES_COMERCIALES
    // son enteros fijos del enum (no input de usuario), seguro de interpolar
    // directo en el SQL.
    private function caseUserIdAgrupado(): string
    {
        $roles = implode(',', self::ROLES_COMERCIALES);
        return "CASE WHEN users.role_id IN ({$roles}) THEN users.id ELSE " . self::OTROS_USER_ID . ' END';
    }

    private function selectUserIdAgrupado(): \Illuminate\Database\Query\Expression
    {
        return DB::raw($this->caseUserIdAgrupado() . ' as user_id');
    }

    private function selectUsuarioAgrupado(): \Illuminate\Database\Query\Expression
    {
        $roles = implode(',', self::ROLES_COMERCIALES);
        return DB::raw("CASE WHEN users.role_id IN ({$roles}) THEN users.name ELSE '" . self::OTROS_USUARIO . "' END as usuario");
    }

    public function getResumenMesActual(?int $userId = null): array
    {
        $inactivo = EstadoEnum::INACTIVO->value;
        $mesActual = now()->format('Y-m');
        $metricas = collect($this->getMesAMes($userId, now()->startOfMonth()->toDateTimeString()))
            ->where('mes', $mesActual)
            ->keyBy('user_id');

        $usuarios = DB::table('users')
            ->join('clientes', 'clientes.user_id', '=', 'users.id')
            ->where('users.estado_id', '!=', $inactivo)
            ->whereIn('users.role_id', self::ROLES_COMERCIALES)
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

        // 1️⃣ Gestiones — solo sobre clientes activos y usuarios activos
        $gestiones = DB::table('seguimiento_clientes')
            ->join('users', 'seguimiento_clientes.user_id', '=', 'users.id')
            ->join('clientes', function ($join) use ($inactivo) {
                $join->on('seguimiento_clientes.cliente_id', '=', 'clientes.id')
                     ->where('clientes.estado_id', '!=', $inactivo);
            })
            ->where('users.estado_id', '!=', $inactivo)
            ->select(
                $this->selectUserIdAgrupado(),
                $this->selectUsuarioAgrupado(),
                DB::raw('DATE_FORMAT(seguimiento_clientes.created_at, "%Y-%m") as mes'),
                DB::raw('COUNT(*) as gestiones'),
                DB::raw('COUNT(DISTINCT seguimiento_clientes.cliente_id) as clientes_gestionados')
            )
            ->where('seguimiento_clientes.created_at', '>=', $inicio)
            ->when($userId, fn ($q) => $q->where('users.id', $userId))
            ->groupBy('user_id', 'usuario', 'mes')
            ->get();

        // 2️⃣ Cotizaciones — solo usuarios activos
        $cotizaciones = DB::table('cotizaciones')
            ->join('users', 'cotizaciones.user_id', '=', 'users.id')
            ->where('users.estado_id', '!=', $inactivo)
            ->select(
                $this->selectUserIdAgrupado(),
                $this->selectUsuarioAgrupado(),
                DB::raw('DATE_FORMAT(cotizaciones.created_at, "%Y-%m") as mes'),
                DB::raw('COUNT(*) as cotizaciones')
            )
            ->where('cotizaciones.created_at', '>=', $inicio)
            ->when($userId, fn ($q) => $q->where('users.id', $userId))
            ->groupBy('user_id', 'usuario', 'mes')
            ->get();

        // 3️⃣ Órdenes — clientes_con_orden solo activos, y solo usuarios activos
        $ordenes = DB::table('orden__compras')
            ->join('users', 'orden__compras.user_id', '=', 'users.id')
            ->join('clientes', function ($join) use ($inactivo) {
                $join->on('orden__compras.cliente_id', '=', 'clientes.id')
                     ->where('clientes.estado_id', '!=', $inactivo);
            })
            ->where('users.estado_id', '!=', $inactivo)
            ->select(
                $this->selectUserIdAgrupado(),
                $this->selectUsuarioAgrupado(),
                DB::raw('DATE_FORMAT(orden__compras.created_at, "%Y-%m") as mes'),
                DB::raw('COUNT(*) as ordenes'),
                DB::raw('SUM(orden__compras.valor_total) as valor'),
                DB::raw('COUNT(DISTINCT orden__compras.cliente_id) as clientes_con_orden')
            )
            ->where('orden__compras.created_at', '>=', $inicio)
            ->when($userId, fn ($q) => $q->where('users.id', $userId))
            ->groupBy('user_id', 'usuario', 'mes')
            ->get();

        // 4️⃣ Fidelización — solo clientes activos con 2+ órdenes, y solo usuarios activos
        $fielesMensual = DB::table('orden__compras as o')
            ->join('users', 'o.user_id', '=', 'users.id')
            ->where('users.estado_id', '!=', $inactivo)
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
                $this->selectUserIdAgrupado(),
                DB::raw('DATE_FORMAT(o.created_at, "%Y-%m") as mes'),
                DB::raw('COUNT(DISTINCT o.cliente_id) as clientes_fieles')
            )
            ->where('o.created_at', '>=', $inicio)
            ->when($userId, fn ($q) => $q->where('o.user_id', $userId))
            ->groupBy('user_id', 'mes')
            ->get();

        // 4️⃣.5 Nuevos registros de clientes por vendedor/mes: prospectos
        // (nunca han tenido una orden de compra) vs clientes reales (ya
        // tienen al menos 1 orden alguna vez, evaluado al momento de la
        // consulta, no al mes de registro).
        $clientesNuevos = DB::table('clientes')
            ->join('users', 'clientes.user_id', '=', 'users.id')
            ->leftJoin('orden__compras', 'orden__compras.cliente_id', '=', 'clientes.id')
            ->where('users.estado_id', '!=', $inactivo)
            ->where('clientes.estado_id', '!=', $inactivo)
            ->select(
                $this->selectUserIdAgrupado(),
                $this->selectUsuarioAgrupado(),
                DB::raw('DATE_FORMAT(clientes.created_at, "%Y-%m") as mes'),
                DB::raw('COUNT(DISTINCT clientes.id) as clientes_nuevos'),
                DB::raw('COUNT(DISTINCT CASE WHEN orden__compras.id IS NULL THEN clientes.id END) as prospectos_nuevos')
            )
            ->where('clientes.created_at', '>=', $inicio)
            ->when($userId, fn ($q) => $q->where('users.id', $userId))
            ->groupBy('user_id', 'usuario', 'mes')
            ->get();

        // 5️⃣ Cartera: vencidas por usuario (solo usuarios activos)
        $carteraPorUsuario = DB::table('gestion_cartera as gc')
            ->join('users', 'gc.user_comercial_id', '=', 'users.id')
            ->where('users.estado_id', '!=', $inactivo)
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

        // Gestionadas por mes (solo usuarios activos)
        $carteraGestionadasPorUsuarioMes = DB::table('gestion_cartera as gc')
            ->join('gestion_cartera_historial as gh', 'gc.id', '=', 'gh.gestion_cartera_id')
            ->join('users', 'gc.user_comercial_id', '=', 'users.id')
            ->where('users.estado_id', '!=', $inactivo)
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

        // 5️⃣.5 Clientes activos asignados por vendedor (constante en el mes,
        // no varía semana/mes a mes — es el "tamaño de cartera" contra el que
        // se mide cuánto gestionó: gestion_comercial_pct = gestionados / esto).
        $clientesActivosPorUsuario = DB::table('clientes')
            ->join('users', 'clientes.user_id', '=', 'users.id')
            ->where('users.estado_id', '!=', $inactivo)
            ->where('clientes.estado_id', '!=', $inactivo)
            ->select(
                $this->selectUserIdAgrupado(),
                DB::raw('COUNT(*) as total')
            )
            ->when($userId, fn ($q) => $q->where('users.id', $userId))
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        // 7️⃣ Usuarios activos con ventas por mes (divisor de la meta individual).
        // COUNT(DISTINCT CASE...) cuenta cada ejecutivo real por su id, y
        // colapsa a todos los usuarios de otros roles en un solo valor (0):
        // si "Otros" vendió ese mes, cuenta como una unidad más del reparto,
        // igual que si fuera un ejecutivo adicional.
        $usuariosConVentasPorMes = DB::table('orden__compras')
            ->join('users', 'orden__compras.user_id', '=', 'users.id')
            ->where('users.estado_id', '!=', $inactivo)
            ->select(
                DB::raw('DATE_FORMAT(orden__compras.created_at, "%Y-%m") as mes'),
                DB::raw('COUNT(DISTINCT ' . $this->caseUserIdAgrupado() . ') as total_usuarios')
            )
            ->where('orden__compras.created_at', '>=', $inicio)
            ->where('orden__compras.valor_total', '>', 0)
            ->groupBy('mes')
            ->get()
            ->keyBy('mes');

        // 8️⃣ Consolidar
        $resultado = [];

        foreach ([$gestiones, $cotizaciones, $ordenes, $fielesMensual, $clientesNuevos] as $coleccion) {
            foreach ($coleccion as $r) {
                $key = $r->user_id . '_' . $r->mes;

                if (!isset($resultado[$key])) {
                    $resultado[$key] = [
                        'user_id'                   => (int) $r->user_id,
                        'usuario'                   => $r->usuario ?? '',
                        'mes'                       => $r->mes,
                        'gestiones'                 => 0,
                        'clientes_gestionados'      => 0,
                        'cotizaciones'              => 0,
                        'ordenes'                   => 0,
                        'valor_ventas'              => 0,
                        'clientes_con_orden'        => 0,
                        'clientes_fieles'           => 0,
                        'clientes_nuevos'           => 0,
                        'prospectos_nuevos'         => 0,
                        'cartera_vencidas'          => 0,
                        'cartera_gestionadas'       => 0,
                        'conversion_pct'            => 0,
                        'conversion_trimestral_pct' => 0,
                        'trimestre'                 => '',
                        'fidelizacion_pct'          => 0,
                        'cartera_pct_gestion'       => 0,
                        'gestion_comercial_pct'     => 0,
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
                if (isset($r->clientes_nuevos)) {
                    $resultado[$key]['clientes_nuevos']   = (int) $r->clientes_nuevos;
                    $resultado[$key]['prospectos_nuevos'] = (int) $r->prospectos_nuevos;
                }
            }
        }

        // 9️⃣ Cartera + meta + KPIs mensuales
        //
        // La cartera vencida/gestionada se congela por usuario+mes en
        // cartera_gestion_mensual_usuario (ver guardarSnapshotCarteraMensualPorUsuario,
        // corrido a diario). Sin el snapshot, "vencida" se recalcula contra
        // now() y, como las deudas van pasando de 'pendiente' a 'completado' al
        // pagarse, un mes ya cerrado iría bajando su propio dato retroactivamente
        // cada vez que alguien pagara — el snapshot es la foto real de ese mes.
        // Solo el mes en curso puede caer al cálculo en vivo, como respaldo si el
        // comando diario aún no ha corrido hoy; un mes pasado sin snapshot (p.ej.
        // meses anteriores a este despliegue) queda en 0 en vez de recalcularse.
        $carteraSnapshotsPorUsuarioMes = CarteraGestionMensualUsuario::get()
            ->keyBy(fn ($s) => $s->user_id . '_' . $s->anio . '-' . str_pad($s->mes, 2, '0', STR_PAD_LEFT));
        $mesActualStr = now()->format('Y-m');

        foreach ($resultado as &$r) {
            $snapshot = $carteraSnapshotsPorUsuarioMes->get($r['user_id'] . '_' . $r['mes']);

            if ($snapshot) {
                $vencidas    = (int) $snapshot->cartera_vencidas;
                $gestionadas = (int) $snapshot->cartera_gestionadas;
                $pctCartera  = (float) $snapshot->cartera_pct_gestion;
            } elseif ($r['mes'] === $mesActualStr) {
                $c        = $carteraPorUsuario[$r['user_id']] ?? null;
                $vencidas = $c ? (int) $c->cartera_vencidas : 0;

                $gestionadasMes = collect($carteraGestionadasPorUsuarioMes[$r['user_id']] ?? [])
                    ->firstWhere('mes', $r['mes']);
                $gestionadas = $gestionadasMes ? (int) $gestionadasMes->gestionadas : 0;
                $pctCartera  = $vencidas > 0 ? round(($gestionadas / $vencidas) * 100, 2) : 0;
            } else {
                $vencidas    = 0;
                $gestionadas = 0;
                $pctCartera  = 0;
            }

            $r['cartera_vencidas']    = $vencidas;
            $r['cartera_gestionadas'] = $gestionadas;
            $r['cartera_pct_gestion'] = $pctCartera;

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

            $clientesActivos = (int) ($clientesActivosPorUsuario[$r['user_id']]->total ?? 0);
            $r['gestion_comercial_pct'] = $clientesActivos > 0
                ? round(($r['clientes_gestionados'] / $clientesActivos) * 100, 2)
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

    /**
     * Congela el % de gestión sobre cartera vencida del mes en curso, por
     * usuario (mismo patrón que KpiService::guardarSnapshotCarteraMensual,
     * pero por vendedor en vez de global para la empresa). Pensado para
     * correr diariamente: mientras el mes no termine, cada corrida
     * actualiza sus propias filas; en cuanto cambia el mes, deja de
     * tocarlas y el valor queda fijo con el de la última corrida de ese mes.
     */
    public function guardarSnapshotCarteraMensualPorUsuario(): Collection
    {
        $hoy = now();
        $inactivo = EstadoEnum::INACTIVO->value;

        $vencidasPorUsuario = DB::table('gestion_cartera as gc')
            ->join('users', 'gc.user_comercial_id', '=', 'users.id')
            ->where('users.estado_id', '!=', $inactivo)
            ->where('gc.estado', 'pendiente')
            ->whereDate('gc.fecha_vencimiento', '<', $hoy)
            ->select('users.id as user_id', DB::raw('COUNT(DISTINCT gc.id) as total'))
            ->groupBy('users.id')
            ->pluck('total', 'user_id');

        $gestionadasPorUsuario = DB::table('gestion_cartera as gc')
            ->join('gestion_cartera_historial as gh', 'gc.id', '=', 'gh.gestion_cartera_id')
            ->join('users', 'gc.user_comercial_id', '=', 'users.id')
            ->where('users.estado_id', '!=', $inactivo)
            ->where('gc.estado', 'pendiente')
            ->whereDate('gc.fecha_vencimiento', '<', $hoy)
            ->select('users.id as user_id', DB::raw('COUNT(DISTINCT gc.id) as total'))
            ->groupBy('users.id')
            ->pluck('total', 'user_id');

        return $vencidasPorUsuario->keys()
            ->map(function ($userId) use ($vencidasPorUsuario, $gestionadasPorUsuario, $hoy) {
                $vencidas = (int) $vencidasPorUsuario[$userId];
                $gestionadas = (int) ($gestionadasPorUsuario[$userId] ?? 0);
                $pct = $vencidas > 0 ? round(($gestionadas / $vencidas) * 100, 2) : 0;

                return CarteraGestionMensualUsuario::updateOrCreate(
                    ['user_id' => $userId, 'anio' => $hoy->year, 'mes' => $hoy->month],
                    [
                        'cartera_vencidas' => $vencidas,
                        'cartera_gestionadas' => $gestionadas,
                        'cartera_pct_gestion' => $pct,
                    ]
                );
            })
            ->values();
    }

    // Desglose del mes elegido en semanas (1..5, semana de calendario dentro
    // del mes, no semana ISO del año — evita que una semana cruce el límite
    // de mes, que sería confuso para "las semanas de junio"), por vendedor
    // (o uno solo si se pasa $userId), para comparar avance semana a semana
    // entre usuarios. Sin cartera ni metas: esas métricas no tienen una
    // lectura semanal con sentido.
    public function getSemanasDelMes(?int $userId, string $mes): array
    {
        $inactivo = EstadoEnum::INACTIVO->value;
        $inicio = Carbon::createFromFormat('Y-m-d', $mes.'-01')->startOfMonth();
        $fin = $inicio->copy()->endOfMonth();
        $totalSemanas = (int) ceil($inicio->daysInMonth / 7);

        // Mismos filtros que getMesAMes: solo clientes activos cuentan para
        // gestiones y órdenes, y solo usuarios activos, para que la suma de
        // las semanas cuadre con el total mensual que ya se muestra en el
        // dashboard.
        $gestiones = DB::table('seguimiento_clientes')
            ->join('users', 'seguimiento_clientes.user_id', '=', 'users.id')
            ->join('clientes', function ($join) use ($inactivo) {
                $join->on('seguimiento_clientes.cliente_id', '=', 'clientes.id')
                     ->where('clientes.estado_id', '!=', $inactivo);
            })
            ->where('users.estado_id', '!=', $inactivo)
            ->select(
                $this->selectUserIdAgrupado(),
                $this->selectUsuarioAgrupado(),
                DB::raw('CEIL(DAY(seguimiento_clientes.created_at) / 7) as semana_mes'),
                DB::raw('COUNT(*) as total'),
                DB::raw('COUNT(DISTINCT seguimiento_clientes.cliente_id) as clientes_gestionados')
            )
            ->whereBetween('seguimiento_clientes.created_at', [$inicio, $fin])
            ->when($userId, fn ($q) => $q->where('seguimiento_clientes.user_id', $userId))
            ->groupBy('user_id', 'usuario', 'semana_mes')
            ->get();

        // Clientes activos asignados por vendedor (constante en el mes, no
        // por semana — es el "tamaño de cartera" contra el que se mide la
        // cobertura semanal). Mismo criterio que getResumenMesActual.
        $clientesActivosPorUsuario = DB::table('clientes')
            ->join('users', 'clientes.user_id', '=', 'users.id')
            ->where('users.estado_id', '!=', $inactivo)
            ->where('clientes.estado_id', '!=', $inactivo)
            ->select(
                $this->selectUserIdAgrupado(),
                DB::raw('COUNT(*) as total')
            )
            ->when($userId, fn ($q) => $q->where('users.id', $userId))
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $cotizaciones = DB::table('cotizaciones')
            ->join('users', 'cotizaciones.user_id', '=', 'users.id')
            ->where('users.estado_id', '!=', $inactivo)
            ->select(
                $this->selectUserIdAgrupado(),
                $this->selectUsuarioAgrupado(),
                DB::raw('CEIL(DAY(cotizaciones.created_at) / 7) as semana_mes'),
                DB::raw('COUNT(*) as total')
            )
            ->whereBetween('cotizaciones.created_at', [$inicio, $fin])
            ->when($userId, fn ($q) => $q->where('cotizaciones.user_id', $userId))
            ->groupBy('user_id', 'usuario', 'semana_mes')
            ->get();

        $ordenes = DB::table('orden__compras')
            ->join('users', 'orden__compras.user_id', '=', 'users.id')
            ->join('clientes', function ($join) use ($inactivo) {
                $join->on('orden__compras.cliente_id', '=', 'clientes.id')
                     ->where('clientes.estado_id', '!=', $inactivo);
            })
            ->where('users.estado_id', '!=', $inactivo)
            ->select(
                $this->selectUserIdAgrupado(),
                $this->selectUsuarioAgrupado(),
                DB::raw('CEIL(DAY(orden__compras.created_at) / 7) as semana_mes'),
                DB::raw('COUNT(*) as total'),
                DB::raw('COALESCE(SUM(orden__compras.valor_total),0) as ventas'),
                DB::raw('COUNT(DISTINCT orden__compras.cliente_id) as clientes_con_orden')
            )
            ->whereBetween('orden__compras.created_at', [$inicio, $fin])
            ->when($userId, fn ($q) => $q->where('orden__compras.user_id', $userId))
            ->groupBy('user_id', 'usuario', 'semana_mes')
            ->get();

        // Cartera gestionada por semana: a diferencia del % mensual (que es
        // un snapshot congelado porque su denominador "vencidas" es un
        // estado en vivo), esto es un CONTEO de gestiones de cartera
        // registradas esa semana (gestion_cartera_historial.created_at) — un
        // hecho histórico estable, igual que gestiones/cotizaciones/órdenes,
        // que no cambia si después se paga la deuda. No lleva el filtro
        // "estado = pendiente actualmente" que sí tiene el cálculo mensual,
        // justamente para no heredar el mismo problema de recalculo en vivo.
        $carteraGestionada = DB::table('gestion_cartera_historial as gh')
            ->join('gestion_cartera as gc', 'gh.gestion_cartera_id', '=', 'gc.id')
            ->join('users', 'gc.user_comercial_id', '=', 'users.id')
            ->where('users.estado_id', '!=', $inactivo)
            ->select(
                'users.id as user_id',
                'users.name as usuario',
                DB::raw('CEIL(DAY(gh.created_at) / 7) as semana_mes'),
                DB::raw('COUNT(DISTINCT gh.id) as total')
            )
            ->whereBetween('gh.created_at', [$inicio, $fin])
            ->when($userId, fn ($q) => $q->where('gc.user_comercial_id', $userId))
            ->groupBy('users.id', 'users.name', 'semana_mes')
            ->get();

        // Consolidar por usuario, con las semanas del mes ya inicializadas en
        // 0 para que cada vendedor tenga una fila por semana aunque no haya
        // tenido actividad esa semana (necesario para dibujar una línea
        // continua por vendedor en el gráfico).
        $porUsuario = [];

        $asegurarUsuario = function (int $userId, string $usuario) use (&$porUsuario, $totalSemanas) {
            if (isset($porUsuario[$userId])) {
                return;
            }

            $porUsuario[$userId] = [
                'user_id' => $userId,
                'usuario' => $usuario,
                'semanas' => collect(range(1, $totalSemanas))->mapWithKeys(fn ($s) => [$s => [
                    'semana' => $s,
                    'label' => "Sem {$s}",
                    'gestiones' => 0,
                    'cotizaciones' => 0,
                    'ordenes' => 0,
                    'valor_ventas' => 0,
                    'clientes_con_orden' => 0,
                    'cartera_gestionada' => 0,
                    'gestion_comercial_pct' => 0,
                ]])->all(),
            ];
        };

        foreach ($gestiones as $r) {
            $asegurarUsuario($r->user_id, $r->usuario);
            $porUsuario[$r->user_id]['semanas'][$r->semana_mes]['gestiones'] = (int) $r->total;

            $clientesActivos = (int) ($clientesActivosPorUsuario[$r->user_id]->total ?? 0);
            $porUsuario[$r->user_id]['semanas'][$r->semana_mes]['gestion_comercial_pct'] = $clientesActivos > 0
                ? round(((int) $r->clientes_gestionados / $clientesActivos) * 100, 2)
                : 0;
        }

        foreach ($cotizaciones as $r) {
            $asegurarUsuario($r->user_id, $r->usuario);
            $porUsuario[$r->user_id]['semanas'][$r->semana_mes]['cotizaciones'] = (int) $r->total;
        }

        foreach ($ordenes as $r) {
            $asegurarUsuario($r->user_id, $r->usuario);
            $porUsuario[$r->user_id]['semanas'][$r->semana_mes]['ordenes'] = (int) $r->total;
            $porUsuario[$r->user_id]['semanas'][$r->semana_mes]['valor_ventas'] = (float) $r->ventas;
            $porUsuario[$r->user_id]['semanas'][$r->semana_mes]['clientes_con_orden'] = (int) $r->clientes_con_orden;
        }

        foreach ($carteraGestionada as $r) {
            $asegurarUsuario($r->user_id, $r->usuario);
            $porUsuario[$r->user_id]['semanas'][$r->semana_mes]['cartera_gestionada'] = (int) $r->total;
        }

        $usuarios = collect($porUsuario)
            ->map(fn ($u) => [
                'user_id' => $u['user_id'],
                'usuario' => $u['usuario'],
                'semanas' => array_values($u['semanas']),
            ])
            ->sortBy('usuario')
            ->values()
            ->all();

        return [
            'mes' => $mes,
            'semanas_totales' => $totalSemanas,
            'usuarios' => $usuarios,
        ];
    }

    public function getTrimestral(?int $userId, string $inicio): array
    {
        $inactivo = EstadoEnum::INACTIVO->value;
        $periodoExpr = DB::raw('CONCAT(YEAR(created_at), "-Q", QUARTER(created_at)) as trimestre');

        // 1️⃣ Gestiones — solo usuarios activos
        $gestiones = DB::table('seguimiento_clientes')
            ->join('users', 'seguimiento_clientes.user_id', '=', 'users.id')
            ->join('clientes', function ($join) use ($inactivo) {
                $join->on('seguimiento_clientes.cliente_id', '=', 'clientes.id')
                     ->where('clientes.estado_id', '!=', $inactivo);
            })
            ->where('users.estado_id', '!=', $inactivo)
            ->select(
                $this->selectUserIdAgrupado(),
                $this->selectUsuarioAgrupado(),
                DB::raw('CONCAT(YEAR(seguimiento_clientes.created_at), "-Q", QUARTER(seguimiento_clientes.created_at)) as trimestre'),
                DB::raw('COUNT(*) as gestiones'),
                DB::raw('COUNT(DISTINCT seguimiento_clientes.cliente_id) as clientes_gestionados')
            )
            ->where('seguimiento_clientes.created_at', '>=', $inicio)
            ->when($userId, fn ($q) => $q->where('users.id', $userId))
            ->groupBy('user_id', 'usuario', 'trimestre')
            ->get();

        // 2️⃣ Cotizaciones — solo usuarios activos
        $cotizaciones = DB::table('cotizaciones')
            ->join('users', 'cotizaciones.user_id', '=', 'users.id')
            ->where('users.estado_id', '!=', $inactivo)
            ->select(
                $this->selectUserIdAgrupado(),
                $this->selectUsuarioAgrupado(),
                DB::raw('CONCAT(YEAR(cotizaciones.created_at), "-Q", QUARTER(cotizaciones.created_at)) as trimestre'),
                DB::raw('COUNT(*) as cotizaciones')
            )
            ->where('cotizaciones.created_at', '>=', $inicio)
            ->when($userId, fn ($q) => $q->where('users.id', $userId))
            ->groupBy('user_id', 'usuario', 'trimestre')
            ->get();

        // 3️⃣ Órdenes — solo usuarios activos
        $ordenes = DB::table('orden__compras')
            ->join('users', 'orden__compras.user_id', '=', 'users.id')
            ->join('clientes', function ($join) use ($inactivo) {
                $join->on('orden__compras.cliente_id', '=', 'clientes.id')
                     ->where('clientes.estado_id', '!=', $inactivo);
            })
            ->where('users.estado_id', '!=', $inactivo)
            ->select(
                $this->selectUserIdAgrupado(),
                $this->selectUsuarioAgrupado(),
                DB::raw('CONCAT(YEAR(orden__compras.created_at), "-Q", QUARTER(orden__compras.created_at)) as trimestre'),
                DB::raw('COUNT(*) as ordenes'),
                DB::raw('SUM(orden__compras.valor_total) as valor'),
                DB::raw('COUNT(DISTINCT orden__compras.cliente_id) as clientes_con_orden')
            )
            ->where('orden__compras.created_at', '>=', $inicio)
            ->when($userId, fn ($q) => $q->where('users.id', $userId))
            ->groupBy('user_id', 'usuario', 'trimestre')
            ->get();

        // 4️⃣ Fidelización — clientes activos con 2+ órdenes en el trimestre, solo usuarios activos
        $fielesTrimestral = DB::table('orden__compras as o')
            ->join('users', 'o.user_id', '=', 'users.id')
            ->where('users.estado_id', '!=', $inactivo)
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
                $this->selectUserIdAgrupado(),
                DB::raw('CONCAT(YEAR(o.created_at), "-Q", QUARTER(o.created_at)) as trimestre'),
                DB::raw('COUNT(DISTINCT o.cliente_id) as clientes_fieles')
            )
            ->where('o.created_at', '>=', $inicio)
            ->when($userId, fn ($q) => $q->where('o.user_id', $userId))
            ->groupBy('user_id', 'trimestre')
            ->get();

        // 5️⃣ Cartera vencidas por usuario (solo usuarios activos)
        $carteraPorUsuario = DB::table('gestion_cartera as gc')
            ->join('users', 'gc.user_comercial_id', '=', 'users.id')
            ->where('users.estado_id', '!=', $inactivo)
            ->select('users.id as user_id', DB::raw('COUNT(DISTINCT gc.id) as cartera_vencidas'))
            ->where('gc.estado', 'pendiente')
            ->whereDate('gc.fecha_vencimiento', '<', now())
            ->when($userId, fn ($q) => $q->where('gc.user_comercial_id', $userId))
            ->groupBy('users.id')
            ->get()
            ->keyBy('user_id');

        // Gestionadas por trimestre (solo usuarios activos)
        $carteraGestionadasPorUsuarioTrimestre = DB::table('gestion_cartera as gc')
            ->join('gestion_cartera_historial as gh', 'gc.id', '=', 'gh.gestion_cartera_id')
            ->join('users', 'gc.user_comercial_id', '=', 'users.id')
            ->where('users.estado_id', '!=', $inactivo)
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

        // 6️⃣.5 Clientes activos asignados por vendedor (constante, no varía
        // por trimestre — ver mismo criterio en getMesAMes).
        $clientesActivosPorUsuario = DB::table('clientes')
            ->join('users', 'clientes.user_id', '=', 'users.id')
            ->where('users.estado_id', '!=', $inactivo)
            ->where('clientes.estado_id', '!=', $inactivo)
            ->select(
                $this->selectUserIdAgrupado(),
                DB::raw('COUNT(*) as total')
            )
            ->when($userId, fn ($q) => $q->where('users.id', $userId))
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        // 7️⃣ Usuarios activos con ventas por trimestre (divisor de la meta
        // individual). Igual que en getMesAMes: COUNT(DISTINCT CASE...)
        // cuenta cada ejecutivo real y colapsa a otros roles en un solo
        // valor (0), así "Otros" cuenta como una unidad más si vendió.
        $usuariosConVentasPorTrimestre = DB::table('orden__compras')
            ->join('users', 'orden__compras.user_id', '=', 'users.id')
            ->where('users.estado_id', '!=', $inactivo)
            ->select(
                DB::raw('CONCAT(YEAR(orden__compras.created_at), "-Q", QUARTER(orden__compras.created_at)) as trimestre'),
                DB::raw('COUNT(DISTINCT ' . $this->caseUserIdAgrupado() . ') as total_usuarios')
            )
            ->where('orden__compras.created_at', '>=', $inicio)
            ->where('orden__compras.valor_total', '>', 0)
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
                        'user_id'              => (int) $r->user_id,
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
                        'conversion_pct'        => 0,
                        'fidelizacion_pct'      => 0,
                        'cartera_pct_gestion'   => 0,
                        'gestion_comercial_pct' => 0,
                        'meta_trimestre'        => 0,
                        'meta_individual'       => 0,
                        'cumplimiento_pct'      => 0,
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

            $clientesActivos = (int) ($clientesActivosPorUsuario[$r['user_id']]->total ?? 0);
            $r['gestion_comercial_pct'] = $clientesActivos > 0
                ? round(($r['clientes_gestionados'] / $clientesActivos) * 100, 2)
                : 0;
        }
        unset($r);

        return collect($resultado)->sortBy('trimestre')->values()->all();
    }
}
