<?php

namespace App\Services\Crm\EntregasProveedor;

use App\EstadoEnum;
use App\Models\Crm\EntregaProveedor;
use App\Models\Crm\Inventario;
use App\Models\Crm\Orden_servicio\OrdenServicio;
use App\Models\Crm\Orden_servicio\OrdenServicioDetalle;
use App\Models\Crm\OrdenCompraProveedor;
use App\Models\Crm\OrdenCompraProveedorDetalle;
use App\Models\Crm\OrdenCompraProveedorDetalleOrigen;
use App\Models\Crm\OrdenDetalleObservaciones;
use Illuminate\Support\Facades\DB;

class EntregasService
{
    //Obtener entregas por id producto
    public function obtenerEntregasPorProducto($producto_id)
    {
        $detalles = OrdenCompraProveedorDetalle::with([
            'producto',
            'orden.proveedor',
            'entregas',
            'observaciones.usuario',
            'observaciones.proceso'
        ])
            ->where('producto_id', $producto_id)
            ->whereHas('observaciones')
            ->get()
            ->filter(function ($detalle) {
                return $detalle->entregas->sum('cantidad_entregada') < $detalle->cantidad_solicitada;
            })
            ->map(function ($detalle) {

                $totalEntregado = $detalle->entregas->sum('cantidad_entregada');
                $cantidadFaltante = $detalle->cantidad_solicitada - $totalEntregado;

                return [
                    'orden_id' => $detalle->orden_id,
                    'numero_orden' => $detalle->orden->numero_orden,
                    'fecha_orden' => $detalle->orden->fecha,
                    'proveedor' => $detalle->orden->proveedor->nombre ?? 'N/A',
                    //  PRODUCTO COMPLETO
                    'producto' => $detalle->producto ? [
                        'id' => $detalle->producto->id,
                        'nombre' => $detalle->producto->name,
                        'codigo' => $detalle->producto->code ?? null,
                        'descripcion' => $detalle->producto->description ?? null,
                    ] : null,
                    'detalle_id' => $detalle->id,
                    'descripcion' => $detalle->descripcion,
                    'cantidad_solicitada' => $detalle->cantidad_solicitada,
                    'cantidad_entregada' => $totalEntregado,
                    'cantidad_faltante' => $cantidadFaltante,
                    'item' => $detalle->item,
                    'detalle_id' => $detalle->id,
                    'observaciones' => $detalle->observaciones->map(fn($obs) => [
                        'id' => $obs->id,
                        'observacion' => $obs->observacion,
                        'proveedor' => $obs->proveedor ? [
                            'id' => $obs->proveedor->id,
                            'nombre' => $obs->proveedor->nombre,
                        ] : null,
                        'estado' => $obs->estado,
                        'fecha' => $obs->created_at,
                        'usuario' => $obs->usuario->name ?? 'N/A',
                        'proceso' => $obs->proceso
                            ? [
                                'id' => $obs->proceso->id,
                                'nombre' => $obs->proceso->nombre,

                            ]
                            : null,
                    ]),
                ];
            });

        return $detalles->values();
    }
    public function registrarEntrega(array $data)
    {
        return DB::transaction(function () use ($data) {

            $user = auth()->user();
            $detalle = OrdenCompraProveedorDetalle::with('orden.detalles', 'entregas')
                ->findOrFail($data['detalle_id']);

            // 1️⃣ Crear entrega
            $entrega = EntregaProveedor::create([
                'detalle_id'         => $data['detalle_id'],
                'cantidad_entregada' => $data['cantidad_entregada'],
                'fecha_entrega'      => $data['fecha_entrega'],
                'observaciones'      => $data['observaciones'] ?? null,
                'bodega_id'          => $data['bodega_id'],
                'producto_id'        => $data['producto_id'],
                'user_id'            => $user->id,
                'sede_id'            => $user->sede_id,
                'empresa_id' => $detalle->orden->empresa_id,
            ]);

            // 2️ Actualizar detalle
            $detalle = OrdenCompraProveedorDetalle::with('orden.detalles', 'entregas')
                ->findOrFail($data['detalle_id']);

            $detalle->cantidad_entregada += $data['cantidad_entregada'];
            $detalle->save();

            $this->aplicarEntregaAPrioridades($detalle->id, (float) $data['cantidad_entregada']);

            // 3️ Verificar si detalle quedó completo
            $this->verificarDetalleCompleto($data);
            // Buscar OrdenServicio relacionada
            $detalleCompra = OrdenCompraProveedorDetalle::find($data['detalle_id']);

            $ordenServicioDetalle = OrdenServicioDetalle::where(
                'orden_compra_detalle_id',
                $detalleCompra->id
            )->first();

            if ($ordenServicioDetalle) {

                $ordenServicio = OrdenServicio::with('detalles')
                    ->find($ordenServicioDetalle->orden_servicio_id);

                $completos = $ordenServicio->detalles->every(function ($d) {

                    $detalle = OrdenCompraProveedorDetalle::find($d->orden_compra_detalle_id);

                    return $detalle->cantidad_entregada >= $detalle->cantidad_solicitada;
                });

                $ordenServicio->update([
                    'estado' => $completos ? 'completada' : 'en_proceso'
                ]);
            }
            // 4️ Verificar si orden completa
            $this->actualizarEstadoOrden($detalle->orden_id);

            // 5️ Actualizar inventario
            $this->actualizarInventario($data, $data['producto_id'], 0);

            return [
                'entrega' => $entrega,
                'detalle' => $detalle
            ];
        });
    }

    private function aplicarEntregaAPrioridades(int $detalleProveedorId, float $cantidadEntregada): void
    {
        if ($cantidadEntregada <= 0) {
            return;
        }

        $pendienteAplicar = $cantidadEntregada;

        $origenes = OrdenCompraProveedorDetalleOrigen::query()
            ->where('orden_compra_proveedor_detalle_id', $detalleProveedorId)
            ->orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(prioridad_snapshot, '$.fecha_entrega')) IS NULL")
            ->orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(prioridad_snapshot, '$.fecha_entrega')) ASC")
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($origenes as $origen) {
            $cantidadPrioridad = (float) ($origen->cantidad_prioridad ?: $origen->cantidad_solicitada);
            $pendienteOrigen = max(0, $cantidadPrioridad - (float) $origen->cantidad_recibida_aplicada);

            if ($pendienteOrigen <= 0) {
                continue;
            }

            $aplicar = min($pendienteAplicar, $pendienteOrigen);
            $origen->cantidad_recibida_aplicada += $aplicar;
            $origen->save();

            $pendienteAplicar -= $aplicar;

            if ($pendienteAplicar <= 0) {
                break;
            }
        }
    }


    public function actualizarEntrega(array $data, int $id, $user): EntregaProveedor
    {
        return DB::transaction(function () use ($data, $id, $user) {
            $entrega = EntregaProveedor::findOrFail($id);
            $cantidadAnterior = $entrega->cantidad_entregada;

            $entrega->update([
                'cantidad_entregada' => $data['cantidad_entregada'],
                'fecha_entrega'      => $data['fecha_entrega'],
                'observaciones'      => $data['observaciones'] ?? null,
                'bodega_id'          => $data['bodega_id'],
                'producto_id'        => $data['producto_id'] ?? null,
                'user_id'            => $user->id,
                'sede_id'            => $user->sede_id,

            ]);

            $productoId = $this->resolverProductoId($data);
            $this->actualizarInventario($data, $productoId, $cantidadAnterior);

            $detalle = OrdenCompraProveedorDetalle::findOrFail($entrega->detalle_id);
            $detalle->cantidad_entregada += ($data['cantidad_entregada'] - $cantidadAnterior);
            $detalle->save();

            $this->verificarDetalleCompleto(['detalle_id' => $entrega->detalle_id]);
            $this->actualizarEstadoOrden($detalle->orden_id);

            return $entrega;
        });
    }

    private function resolverProductoId(array $data): ?int
    {
        $productoId = $data['producto_id'] ?? null;

        if (!$productoId && isset($data['detalle_id'])) {
            $detalle = OrdenCompraProveedorDetalle::find($data['detalle_id']);
            if ($detalle) {
                $productoId = $detalle->producto_id;
            }
        }

        return $productoId;
    }

    private function actualizarInventario(array $data, ?int $productoId, float $cantidadAnterior): void
    {
        $detalle = OrdenCompraProveedorDetalle::with('orden')
            ->find($data['detalle_id']);

        if (!$detalle) {
            return;
        }

        $empresaId = $detalle->orden->empresa_id;
        $sedeId    = auth()->user()->sede_id;
        $bodegaId  = $data['bodega_id'];

        if (!$productoId || !$empresaId || !$sedeId || !$bodegaId) {
            return;
        }

        $inventario = Inventario::firstOrCreate(
            [
                'producto_id' => $productoId,
                'empresa_id'  => $empresaId,
                'sede_id'     => $sedeId,
                'bodega_id'   => $bodegaId,
            ],
            ['stock' => 0]
        );

        $inventario->stock = ($inventario->stock - $cantidadAnterior) + $data['cantidad_entregada'];

        if ($inventario->stock < 0) {
            $inventario->stock = 0;
        }

        $inventario->save();
    }

    private function actualizarEstadoOrden(int $ordenId): void
    {
        $orden = OrdenCompraProveedor::with('detalles')->findOrFail($ordenId);

        $todosCompletos = $orden->detalles->every(
            fn($d) => $d->cantidad_entregada >= $d->cantidad_solicitada
        );

        $algunoIniciado = $orden->detalles->some(
            fn($d) => $d->cantidad_entregada > 0
        );

        if ($todosCompletos) {
            $orden->estado_id = EstadoEnum::COMPLETADO->value;
        } elseif ($algunoIniciado) {
            $orden->estado_id = EstadoEnum::ENTREGA_PARCIAL->value;
        }

        $orden->save();
    }

    private function verificarDetalleCompleto(array $data): void
    {
        if (!isset($data['detalle_id'])) {
            return;
        }

        $detalle = OrdenCompraProveedorDetalle::with('entregas')
            ->find($data['detalle_id']);

        if (!$detalle) {
            return;
        }

        $totalEntregado = $detalle->entregas->sum('cantidad_entregada');
        $nuevoEstado = $totalEntregado >= $detalle->cantidad_solicitada
            ? 'completada'
            : 'pendiente';
        OrdenDetalleObservaciones::where('orden_detalle_id', $detalle->id)
            ->where('estado', '!=', $nuevoEstado)
            ->update(['estado' => $nuevoEstado]);
    }
}
