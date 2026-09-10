<?php

namespace App\Services\Crm;

use App\Models\Crm\Inventario;
use App\Models\Crm\KardexMovimiento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KardexService
{
    /**
     * Registra una entrada de stock con costo conocido y recalcula el precio
     * del Inventario por costo promedio ponderado:
     * nuevo_precio = (stock_actual*precio_actual + cantidad*costoUnitario) / (stock_actual+cantidad)
     */
    public function registrarEntrada(
        int $productoId,
        int $empresaId,
        int $sedeId,
        int $bodegaId,
        float $cantidad,
        float $costoUnitario,
        array $meta = []
    ): KardexMovimiento {
        return DB::transaction(function () use ($productoId, $empresaId, $sedeId, $bodegaId, $cantidad, $costoUnitario, $meta) {
            $inventario = Inventario::where([
                'producto_id' => $productoId,
                'empresa_id'  => $empresaId,
                'sede_id'     => $sedeId,
                'bodega_id'   => $bodegaId,
            ])->lockForUpdate()->first();

            if (!$inventario) {
                $inventario = new Inventario([
                    'producto_id' => $productoId,
                    'empresa_id'  => $empresaId,
                    'sede_id'     => $sedeId,
                    'bodega_id'   => $bodegaId,
                    'stock'       => 0,
                    'min_stock'   => 0,
                    'max_stock'   => 0,
                ]);
            }

            $stockAnterior = (float) ($inventario->stock ?? 0);
            $precioAnterior = (float) ($inventario->precio ?? 0);
            $nuevoStock = $stockAnterior + $cantidad;

            $nuevoPrecio = $nuevoStock > 0
                ? (($stockAnterior * $precioAnterior) + ($cantidad * $costoUnitario)) / $nuevoStock
                : $costoUnitario;

            $inventario->stock = $nuevoStock;
            $inventario->precio = $nuevoPrecio;
            $inventario->user_id = $meta['usuario_id'] ?? $inventario->user_id;
            $inventario->save();

            return KardexMovimiento::create([
                'inventario_id'             => $inventario->id,
                'movimiento_stock_id'       => $meta['movimiento_stock_id'] ?? null,
                'factura_compra_detalle_id' => $meta['factura_compra_detalle_id'] ?? null,
                'tipo'                      => 'entrada',
                'cantidad'                  => $cantidad,
                'costo_unitario'            => $costoUnitario,
                'costo_total'               => $cantidad * $costoUnitario,
                'saldo_cantidad'            => $nuevoStock,
                'saldo_costo_unitario'      => $nuevoPrecio,
                'saldo_costo_total'         => $nuevoStock * $nuevoPrecio,
                'usuario_id'                => $meta['usuario_id'] ?? null,
            ]);
        });
    }

    /**
     * Registra una salida de stock. El llamador ya debe haber descontado
     * $inventario->stock (mismo patrón que descontarStockMasivo); este método
     * solo deja la línea del kardex con el costo promedio vigente, que no
     * cambia en una salida.
     */
    public function registrarSalida(Inventario $inventario, float $cantidad, array $meta = []): KardexMovimiento
    {
        $costoUnitario = (float) ($inventario->precio ?? 0);
        $saldoCantidad = (float) $inventario->stock;

        return KardexMovimiento::create([
            'inventario_id'             => $inventario->id,
            'movimiento_stock_id'       => $meta['movimiento_stock_id'] ?? null,
            'factura_compra_detalle_id' => $meta['factura_compra_detalle_id'] ?? null,
            'tipo'                      => 'salida',
            'cantidad'                  => $cantidad,
            'costo_unitario'            => $costoUnitario,
            'costo_total'               => $cantidad * $costoUnitario,
            'saldo_cantidad'            => $saldoCantidad,
            'saldo_costo_unitario'      => $costoUnitario,
            'saldo_costo_total'         => $saldoCantidad * $costoUnitario,
            'usuario_id'                => $meta['usuario_id'] ?? null,
        ]);
    }

    /**
     * Actualiza SOLO el precio (costo promedio ponderado) del Inventario ya
     * existente para un producto en una empresa+sede, a partir de una línea
     * de Factura de Compra. No crea filas de Inventario nuevas ni toca
     * `stock` — el stock lo maneja exclusivamente el flujo de recepción de
     * mercancía (Entregas de Proveedor). Si el producto todavía no tiene
     * inventario en ninguna bodega de esa empresa+sede, no hace nada.
     *
     * Si existe en más de una bodega (caso raro pero real), se actualiza el
     * precio en todas, cada una ponderada contra su propio stock.
     *
     * @return KardexMovimiento[]
     */
    public function actualizarCostoPorFactura(
        int $productoId,
        int $empresaId,
        int $sedeId,
        float $cantidad,
        float $costoUnitario,
        array $meta = []
    ): array {
        return DB::transaction(function () use ($productoId, $empresaId, $sedeId, $cantidad, $costoUnitario, $meta) {
            $inventarios = Inventario::where([
                'producto_id' => $productoId,
                'empresa_id'  => $empresaId,
                'sede_id'     => $sedeId,
            ])->lockForUpdate()->get();

            $kardexCreados = [];

            foreach ($inventarios as $inventario) {
                $stockActual = (float) $inventario->stock;
                $precioActual = (float) ($inventario->precio ?? 0);
                $totalPonderado = $stockActual + $cantidad;

                $nuevoPrecio = $totalPonderado > 0
                    ? (($stockActual * $precioActual) + ($cantidad * $costoUnitario)) / $totalPonderado
                    : $costoUnitario;

                $inventario->precio = $nuevoPrecio;
                $inventario->save();

                $kardexCreados[] = KardexMovimiento::create([
                    'inventario_id'             => $inventario->id,
                    'movimiento_stock_id'       => null,
                    'factura_compra_detalle_id' => $meta['factura_compra_detalle_id'] ?? null,
                    'tipo'                      => 'entrada',
                    'cantidad'                  => $cantidad,
                    'costo_unitario'            => $costoUnitario,
                    'costo_total'               => $cantidad * $costoUnitario,
                    'saldo_cantidad'            => $stockActual,
                    'saldo_costo_unitario'      => $nuevoPrecio,
                    'saldo_costo_total'         => $stockActual * $nuevoPrecio,
                    'usuario_id'                => $meta['usuario_id'] ?? null,
                ]);
            }

            return $kardexCreados;
        });
    }

    public function listar(Request $request)
    {
        $query = KardexMovimiento::with([
            'inventario.producto:id,name,code',
            'inventario.bodega:id,nombre',
            'inventario.sede:id,nombre',
            'inventario.empresa:id,nombre',
            'usuario:id,name',
        ]);

        $this->aplicarFiltros($query, $request);

        return $query->orderBy('created_at', 'DESC')->paginate($request->get('per_page', 20));
    }

    public function exportar(Request $request)
    {
        $query = KardexMovimiento::with([
            'inventario.producto:id,name,code',
            'inventario.bodega:id,nombre',
            'inventario.sede:id,nombre',
            'inventario.empresa:id,nombre',
            'usuario:id,name',
        ]);

        $this->aplicarFiltros($query, $request);

        return $query->orderBy('created_at', 'DESC')->get();
    }

    private function aplicarFiltros($query, Request $request): void
    {
        if ($request->filled('producto_id')) {
            $query->whereHas('inventario', fn ($q) => $q->where('producto_id', $request->producto_id));
        }

        if ($request->filled('bodega_id')) {
            $query->whereHas('inventario', fn ($q) => $q->where('bodega_id', $request->bodega_id));
        }

        if ($request->filled('sede_id')) {
            $query->whereHas('inventario', fn ($q) => $q->where('sede_id', $request->sede_id));
        }

        if ($request->filled('empresa_id')) {
            $query->whereHas('inventario', fn ($q) => $q->where('empresa_id', $request->empresa_id));
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('desde')) {
            $query->whereDate('created_at', '>=', $request->desde);
        }

        if ($request->filled('hasta')) {
            $query->whereDate('created_at', '<=', $request->hasta);
        }
    }
}
