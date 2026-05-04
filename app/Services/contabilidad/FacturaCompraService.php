<?php

namespace App\Services\contabilidad;

use App\Http\Resources\contabilidad\FacturaCompraResource;
use App\Models\contabilidad\FacturaCompra;
use App\Models\contabilidad\FormaPago;
use App\Models\contabilidad\Impuesto;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class FacturaCompraService
{
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
            'estado_id' => 1,
            'user_id' => auth()->id(),
        ]);

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

                    $monto = $base * ($impuesto->porcentaje / 100);

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

                $monto = $subtotal * ($impuesto->porcentaje / 100);

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

        $montoPago = $pago['monto'] > 0 ? $pago['monto'] : $total;

        $factura->pagos()->create([
            'forma_pago_id' => $pago['forma_pago_id'] ?? $formaPagoId,
            'monto' => $montoPago,
            'fecha_pago' => $pago['fecha_pago'] ?? now(),
            'observaciones' => $pago['observaciones'] ?? null,
            'user_id' => auth()->id(),
        ]);

        $totalPagos += $montoPago;    
    }

} else {

    //  Si NO vienen pagos, igual registrar forma de pago inicial
    if ($formaPagoId) {

        $formaPago = FormaPago::find($formaPagoId);

        if ($formaPago) {

            $nombreFormaPago = strtolower(
                trim(
                    iconv('UTF-8', 'ASCII//TRANSLIT', $formaPago->nombre)
                )
            );

            // 🔹 CONTADO = paga total
            if (str_contains($nombreFormaPago, 'contado')) {

                $montoInicial = $total;
                $totalPagos = $total;

            } else {

                // 🔹 CRÉDITO / TRANSFERENCIA / OTRO
                $montoInicial = 0;
            }

            $factura->pagos()->create([
                'forma_pago_id' => $formaPagoId,
                'monto' => $montoInicial,
                'fecha_pago' => now(),
                'observaciones' => 'Registro inicial automático',
                'user_id' => auth()->id(),
            ]);
        }
    }
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

        // 🔹 9. Estado
        if ($totalPagos == 0) {
            $estado = 1; // Pendiente
        } elseif ($totalPagos < $total) {
            $estado = 5; // Parcial
        } else {
            $estado = 4; // Pagado
        }

        $factura->update([
            'estado_id' => $estado
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
public function actualizar(FacturaCompra $factura, array $data)
{
    return DB::transaction(function () use ($factura, $data) {

        // 🔹 1. Recalcular totales
        $subtotal = collect($data['detalles'])->sum(function ($d) {
            return $d['cantidad'] * $d['precio_unitario'];
        });

        $totalGastos = collect($data['gastos'] ?? [])->sum('monto');
        $totalImpuestos = collect($data['impuestos'] ?? [])->sum('valor');

        $total = $subtotal + $totalGastos + $totalImpuestos;

        // 🔹 2. Actualizar factura
        $factura->update(array_merge($data['factura'], [
            'subtotal' => $subtotal,
            'total' => $total
        ]));

        // =====================================================
        // 🔹 3. DETALLES (sync manual)
        // =====================================================

        if (isset($data['detalles'])) {

            $idsEnviados = collect($data['detalles'])
                ->pluck('id')
                ->filter()
                ->toArray();

            // ❗ eliminar los que ya no existen
            $factura->detalles()
                ->whereNotIn('id', $idsEnviados)
                ->delete();

            foreach ($data['detalles'] as $detalle) {

                $detalle['total'] = $detalle['cantidad'] * $detalle['precio_unitario'];

                if (isset($detalle['id'])) {
                    // actualizar
                    $factura->detalles()->where('id', $detalle['id'])->update($detalle);
                } else {
                    // crear
                    $factura->detalles()->create($detalle);
                }
            }
        }

        // =====================================================
        // 🔹 4. PAGOS
        // =====================================================

$totalPagos = 0;
$formaPagoId = $data['factura']['forma_pago_id'] ?? null;

if (isset($data['pagos'])) {

    $factura->pagos()->delete();

foreach ($data['pagos'] as $pago) {

    $montoPago = $pago['monto'] > 0 ? $pago['monto'] : $total;
    $formaPagoId = $data['factura']['forma_pago_id'] ?? null;

    $factura->pagos()->create([
        'forma_pago_id' => $formaPagoId,
        'monto' => $montoPago,
        'fecha_pago' => $pago['fecha_pago'] ?? now(),
        'observaciones' => $pago['observaciones'] ?? null,
        'user_id' => auth()->id(),
    ]);

    $totalPagos += $montoPago;
}
}

        // =====================================================
        // 🔹 5. GASTOS
        // =====================================================

        if (isset($data['gastos'])) {

            $factura->gastos()->delete();

            foreach ($data['gastos'] as $gasto) {
                $factura->gastos()->create($gasto);
            }
        }

        // =====================================================
        // 🔹 6. IMPUESTOS
        // =====================================================

        if (isset($data['impuestos'])) {

            $factura->impuestos()->delete();

            foreach ($data['impuestos'] as $imp) {
                $factura->impuestos()->create($imp);
            }
        }

        // =====================================================
        // 🔹 7. VALIDAR PAGOS
        // =====================================================

        if ($totalPagos > $total) {
            throw new \Exception('Los pagos no pueden superar el total');
        }

        // =====================================================
        // 🔹 8. ESTADO AUTOMÁTICO
        // =====================================================

        if ($totalPagos == 0) {
            $estado = 1;
        } elseif ($totalPagos < $total) {
            $estado = 2;
        } else {
            $estado = 3;
        }

        $factura->update([
            'estado_id' => $estado
        ]);

        return new FacturaCompraResource($factura->load(['detalles', 'pagos', 'gastos', 'impuestos']));
    });
}


   public function listar(array $filtros = [], $perPage = 15)
{
    $query = FacturaCompra::query()
        ->with(['proveedor', 'detalles', 'pagos', 'gastos', 'impuestos', 'estados']); // 🔥 eager loading

    // 🔹 Filtro por proveedor
    if (!empty($filtros['proveedor_id'])) {
        $query->where('proveedor_id', $filtros['proveedor_id']);
    }

    // 🔹 Filtro por rango de fechas
    if (!empty($filtros['fecha_inicial']) && !empty($filtros['fecha_final'])) {
        $query->whereBetween('fecha_compra', [
            $filtros['fecha_inicial'],
            $filtros['fecha_final']
        ]);
    }

    // 🔹 Filtro por estado
    if (isset($filtros['estado_id']) && $filtros['estado_id'] !== '') {
        $query->where('estado_id', $filtros['estado_id']);
    }

    // 🔹 Búsqueda general (opcional)
    if (!empty($filtros['search'])) {
        $search = $filtros['search'];

        $query->where(function ($q) use ($search) {
            $q->where('numero_factura_proveedor', 'like', "%$search%")
              ->orWhereHas('proveedor', function ($q2) use ($search) {
                  $q2->where('nombre', 'like', "%$search%");
              });
        });
    }

    // 🔹 Ordenamiento
    $query->orderBy('fecha_emision', 'desc');

    return $query->paginate($perPage);
}



   public function anular(FacturaCompra $factura)
{
    return DB::transaction(function () use ($factura) {

        if ($factura->estado_id == 4) {
            return response()->json(['error' => 'La factura ya está anulada'], 400);
        }

        if ($factura->pagos()->exists()) {
            return response()->json(['error' => 'No puedes anular una factura con pagos'], 400);
        }

        // Revertir inventario
        foreach ($factura->detalles as $detalle) {
            $producto = $detalle->producto;
            $producto->stock -= $detalle->cantidad;
            $producto->save();
        }

        // Anular factura
        $factura->estado_id = 4;
        $factura->fecha_anulacion = now();
        $factura->save();

        return $factura;
    });
}

//oBTENER DETALLES DE UNA FACTURA POR ID
public function obtenerDetalles(int $id)
{
    $factura = FacturaCompra::with(['detalles', 'pagos', 'gastos', 'impuestos'])->findOrFail($id);
    return $factura;
    
}

//Anular pasas a estado 4 y no se pueden eliminar, solo anular, para mantener la trazabilidad de los movimientos en el sistema
public function eliminar(int $id)
{
    $factura = FacturaCompra::findOrFail($id);

    if ($factura->estado_id == 4) {
        return response()->json(['error' => 'La factura ya está anulada'], 400);
    }

    if ($factura->pagos()->exists()) {
        return response()->json(['error' => 'No puedes eliminar una factura con pagos'], 400);
    }

    $factura->estado_id = 4;
    $factura->fecha_anulacion = now();
    $factura->save();

  return $factura;
}
    }