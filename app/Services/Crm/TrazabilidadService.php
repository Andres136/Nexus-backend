<?php

namespace App\Services\Crm;

use App\Models\Crm\Orden_Compra;
use App\Models\Crm\OrdenCompraProveedorDetalle;
use App\Models\Crm\OrdenDeTrabajo;
use App\Models\Crm\product;
use App\Models\Rutas\DeliveryEvent;
use App\Models\Vsm\Alistamiento;
use App\Models\Vsm\AlistamientoUsuarioDetalle;
use Illuminate\Support\Collection;

class TrazabilidadService
{
    // Buscador 1: orden de compra (cliente) -> id, cliente.nombre, o id de su
    // orden de trabajo. El id de orden de compra y el id de su OT son
    // numeraciones DISTINTAS (ej. orden_compra #9 tiene orden_trabajo #14) —
    // el calendario de "Entregas" solo muestra el número de OT ("OT 14"), así
    // que buscar por ese número también debe encontrar la orden de compra
    // correcta, o alguien que busca "14" pensando en el calendario cae en la
    // orden de compra #14 (otro cliente) y no ve nada de lo que esperaba.
    // El identificador humano "code" de orden__compras nunca se usa en la
    // práctica (columna siempre nula), por eso no se busca por ahí.
    public function buscarOrdenesCompra(string $search): Collection
    {
        $query = Orden_Compra::with(['cliente:id,nombre', 'ordenTrabajo:id,orden_compra_id'])
            ->select('id', 'cliente_id', 'created_at', 'valor_total');

        if ($search !== '') {
            if (is_numeric($search)) {
                $searchId = (int) $search;

                // En Rutas el usuario ve y busca el numero de OT, aunque
                // delivery_events guarda el id de la orden de compra. Si el
                // mismo numero existe como OC y como OT, priorizar la OT evita
                // consultar el delivery de otra orden de compra.
                $ordenCompraIdDeOt = OrdenDeTrabajo::whereKey($searchId)
                    ->value('orden_compra_id');

                $query->whereKey($ordenCompraIdDeOt ?? $searchId);
            } else {
                $query->whereHas('cliente', fn ($q) => $q->where('nombre', 'like', "%{$search}%"));
            }
        }

        return $query->latest('id')->limit(20)->get()->map(fn ($oc) => [
            'id' => $oc->id,
            'orden_trabajo_id' => $oc->ordenTrabajo?->id,
            'cliente' => $oc->cliente?->nombre,
            'fecha' => $oc->created_at,
            'valor_total' => $oc->valor_total,
        ]);
    }

    // Dado un id de orden de compra, su orden de trabajo y cuándo fue
    // entregada. La fecha "programada" vive en orden_de_trabajos.fecha_entrega;
    // la fecha real de entrega (en bodega) está en orden_trabajo_entregas.fecha_entrega
    // (puede haber varias entregas parciales). Además se cruza con el módulo
    // Rutas para saber cuándo se asignó un conductor y si ya se le entregó
    // al cliente.
    public function ordenCompraDetalle(int $id): array
    {
        $ordenCompra = Orden_Compra::with([
            'cliente:id,nombre',
            'ordenTrabajo.estado:id,nombre',
            'ordenTrabajo.entregas.usuario:id,name',
            'ordenTrabajo.entregas.detalle.product:id,name',
        ])->findOrFail($id);

        $ordenTrabajo = $ordenCompra->ordenTrabajo;

        $entregas = $ordenTrabajo
            ? $ordenTrabajo->entregas->map(fn ($e) => [
                'fecha_entrega' => $e->fecha_entrega,
                'cantidad' => $e->cantidad,
                'faltante' => $e->faltante,
                'producto' => $e->detalle?->product?->name,
                'usuario' => $e->usuario?->name,
            ])->values()
            : collect();

        return [
            'orden_compra' => [
                'id' => $ordenCompra->id,
                'cliente' => $ordenCompra->cliente?->nombre,
                'fecha' => $ordenCompra->created_at,
                'fecha_entrega' => $ordenCompra->fecha_entrega,
                'valor_total' => $ordenCompra->valor_total,
            ],
            'orden_trabajo' => $ordenTrabajo ? [
                'id' => $ordenTrabajo->id,
                'fecha_entrega_programada' => $ordenTrabajo->fecha_entrega,
                'estado' => $ordenTrabajo->estado?->nombre,
                'fecha_entrega_real' => $entregas->max('fecha_entrega'),
                'entregas' => $entregas,
                'alistamientos' => $this->alistamientosOrdenTrabajo($ordenTrabajo->id),
            ] : null,
            'entregas_ruta' => $this->entregasRuta($id),
        ];
    }

    private function alistamientosOrdenTrabajo(int $ordenTrabajoId): Collection
    {
        $alistamientos = Alistamiento::where('orden_trabajo_id', $ordenTrabajoId)
            ->with([
                'detalles.product:id,name',
                'usuarios:id,name',
            ])
            ->latest('id')
            ->get();

        $usuariosPorDetalle = AlistamientoUsuarioDetalle::whereIn(
            'alistamiento_id',
            $alistamientos->pluck('id')
        )
            ->with('usuario:id,name')
            ->get()
            ->groupBy('detalle_id');

        return $alistamientos->flatMap(function (Alistamiento $alistamiento) use ($usuariosPorDetalle) {
            return $alistamiento->detalles->map(function ($detalle) use ($alistamiento, $usuariosPorDetalle) {
                $usuariosDetalle = $usuariosPorDetalle
                    ->get($detalle->id, collect())
                    ->pluck('usuario.name')
                    ->filter();

                $usuarios = $usuariosDetalle->isNotEmpty()
                    ? $usuariosDetalle
                    : $alistamiento->usuarios->pluck('name')->filter();

                return [
                    'alistamiento_id' => $alistamiento->id,
                    'fecha' => $alistamiento->fecha,
                    'estado' => $alistamiento->estado,
                    'producto' => $detalle->product?->name,
                    'cantidad_programada' => $detalle->cantidad_programada,
                    'cantidad_alistada' => $detalle->cantidad_alistada,
                    'usuarios' => $usuarios->unique()->values(),
                ];
            });
        })->values();
    }

    // Ruta/reparto al cliente (módulo Rutas): cuándo y a qué conductor se le
    // asignó esta orden de compra para entregarla, y el resultado real que
    // registró (delivery_records). tipo="entrega" la distingue de las
    // recogidas en proveedor, que van por proveedor_id, no por orden_id.
    private function entregasRuta(int $ordenCompraId): Collection
    {
        return DeliveryEvent::where('orden_id', $ordenCompraId)
            ->where('tipo', 'entrega')
            ->with(['usuario:id,name', 'vehiculo:id,placa,nombre', 'lastRecord'])
            ->orderByDesc('fecha_entrega')
            ->get()
            ->map(function (DeliveryEvent $ev) {
                // "Cuándo fue entregada" en firme: si el conductor registró un
                // delivery_record, esa es la fecha real. Si no (el flujo de
                // "entrega" a cliente permite marcar completado sin registrar
                // un record, a diferencia de "recogida"), no hay columna
                // dedicada — se usa el updated_at del evento como el momento
                // en que quedó completado.
                $fechaCompletado = $ev->lastRecord?->fecha_real
                    ?? ($ev->estado === 'completado' ? $ev->updated_at : null);

                return [
                    'fecha_entrega' => $ev->fecha_entrega,
                    'hora' => $ev->hora,
                    'estado' => $ev->estado,
                    'conductor' => $ev->usuario?->name,
                    'vehiculo' => $ev->vehiculo?->placa ?? $ev->vehiculo?->nombre,
                    'fecha_completado' => $fechaCompletado,
                    'resultado_real' => $ev->lastRecord ? [
                        'fecha_real' => $ev->lastRecord->fecha_real,
                        'hora_real' => $ev->lastRecord->hora_real,
                        'resultado' => $ev->lastRecord->resultado,
                        'cantidad_entregada' => $ev->lastRecord->cantidad_entregada,
                    ] : null,
                ];
            });
    }

    // Buscador 2: dado un producto, (a) qué orden de compra a proveedor se
    // pidió para reabastecerlo y (b) quién hizo el alistamiento VSM de las
    // órdenes de trabajo que lo contienen.
    public function productoTrazabilidad(int $productoId): array
    {
        $producto = product::select('id', 'name')->findOrFail($productoId);

        $ordenesProveedor = OrdenCompraProveedorDetalle::where('producto_id', $productoId)
            ->whereHas('entregas')
            ->with(['orden:id,numero_orden,fecha,proveedor_id', 'orden.proveedor:id,nombre'])
            ->latest('id')
            ->limit(20)
            ->get()
            ->map(fn ($d) => [
                'orden_id' => $d->orden?->id,
                'numero_orden' => $d->orden?->numero_orden,
                'fecha' => $d->orden?->fecha,
                'proveedor' => $d->orden?->proveedor?->nombre,
                'cantidad_solicitada' => $d->cantidad_solicitada,
                'cantidad_entregada' => $d->cantidad_entregada,
            ]);

        return [
            'producto' => ['id' => $producto->id, 'name' => $producto->name],
            'ordenes_proveedor' => $ordenesProveedor,
        ];
    }
}
