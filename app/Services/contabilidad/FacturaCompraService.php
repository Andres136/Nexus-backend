<?php

namespace App\Services\contabilidad;

use App\Http\Resources\contabilidad\FacturaCompraResource;
use App\Models\contabilidad\FacturaCompra;
use Illuminate\Support\Facades\DB;

class FacturaCompraService
{
  public function crear(array $data)
{
    return DB::transaction(function () use ($data) {

    $ultimo = FacturaCompra::latest('id')->first();

$consecutivo = $ultimo ? $ultimo->id + 1 : 1;

$numeroFactura = 'FAC-' . str_pad($consecutivo, 6, '0', STR_PAD_LEFT);

        // 🔹 1. Calcular totales
        $subtotal = collect($data['detalles'])->sum(function ($d) {
            return $d['cantidad'] * $d['precio_unitario'];
        });

        $totalGastos = collect($data['gastos'] ?? [])->sum('monto');
        $totalImpuestos = collect($data['impuestos'] ?? [])->sum('valor');


        $total = $subtotal + $totalGastos + $totalImpuestos;

        // 🔹 2. Crear factura
        $data['factura']['subtotal'] = $subtotal;
        $data['factura']['total'] = $total;
        $data['factura']['numero_factura'] = $numeroFactura;
       $data['factura']['estado_id'] = 1; // pendiente por defecto
       $data['factura']['user_id'] = auth()->id();

        $factura = FacturaCompra::create($data['factura']);

        // 🔹 3. Detalles (productos + inventario)
        foreach ($data['detalles'] as $detalle) {

            $detalle['total'] = $detalle['cantidad'] * $detalle['precio_unitario'];

            $factura->detalles()->create($detalle);

       
        }

        // 🔹 4. Pagos
        $totalPagos = 0;

        if (!empty($data['pagos'])) {
            foreach ($data['pagos'] as $pago) {
                $factura->pagos()->create($pago);
                $totalPagos += $pago['monto'];
            }
        }

        // 🔹 5. Gastos
        if (!empty($data['gastos'])) {
            foreach ($data['gastos'] as $gasto) {
                $factura->gastos()->create($gasto);
            }
        }

        // 🔹 6. Impuestos
        if (!empty($data['impuestos'])) {
            foreach ($data['impuestos'] as $imp) {
                $factura->impuestos()->attach($imp['impuesto_id'], [
    'monto' => $imp['monto']
]);

         
            }

            //validar si se envio  un puck
            if (!empty($data['pucks_id'])) {
                $factura->puck()->attach($data['pucks_id']);
            }
        }

        // 🔹 7. Validar pagos vs total
        if ($totalPagos > 0 && $totalPagos > $total) {
            throw new \Exception('Los pagos no pueden ser mayores al total de la factura');
        }

        // 🔹 8. Estado automático
        if ($totalPagos == 0) {
            $estado = 1; // pendiente
        } elseif ($totalPagos < $total) {
            $estado = 5; // parcial
        } else {
            $estado = 4; // pagado
        }

        $factura->update([
            'estado_id' => $estado
        ]);

        return new FacturaCompraResource($factura->load(['detalles', 'pagos', 'gastos', 'impuestos']));
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

        if (isset($data['pagos'])) {

            $factura->pagos()->delete(); // aquí sí es aceptable

            foreach ($data['pagos'] as $pago) {
                $factura->pagos()->create($pago);
                $totalPagos += $pago['monto'];
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
        ->with(['proveedor', 'detalles', 'pagos']); // 🔥 eager loading

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
    if (!empty($filtros['estado_id'])) {
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
    $query->orderBy('fecha_compra', 'desc');

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