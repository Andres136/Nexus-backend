<?php

namespace App\Services\contabilidad;

use App\EstadoEnum;
use App\Http\Resources\contabilidad\FacturaCompraResource;
use App\Models\contabilidad\FacturaCompra;
use App\Models\contabilidad\FormaPago;
use App\Models\contabilidad\Impuesto;
use App\RolEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class FacturaCompraService
{
    public function __construct(
        private readonly FacturaCompraEstadoService $estadoService
    ) {
    }

 public function crear(array $data)
{
    return DB::transaction(function () use ($data) {

        $ultimo = FacturaCompra::latest('id')->first();
        $consecutivo = $ultimo ? $ultimo->id + 1 : 1;
        $numeroFactura = 'FAC-' . str_pad($consecutivo, 6, '0', STR_PAD_LEFT);

        // 🔹 1. Calcular subtotal
        $subtotal = collect($data['detalles'])->sum(function ($d) {
            return $d['cantidad'] * $d['precio_unitario'];
        });

        $totalGastos = collect($data['gastos'] ?? [])->sum('monto');
        $totalImpuestos = 0;

        // 🔹 2. Crear factura
        $factura = FacturaCompra::create([
            ...$data['factura'],
            'subtotal' => $subtotal,
            'total' => 0,
            'numero_factura' => $numeroFactura,
            'estado_id' => EstadoEnum::PENDIENTE->value,
            'user_id' => auth()->id(),
        ]);
        $factura->ordenesCompraProveedor()->sync($data['ordenes_compra_proveedor_ids'] ?? []);

        // 🔹 3. Detalles + impuestos por detalle
        foreach ($data['detalles'] as $detalle) {

            $base = $detalle['cantidad'] * $detalle['precio_unitario'];

            $detalleModel = $factura->detalles()->create([
                ...$detalle,
                'total' => $base
            ]);

            $impuestosDetalle = 0;

            if (!empty($detalle['impuestos'])) {
                foreach ($detalle['impuestos'] as $imp) {

                    $impuesto = Impuesto::find($imp['impuesto_id']);
                    if (!$impuesto) continue;

                    $monto = $impuesto->calcularMonto($base);

                    $impuestosDetalle += $monto;
                    $totalImpuestos += $monto;

                    $detalleModel->impuestos()->attach($imp['impuesto_id'], [
                        'monto' => $monto
                    ]);
                }
            }

            $detalleModel->update([
                'total' => $base + $impuestosDetalle
            ]);
        }

        // 🔹 4. Impuestos generales
        if (!empty($data['impuestos'])) {
            foreach ($data['impuestos'] as $imp) {

                $impuesto = Impuesto::find($imp['impuesto_id']);
                if (!$impuesto) continue;

                $monto = $impuesto->calcularMonto($subtotal);

                $totalImpuestos += $monto;

                $factura->impuestos()->attach($imp['impuesto_id'], [
                    'monto' => $monto
                ]);
            }
        }

        // 🔹 5. Total final
        $total = $subtotal + $totalGastos + $totalImpuestos;

        $factura->update([
            'total' => $total,
            'total_impuestos' => $totalImpuestos,
            'total_gastos' => $totalGastos
        ]);

        //  6. PAGOS
       
$totalPagos = 0;

$formaPagoId = $data['factura']['forma_pago_id'] ?? null;

if (!empty($data['pagos'])) {

    // 👉 Si vienen pagos manuales
    foreach ($data['pagos'] as $pago) {

        $montoPago = (float) $pago['monto'];

        if ($montoPago <= 0) {
            continue;
        }

        $factura->pagos()->create([
            'forma_pago_id' => $pago['forma_pago_id'] ?? $formaPagoId,
            'monto' => $montoPago,
            'fecha_pago' => $pago['fecha_pago'] ?? now(),
            'observaciones' => $pago['observaciones'] ?? null,
            'user_id' => auth()->id(),
        ]);

        $totalPagos += $montoPago;    
    }

} elseif ($this->esContado($formaPagoId)) {
    $factura->pagos()->create([
        'forma_pago_id' => $formaPagoId,
        'monto' => $total,
        'fecha_pago' => now(),
        'observaciones' => 'Pago automático por compra de contado',
        'user_id' => auth()->id(),
    ]);
    $totalPagos = $total;
    }

        // 🔹 7. GASTOS
        if (!empty($data['gastos'])) {
            foreach ($data['gastos'] as $gasto) {
                $factura->gastos()->create($gasto);
            }
        }

        // 🔹 8. Validación
        if ($totalPagos > 0 && $totalPagos > $total) {
            throw new \Exception('Los pagos no pueden ser mayores al total');
        }

        $factura->update([
            ...$this->estadoService->calcular($total, $totalPagos),
        ]);

        // 🔹 10. PDF
        $logoPath = null;

        $factura->load('empresa');

        if ($factura->empresa && $factura->empresa->logo) {
            $possiblePath = public_path('storage/' . $factura->empresa->logo);

            if (file_exists($possiblePath) && !is_dir($possiblePath)) {
                $logoPath = $possiblePath;
            }
        }

        $pdf = Pdf::loadView('pdf.factura_compra', [
            'factura' => $factura->load([
                'detalles.producto',
                'detalles.impuestos',
                'impuestos',
                'proveedor',
                'empresa'
            ]),
            'logoPath' => $logoPath
        ]);

        $fileName = 'factura_compra_' . $factura->numero_factura . '.pdf';

        Storage::disk('public')->put('facturas/' . $fileName, $pdf->output());

        $factura->update([
            'pdf_url' => 'storage/facturas/' . $fileName
        ]);

        return new FacturaCompraResource(
            $factura->load(['detalles', 'pagos', 'gastos', 'impuestos'])
        );
    });
}

    // Otros métodos como actualizar, eliminar, etc.
public function actualizar(int $id, array $data)
{
    $factura = FacturaCompra::findOrFail($id);
    return $this->_actualizar($factura, $data);
}

private function _actualizar(FacturaCompra $factura, array $data)
{
    return DB::transaction(function () use ($factura, $data) {
        $totalPagadoActual = (float) $factura->pagos()->where('monto', '>', 0)->sum('monto');
        $estadoAnulada = EstadoEnum::ANULADA->value;

        if ((int) $factura->estado_id === $estadoAnulada) {
            throw new \Exception('La factura está anulada y no puede editarse.');
        }

        if ((float) $factura->total > 0 && $totalPagadoActual >= (float) $factura->total) {
            throw new \Exception(
                'La factura ya está pagada y no puede editarse.'
            );
        }
        // =====================================================
        // 🔹 1. RECALCULAR SUBTOTAL
        // =====================================================
        $subtotal = collect($data['detalles'])->sum(function ($d) {
            return $d['cantidad'] * $d['precio_unitario'];
        });

        $totalGastos = collect($data['gastos'] ?? [])->sum('monto');
        $totalImpuestos = 0;

        // =====================================================
        // 🔹 2. ACTUALIZAR FACTURA BASE
        // =====================================================
        $factura->update(array_merge($data['factura'], [
            'subtotal' => $subtotal,
        ]));
        $factura->ordenesCompraProveedor()->sync($data['ordenes_compra_proveedor_ids'] ?? []);

        // =====================================================
        // 🔹 3. DETALLES + IMPUESTOS POR DETALLE
        // =====================================================
        if (isset($data['detalles'])) {

            $idsEnviados = collect($data['detalles'])
                ->pluck('id')
                ->filter()
                ->toArray();

            // Eliminar detalles removidos
            $detallesEliminar = $factura->detalles()
                ->whereNotIn('id', $idsEnviados)
                ->get();

            foreach ($detallesEliminar as $detalleEliminar) {
                $detalleEliminar->impuestos()->detach();
                $detalleEliminar->delete();
            }

            foreach ($data['detalles'] as $detalle) {

                $base = $detalle['cantidad'] * $detalle['precio_unitario'];

                $detalleData = [
                    'bodega_id' => $detalle['bodega_id'] ?? null,
                    'producto_id' => $detalle['producto_id'],
                    'orden_compra_proveedor_detalle_id' => $detalle['orden_compra_proveedor_detalle_id'] ?? null,
                    'puck_id' => $detalle['puck_id'],
                    'cantidad' => $detalle['cantidad'],
                    'precio_unitario' => $detalle['precio_unitario'],
                    'total' => $base,
                ];

                if (!empty($detalle['id'])) {

                    $detalleModel = $factura->detalles()->findOrFail($detalle['id']);
                    $detalleModel->update($detalleData);

                } else {

                    $detalleModel = $factura->detalles()->create($detalleData);
                }

                // Limpiar impuestos anteriores
                $detalleModel->impuestos()->detach();

                $impuestosDetalle = 0;

                if (!empty($detalle['impuestos'])) {

                    foreach ($detalle['impuestos'] as $imp) {

                        $impuesto = Impuesto::find($imp['impuesto_id']);
                        if (!$impuesto) continue;

                        $monto = $impuesto->calcularMonto($base);

                        $detalleModel->impuestos()->attach($imp['impuesto_id'], [
                            'monto' => $monto
                        ]);

                        $impuestosDetalle += $monto;
                        $totalImpuestos += $monto;
                    }
                }

                $detalleModel->update([
                    'total' => $base + $impuestosDetalle
                ]);
            }
        }

        // =====================================================
        // 🔹 4. PAGOS
        // =====================================================
        $totalPagos = $totalPagadoActual;
        $formaPagoId = $data['factura']['forma_pago_id'] ?? null;

        // La forma de pago pertenece a la factura. Los abonos existentes no se
        // eliminan al editar datos generales de la factura.
        $factura->pagos()->where('monto', '<=', 0)->delete();

        // =====================================================
        // 🔹 5. GASTOS
        // =====================================================
        $factura->gastos()->delete();

        if (!empty($data['gastos'])) {
            foreach ($data['gastos'] as $gasto) {
                $factura->gastos()->create($gasto);
            }
        }

        // =====================================================
        // 🔹 6. IMPUESTOS GENERALES
        // =====================================================
        $factura->impuestos()->detach();

        if (!empty($data['impuestos'])) {

            foreach ($data['impuestos'] as $imp) {

                $impuesto = Impuesto::find($imp['impuesto_id']);
                if (!$impuesto) continue;

                $monto = $impuesto->calcularMonto($subtotal);

                $factura->impuestos()->attach($imp['impuesto_id'], [
                    'monto' => $monto
                ]);

                $totalImpuestos += $monto;
            }
        }

        // =====================================================
        // 🔹 7. TOTAL FINAL
        // =====================================================
        $total = $subtotal + $totalGastos + $totalImpuestos;

        if ($totalPagos <= 0 && $this->esContado($formaPagoId)) {
            $factura->pagos()->create([
                'forma_pago_id' => $formaPagoId,
                'monto' => $total,
                'fecha_pago' => now(),
                'observaciones' => 'Pago automático por compra de contado',
                'user_id' => auth()->id(),
            ]);
            $totalPagos = $total;
        }

        if ($totalPagos > $total) {
            throw new \Exception('Los pagos no pueden superar el total');
        }

        $factura->update([
            'total' => $total,
            'total_impuestos' => $totalImpuestos,
            'total_gastos' => $totalGastos,
            ...$this->estadoService->calcular($total, $totalPagos),
        ]);

        return new FacturaCompraResource(
            $factura->load([
                'detalles.impuestos',
                'pagos',
                'gastos',
                'impuestos'
            ])
        );
    });
}

public function listar(array $filtros = [], $perPage = 15)
{
    $query = FacturaCompra::query()
        ->with([
            'proveedor',
            'detalles',
            'pagos.user',
            'gastos',
            'impuestos',
            'estados'
        ]);

    /*
    |--------------------------------------------------------------------------
    | FILTRO PROVEEDOR
    |--------------------------------------------------------------------------
    */
    if (!empty($filtros['proveedor_id'])) {
        $query->where(
            'proveedor_id',
            $filtros['proveedor_id']
        );
    }

    /*
    |--------------------------------------------------------------------------
    | FILTRO FECHAS
    |--------------------------------------------------------------------------
    */
    $fechaInicio = $filtros['fecha_inicio'] ?? $filtros['fecha_inicial'] ?? null;
    $fechaFin = $filtros['fecha_fin'] ?? $filtros['fecha_final'] ?? null;

    if (!empty($fechaInicio) && !empty($fechaFin)) {
        $query->whereBetween(
            'fecha_emision',
            [$fechaInicio, $fechaFin]
        );
    } elseif (!empty($fechaInicio)) {
        $query->whereDate('fecha_emision', '>=', $fechaInicio);
    } elseif (!empty($fechaFin)) {
        $query->whereDate('fecha_emision', '<=', $fechaFin);
    }

    /*
    |--------------------------------------------------------------------------
    | FILTRO ESTADO
    |--------------------------------------------------------------------------
    */
    if (
        isset($filtros['estado_id']) &&
        $filtros['estado_id'] !== ''
    ) {
        $query->where(
            'estado_id',
            $filtros['estado_id']
        );
    }

    /*
    |--------------------------------------------------------------------------
    | FILTRO BÚSQUEDA GENERAL
    |--------------------------------------------------------------------------
    */
    if (!empty($filtros['search'])) {
        $search = $filtros['search'];

        $query->where(function ($q) use ($search) {
            $q->where(
                'numero_factura_proveedor',
                'like',
                "%{$search}%"
            )
            ->orWhere(
                'numero_factura',
                'like',
                "%{$search}%"
            )
            ->orWhereHas(
                'proveedor',
                function ($q2) use ($search) {
                    $q2->where(
                        'nombre',
                        'like',
                        "%{$search}%"
                    );
                }
            );
        });
    }

    /*
    |--------------------------------------------------------------------------
    | FILTRO ESPECÍFICO FACTURA PROVEEDOR
    |--------------------------------------------------------------------------
    */
    if (
        !empty(
            $filtros['numero_factura_proveedor']
        )
    ) {
        $query->where(
            'numero_factura_proveedor',
            'like',
            '%' .
            $filtros['numero_factura_proveedor'] .
            '%'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | ORDENAMIENTO
    |--------------------------------------------------------------------------
    */
    $query->orderBy(
        'fecha_emision',
        'desc'
    );

    /*
    |--------------------------------------------------------------------------
    | PAGINACIÓN
    |--------------------------------------------------------------------------
    */
    $facturas = $query->paginate($perPage);

    /*
    |--------------------------------------------------------------------------
    | TOTALES GLOBALES SEGÚN FILTROS
    |--------------------------------------------------------------------------
    */
    $totalesQuery = clone $query;

    $resumen = [
        'total_facturas' => $totalesQuery->count(),

        'total_subtotal' => (float) $totalesQuery->sum(
            'subtotal'
        ),

        'total_general' => (float) $totalesQuery->sum(
            'total'
        ),

        'total_saldo_pendiente' => (float) $totalesQuery->sum(
            'saldo_pendiente'
        ),

        'total_pagado' => (float) (
            $totalesQuery->sum('total') -
            $totalesQuery->sum('saldo_pendiente')
        ),
    ];

    /*
    |--------------------------------------------------------------------------
    | RESPUESTA FINAL
    |--------------------------------------------------------------------------
    */
    return [
        'facturas' => $facturas,
        'resumen' => $resumen,
    ];
}


public function anular(int $id)
{
    return DB::transaction(function () use ($id) {

        if (!$id || $id <= 0) {
            throw new \Exception('ID de factura inválido.');
        }

        $factura = FacturaCompra::with([
            'pagos',
            'detalles.producto'
        ])->findOrFail($id);

        if ((int) $factura->estado_id === EstadoEnum::ANULADA->value) {
            throw new \Exception(
                'La factura ya está anulada.'
            );
        }

        // ❌ Tiene pagos reales
        $pagosReales = $factura->pagos()
            ->where('monto', '>', 0)
            ->count();

        if ($pagosReales > 0) {
            throw new \Exception(
                'No puedes anular una factura con pagos registrados.'
            );
        }

        // 🗑 Eliminar pagos automáticos en cero
        $factura->pagos()->where('monto', '<=', 0)->delete();

        // ❌ Anular
        $factura->update([
            'estado_id' => EstadoEnum::ANULADA->value,
            'fecha_anulacion' => now(),
            'saldo_pendiente' => 0,
        ]);

        return [
            'message' => 'Factura anulada correctamente.',
            'data' => $factura
        ];
    });
}

private function esContado(?int $formaPagoId): bool
{
    if (!$formaPagoId) {
        return false;
    }

    $nombre = FormaPago::whereKey($formaPagoId)->value('nombre');
    $normalizado = strtolower(trim(iconv('UTF-8', 'ASCII//TRANSLIT', $nombre ?? '')));

    return str_contains($normalizado, 'contado');
}
//oBTENER DETALLES DE UNA FACTURA POR ID
public function obtenerDetalles(int $id)
{
    $factura = FacturaCompra::with([
        'detalles.impuestos',
        'pagos',
        'gastos',
        'impuestos',
        'ordenesCompraProveedor',
    ])->findOrFail($id);
    return $factura;
    
}

public function eliminar(int $id)
{
    return $this->anular($id);
}

public function eliminarDefinitivamente(int $id): void
{
    $user = auth()->user();

    abort_unless(
        $user && (int) $user->role_id === RolEnum::ADMINISTRADOR->value,
        403,
        'Solo un usuario administrador puede eliminar definitivamente una factura.'
    );

    $pdfPath = DB::transaction(function () use ($id) {
        $factura = FacturaCompra::findOrFail($id);
        $pdfPath = $factura->pdf_url;

        // Las relaciones de la factura tienen eliminación en cascada.
        $factura->delete();

        return $pdfPath;
    });

    if ($pdfPath) {
        Storage::disk('public')->delete(
            preg_replace('#^/?storage/#', '', $pdfPath)
        );
    }
}

// ==========================================
// REGISTRAR ABONO / PAGO A FACTURA COMPRA
// ==========================================

}
