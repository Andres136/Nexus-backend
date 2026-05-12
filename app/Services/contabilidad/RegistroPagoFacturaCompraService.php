<?php

namespace App\Services\contabilidad;

use App\Models\contabilidad\FacturaCompra;
use App\Models\contabilidad\FacturaPago;
use Illuminate\Support\Facades\DB;

class RegistroPagoFacturaCompraService
{
public function registrarPago(int $facturaId, array $data)
{
    return DB::transaction(function () use ($facturaId, $data) {

        // 🔹 Buscar factura
        $factura = FacturaCompra::with('pagos')->findOrFail($facturaId);

        $totalFactura = (float) $factura->total;
        $pagosActuales = (float) $factura->pagos()->sum('monto');
        $nuevoPago = (float) $data['monto'];

        // 🔹 Validar monto
        if ($nuevoPago <= 0) {
            throw new \Exception('El monto debe ser mayor a cero');
        }

        // 🔹 Validar sobrepago
        if (($pagosActuales + $nuevoPago) > $totalFactura) {
            throw new \Exception('El pago excede el saldo pendiente');
        }

        // 🔹 Registrar pago
        $factura->pagos()->create([
            'forma_pago_id' => $data['forma_pago_id'],
            'monto' => $nuevoPago,
            'fecha_pago' => $data['fecha_pago'] ?? now(),
            'observaciones' => $data['observaciones'] ?? null,
            'user_id' => auth()->id(),
        ]);

        // 🔹 Recalcular pagos
        $totalPagado = $pagosActuales + $nuevoPago;
        $saldoPendiente = $totalFactura - $totalPagado;

        // 🔹 Determinar estado
        if ($totalPagado <= 0) {
            $estado = 1; // Pendiente
        } elseif ($totalPagado < $totalFactura) {
            $estado = 5; // Parcial
        } else {
            $estado = 4; // Pagado
        }

        // 🔹 Actualizar factura
        $factura->update([
            'estado_id' => $estado,
            'saldo_pendiente' => $saldoPendiente,
        ]);

        // 🔹 Retornar actualizado
        return $factura->fresh([
            'pagos',
            'proveedor',
            'empresa',
            'detalles',
            'estado'
        ]);
    });
}


    public function listar($filters)
    {
        return FacturaCompra::with(['proveedor', 'empresa', 'estado', 'pagos'])
        ->when(isset($filters['proveedor_id']), function ($query) use ($filters) {
            $query->where('proveedor_id', $filters['proveedor_id']);
        })
        ->when(isset($filters['fecha_inicio']) && isset($filters['fecha_fin']), function ($query) use ($filters) {
            $query->whereBetween('fecha_emision', [$filters['fecha_inicio'], $filters['fecha_fin']]);
        })
        ->when(isset($filters['estado_id']), function ($query) use ($filters) {
            $query->where('estado_id', $filters['estado_id']);
        })
        ->when(isset($filters['search']), function ($query) use ($filters) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('numero_factura', 'like', "%$search%")
                  ->orWhereHas('proveedor', function ($q2) use ($search) {
                      $q2->where('nombre', 'like', "%$search%");
                  })
                  ->orWhereHas('empresa', function ($q3) use ($search) {
                      $q3->where('nombre', 'like', "%$search%");
                  });
            });
        })
        ->orderByDesc('created_at')
        ->paginate(20);

    }

    //actualizar pago
    public function actualizarPago(int $pagoId, array $data)
    {
        return DB::transaction(function () use ($pagoId, $data) {

            // 🔹 Buscar pago
            $pago = FacturaPago::findOrFail($pagoId);
            $factura = $pago->facturaCompra;

            $totalFactura = (float) $factura->total;
            $pagosActuales = (float) $factura->pagos()->where('id', '!=', $pagoId)->sum('monto');
            $nuevoPago = (float) $data['monto'];

            // 🔹 Validar monto
            if ($nuevoPago <= 0) {
                throw new \Exception('El monto debe ser mayor a cero');
            }

            // 🔹 Validar sobrepago
            if (($pagosActuales + $nuevoPago) > $totalFactura) {
                throw new \Exception('El pago excede el saldo pendiente');
            }

            // 🔹 Actualizar pago
            $pago->update([
                'forma_pago_id' => $data['forma_pago_id'],
                'monto' => $nuevoPago,
                'fecha_pago' => $data['fecha_pago'] ?? now(),
                'observaciones' => $data['observaciones'] ?? null,
                'user_id' => auth()->id(),
            ]);

            // 🔹 Recalcular estado
            $totalPagado = $pagosActuales + $nuevoPago;
            $saldoPendiente = $totalFactura - $totalPagado;

            if ($totalPagado == 0) {
                $estado = 1; // Pendiente
            } elseif ($totalPagado < $totalFactura) {
                $estado = 5; // Parcial
            } else {
                $estado = 4; // Pagado
            }

            // 🔹 Actualizar factura
            $factura->update([
                'estado_id'       => $estado,
                'saldo_pendiente' => $saldoPendiente,
            ]);

            // 🔹 Retornar modelo actualizado
            return $factura->fresh([
                'pagos',
                'proveedor',
                'empresa',
                'detalles'
            ]);
        });

    }

    public function obtenerDetalles(int $id)
    {
        return FacturaCompra::with(['proveedor', 'empresa', 'estado', 'pagos.formaPago', 'detalles.producto'])
            ->findOrFail($id);
    }
      
        
}