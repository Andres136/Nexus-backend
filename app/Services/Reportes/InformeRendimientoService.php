<?php

namespace App\Services\Reportes;

use App\Models\Compras\RequerimientoCompra;
use App\Models\contabilidad\FacturaCompra;
use App\Models\contabilidad\FacturaPago;
use App\Models\Crm\Inventario;
use App\Models\Crm\MetaMensual;
use App\Models\Crm\MovimientoStock;
use App\Models\Crm\Orden_Compra;
use App\Models\Crm\OrdenCompraProveedor;
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
            'cartera' => $this->datosCartera($anio, $mes),
            'compras' => $this->datosCompras($anio, $mes),
            'contabilidad' => $this->datosContabilidad($anio, $mes),
            'inventario' => $this->datosInventario($anio, $mes),
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
        $modelo = config('services.openai.model');
        $payload = [
            'model' => $modelo,
            'messages' => [
                ['role' => 'system', 'content' => $this->promptSistema()],
                ['role' => 'user', 'content' => json_encode($datos, JSON_UNESCAPED_UNICODE)],
            ],
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
            throw new \RuntimeException('La IA devolvió un informe vacío.');
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
}
