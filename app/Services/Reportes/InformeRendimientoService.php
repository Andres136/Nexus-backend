<?php

namespace App\Services\Reportes;

use App\EstadoEnum;
use App\Models\Compras\RequerimientoCompra;
use App\Models\contabilidad\FacturaCompra;
use App\Models\contabilidad\FacturaPago;
use App\Models\Crm\Inventario;
use App\Models\Crm\MetaMensual;
use App\Models\Crm\MovimientoStock;
use App\Models\Crm\Orden_Compra;
use App\Models\Crm\OrdenCompraProveedor;
use App\Models\Crm\OrdenDeTrabajo;
use App\Models\Traslados\Traslado_Bodega;
use App\Services\Crm\GestionCarteraService;
use App\Services\ProductService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class InformeRendimientoService
{
    public function __construct(
        private readonly GestionCarteraService $carteraService,
        private readonly ProductService $productService,
    ) {}

    public function agregarDatos(int $anio, int $mes): array
    {
        return [
            'periodo' => [
                'anio' => $anio,
                'mes' => $mes,
                'nombre_mes' => ucfirst(Carbon::create($anio, $mes, 1)->locale('es')->isoFormat('MMMM')),
            ],
            'ventas' => $this->datosVentas($anio, $mes),
            'ventas_detalle' => $this->ventasPorSemanaYDia($anio, $mes),
            'cartera' => $this->datosCartera($anio, $mes),
            'ranking_cartera' => $this->carteraService->rankingClientesCartera($anio, $mes),
            'compras' => $this->datosCompras($anio, $mes),
            'contabilidad' => $this->datosContabilidad($anio, $mes),
            'inventario' => $this->datosInventario($anio, $mes),
            'auditoria_inventario_ot' => $this->auditoriaOrdenesSinDescuento($anio, $mes),
        ];
    }

    private function ventasPorSemanaYDia(int $anio, int $mes): array
    {
        $porSemana = Orden_Compra::whereYear('created_at', $anio)
            ->whereMonth('created_at', $mes)
            ->selectRaw('WEEK(created_at, 1) as semana, SUM(valor_total) as total, COUNT(*) as cantidad')
            ->groupBy(DB::raw('WEEK(created_at, 1)'))
            ->orderBy('semana')
            ->get();

        $porDia = Orden_Compra::whereYear('created_at', $anio)
            ->whereMonth('created_at', $mes)
            ->selectRaw('DAY(created_at) as dia, SUM(valor_total) as total, COUNT(*) as cantidad')
            ->groupBy(DB::raw('DAY(created_at)'))
            ->orderBy('dia')
            ->get();

        $diasEnMes = Carbon::create($anio, $mes, 1)->daysInMonth;

        return [
            'por_semana' => $porSemana->map(fn ($r) => [
                'semana' => (int) $r->semana,
                'total' => (float) $r->total,
                'cantidad_ordenes' => (int) $r->cantidad,
            ])->values()->toArray(),
            'por_dia' => collect(range(1, $diasEnMes))->map(function ($dia) use ($porDia) {
                $r = $porDia->firstWhere('dia', $dia);
                return [
                    'dia' => $dia,
                    'total' => $r ? (float) $r->total : 0.0,
                    'cantidad_ordenes' => $r ? (int) $r->cantidad : 0,
                ];
            })->values()->toArray(),
        ];
    }

    /**
     * OT en Completado/Entrega Parcial ya tuvieron entregas registradas (ver
     * OrdenTrabajoService::actualizarEstados), por lo que deberían tener un
     * MovimientoStock tipo=descuento no anulado. Una OT Pendiente sin
     * entregas es normal que no lo tenga, por eso no se incluye aquí.
     */
    private function auditoriaOrdenesSinDescuento(int $anio, int $mes): array
    {
        $base = OrdenDeTrabajo::query()
            ->whereIn('estado_id', [EstadoEnum::COMPLETADO->value, EstadoEnum::ENTREGA_PARCIAL->value])
            ->whereHas('entregas', fn ($q) => $q->whereYear('fecha_entrega', $anio)->whereMonth('fecha_entrega', $mes))
            ->whereHas('ordenCompra', fn ($q) => $q->where('estado_id', '!=', EstadoEnum::INACTIVO->value));

        $totalRevisadas = (clone $base)->count();

        $sinDescuentoQuery = (clone $base)->whereDoesntHave(
            'movimientosStock',
            fn ($q) => $q->where('tipo', 'descuento')->where('anulado', false)
        );

        $detalle = (clone $sinDescuentoQuery)
            ->with('cliente:id,nombre')
            ->orderByDesc('id')
            ->limit(50)
            ->get(['id', 'orden_compra_id', 'cliente_id', 'estado_id', 'valor_total']);

        return [
            'total_ot_revisadas' => $totalRevisadas,
            'total_ot_sin_descuento' => (clone $sinDescuentoQuery)->count(),
            'detalle' => $detalle->map(fn (OrdenDeTrabajo $ot) => [
                'orden_trabajo_id' => $ot->id,
                'orden_compra_id' => $ot->orden_compra_id,
                'cliente' => $ot->cliente?->nombre,
                'estado' => EstadoEnum::from($ot->estado_id)->nombre(),
                'valor_total' => (float) $ot->valor_total,
            ])->values()->toArray(),
        ];
    }

    private function datosVentas(int $anio, int $mes): array
    {
        $mesAnterior = Carbon::create($anio, $mes, 1)->subMonth();

        $valorMes = (float) Orden_Compra::whereYear('created_at', $anio)
            ->whereMonth('created_at', $mes)
            ->sum('valor_total');

        $valorMesAnterior = (float) Orden_Compra::whereYear('created_at', $mesAnterior->year)
            ->whereMonth('created_at', $mesAnterior->month)
            ->sum('valor_total');

        $cantidadOrdenes = Orden_Compra::whereYear('created_at', $anio)
            ->whereMonth('created_at', $mes)
            ->count();

        $metaMensual = MetaMensual::where('anio', $anio)->where('mes', $mes)->value('valor_meta');

        return [
            'valor_vendido_mes' => $valorMes,
            'valor_vendido_mes_anterior' => $valorMesAnterior,
            'variacion_porcentual' => $valorMesAnterior > 0
                ? round((($valorMes - $valorMesAnterior) / $valorMesAnterior) * 100, 2)
                : null,
            'cantidad_ordenes' => $cantidadOrdenes,
            'meta_mensual' => $metaMensual !== null ? (float) $metaMensual : null,
            'cumplimiento_meta_porcentual' => $metaMensual > 0
                ? round(($valorMes / $metaMensual) * 100, 2)
                : null,
        ];
    }

    private function datosCartera(int $anio, int $mes): array
    {
        $anual = $this->carteraService->lineaTiempoAnual($anio);
        $vencidoDelMes = collect($anual['timeline'])->firstWhere('mes', $mes)['total'] ?? 0;

        $recaudoMes = (float) DB::table('gestion_cartera_pivote')
            ->whereYear('fecha_pago', $anio)
            ->whereMonth('fecha_pago', $mes)
            ->sum('valor_pago');

        $bloqueadasPorMora = $this->carteraService->ordenesCompraConCarteraVencida(['per_page' => 1]);

        return [
            'total_cartera_actual' => (float) $anual['total_cartera'],
            'total_vencido_actual' => (float) $anual['total_vencido'],
            'porcentaje_vencido_actual' => (float) $anual['porcentaje_vencido'],
            'vencido_generado_en_mes' => (float) $vencidoDelMes,
            'recaudo_mes' => $recaudoMes,
            'valor_ordenes_bloqueadas_por_mora' => (float) $bloqueadasPorMora['total_valor'],
        ];
    }

    private function datosCompras(int $anio, int $mes): array
    {
        $requerimientosPorEstado = RequerimientoCompra::whereYear('fecha_solicitud', $anio)
            ->whereMonth('fecha_solicitud', $mes)
            ->groupBy('estado')
            ->selectRaw('estado, count(*) as total')
            ->pluck('total', 'estado');

        $ordenesProveedorGeneradas = OrdenCompraProveedor::whereYear('fecha', $anio)
            ->whereMonth('fecha', $mes)
            ->count();

        $faltantes = $this->productService
            ->getEstadisticasFaltantes(new Request())
            ->getData(true);

        return [
            'requerimientos_por_estado' => $requerimientosPorEstado,
            'ordenes_proveedor_generadas_mes' => $ordenesProveedorGeneradas,
            'faltantes_pendientes' => $faltantes,
        ];
    }

    private function datosContabilidad(int $anio, int $mes): array
    {
        $facturasMes = FacturaCompra::whereYear('fecha_emision', $anio)
            ->whereMonth('fecha_emision', $mes);

        return [
            'facturas_recibidas_mes' => (clone $facturasMes)->count(),
            'total_facturado_mes' => (float) (clone $facturasMes)->sum('total'),
            'total_gastos_mes' => (float) (clone $facturasMes)->sum('total_gastos'),
            'total_pagado_mes' => (float) FacturaPago::whereYear('fecha_pago', $anio)
                ->whereMonth('fecha_pago', $mes)
                ->sum('monto'),
            'saldo_pendiente_proveedores_actual' => (float) FacturaCompra::where('saldo_pendiente', '>', 0)
                ->sum('saldo_pendiente'),
        ];
    }

    private function datosInventario(int $anio, int $mes): array
    {
        $valorizacion = Inventario::selectRaw('SUM(stock * precio) as total')->value('total');

        $movimientosPorTipo = MovimientoStock::whereYear('created_at', $anio)
            ->whereMonth('created_at', $mes)
            ->where('anulado', false)
            ->groupBy('tipo')
            ->selectRaw('tipo, count(*) as total')
            ->pluck('total', 'tipo');

        $trasladosPorEstado = Traslado_Bodega::groupBy('estado')
            ->selectRaw('estado, count(*) as total')
            ->pluck('total', 'estado');

        $estadosFinales = ['DESPACHADO', 'RECHAZADO_BODEGA', 'RECHAZADO_INVENTARIO'];
        $trasladosPendientes = $trasladosPorEstado
            ->reject(fn ($total, $estado) => in_array($estado, $estadosFinales, true))
            ->sum();

        return [
            'valorizacion_stock_actual' => (float) ($valorizacion ?? 0),
            'movimientos_por_tipo_mes' => $movimientosPorTipo,
            'traslados_por_estado_actual' => $trasladosPorEstado,
            'traslados_pendientes_actual' => $trasladosPendientes,
        ];
    }

    public function generarInformeTexto(array $datos): string
    {
        return $this->llamarOpenAi([
            ['role' => 'system', 'content' => $this->promptSistema()],
            ['role' => 'user', 'content' => json_encode($datos, JSON_UNESCAPED_UNICODE)],
        ]);
    }

    /**
     * $historial: array de ['rol' => 'user'|'asistente', 'contenido' => string],
     * mantenido por el frontend (no se persiste en BD). Las cifras siempre
     * vienen de $datos ya recalculado por el controller, nunca del cliente.
     */
    public function responderPregunta(array $datos, array $historial, string $pregunta): string
    {
        $mensajes = [
            ['role' => 'system', 'content' => $this->promptSistemaChat()],
            ['role' => 'user', 'content' => "Datos agregados del período seleccionado (única fuente de verdad, en JSON):\n"
                . json_encode($datos, JSON_UNESCAPED_UNICODE)],
        ];

        foreach ($historial as $turno) {
            $mensajes[] = [
                'role' => ($turno['rol'] ?? '') === 'asistente' ? 'assistant' : 'user',
                'content' => (string) ($turno['contenido'] ?? ''),
            ];
        }

        $mensajes[] = ['role' => 'user', 'content' => $pregunta];

        return $this->llamarOpenAi($mensajes);
    }

    private function llamarOpenAi(array $mensajes): string
    {
        $modelo = config('services.openai.model');
        $payload = [
            'model' => $modelo,
            'messages' => $mensajes,
        ];

        if (in_array($modelo, config('services.openai.reasoning_models', []), true)) {
            $payload['reasoning_effort'] = 'none';
        } else {
            $payload['temperature'] = 0.3;
        }

        $response = Http::withToken(config('services.openai.key'))
            ->timeout(30)
            ->post('https://api.openai.com/v1/chat/completions', $payload);

        if (!$response->successful()) {
            $detalle = $response->json('error.message', $response->body());
            throw new \RuntimeException('OpenAI respondió con estado ' . $response->status() . ': ' . mb_substr((string) $detalle, 0, 500));
        }

        $texto = trim((string) $response->json('choices.0.message.content', ''));
        if ($texto === '') {
            throw new \RuntimeException('La IA devolvió una respuesta vacía.');
        }

        return $texto;
    }

    private function promptSistema(): string
    {
        return 'Eres un analista financiero y gerencial de la empresa. Vas a recibir un JSON con KPIs agregados '
            . 'de ventas, cartera vencida, flujo de órdenes de compra, contabilidad de proveedores e inventario '
            . 'correspondientes a un período. Redacta en español un informe ejecutivo de rendimiento breve y claro, '
            . 'con estas secciones: un resumen general de 2-3 frases, hallazgos relevantes por cada área, alertas o '
            . 'riesgos que requieran atención (por ejemplo cartera vencida alta, incumplimiento de meta de ventas, '
            . 'faltantes de compra sin cobertura, saldo alto pendiente a proveedores), y recomendaciones concretas. '
            . 'Basa el informe únicamente en las cifras del JSON recibido: nunca inventes números, nombres de '
            . 'clientes o productos que no estén ahí. Si algún dato viene en cero o null, acláralo en vez de omitirlo. '
            . 'No uses markdown de encabezados con #, usa texto plano con títulos cortos seguidos de dos puntos.';
    }

    private function promptSistemaChat(): string
    {
        return 'Eres un analista financiero y gerencial. Ya recibiste en el primer mensaje un JSON con los KPIs '
            . 'agregados de ventas (incluye desglose semanal y diario), cartera (incluye ranking de clientes por '
            . 'deuda pendiente y por pago reciente), compras, contabilidad, inventario y una auditoría de órdenes '
            . 'de trabajo sin descuento de inventario registrado, todo correspondiente a un único período (mes/año). '
            . 'Responde de forma breve y directa las preguntas puntuales del administrador basándote única y '
            . 'exclusivamente en esas cifras: nunca inventes números, nombres ni fechas que no estén en el JSON. '
            . 'Si la pregunta requiere datos de otro período distinto al que tienes, dilo explícitamente y sugiere '
            . 'generar el informe para ese otro mes/año; no lo estimes ni lo inventes. '
            . 'El ranking de cartera es solo informativo: en ningún caso sugieras, recomiendes ni des a entender que '
            . 'algún cliente debería ser bloqueado o restringido; esa decisión es exclusiva del administrador humano. '
            . 'Si te preguntan por eso, responde solo con las cifras (cuánto debe, desde cuándo, cuánto pagó) y '
            . 'aclara que la decisión de bloqueo le corresponde a él.';
    }
}
