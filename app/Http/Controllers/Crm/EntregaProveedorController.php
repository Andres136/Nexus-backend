<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\EntregasRequest;

use App\Models\Crm\OrdenCompraProveedor;
use App\Models\Crm\OrdenCompraProveedorDetalle;

use App\Services\Crm\EntregasProveedor\EntregasService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use PgSql\Lob;

class EntregaProveedorController extends Controller
{

 protected $entregasService;


 public function __construct(EntregasService $entregasService)
 {
     $this->entregasService = $entregasService;
 }

  public function store(EntregasRequest $request)
{

    

        $entrega = $this->entregasService->registrarEntrega(
            $request->validated(),
            auth()->user()
        );
  
        return response()->json([
            'message' => 'Entrega registrada correctamente',
            'entrega' => $entrega['entrega'],
            'detalle_actualizado' => $entrega['detalle'],
        ], 201);


}



  public function update(EntregasRequest $request, $id)
    {
        try {
            $entrega = $this->entregasService->actualizarEntrega(
                $request->validated(),
                $id,
                auth()->user()
            );

            return response()->json([
                'message' => 'Entrega e inventario actualizados correctamente',
                'entrega' => $entrega,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al actualizar entrega e inventario',
                'detalle' => $e->getMessage(),
            ], 500);
        }
    }





    
    public function referenciasExcedidas()
    {
        $ordenes = \App\Models\Crm\OrdenCompraProveedor::with(['detalles', 'proveedor'])->get();
    
        $excedidos = collect();
    
        foreach ($ordenes as $orden) {
            $excedidosOrden = $orden->detalles->filter(function ($detalle) {
                return $detalle->cantidad_entregada > $detalle->cantidad_solicitada;
            })->map(function ($detalle) use ($orden) {
                return [
                    'orden_id' => $orden->id,
                    'numero_orden' => $orden->numero_orden,
                    'fecha_orden' => $orden->fecha,
                    'proveedor' => $orden->proveedor->nombre ?? 'N/A',
                    'descripcion' => $detalle->descripcion,
                    'cantidad_solicitada' => $detalle->cantidad_solicitada,
                    'cantidad_entregada' => $detalle->cantidad_entregada,
                    'excedente' => $detalle->cantidad_entregada - $detalle->cantidad_solicitada,
                    'item' => $detalle->item,
                ];
            });
    
            $excedidos = $excedidos->merge($excedidosOrden);
        }
    
        return response()->json([
            'referencias_excedidas' => $excedidos->values(),
        ]);
    }


public function referenciasFaltantes()
{
    $ordenes = \App\Models\Crm\OrdenCompraProveedor::with([
        'proveedor',
        'detalles' => function ($q) {
            // 🔥 SOLO TRAE DETALLES CON FALTANTES
            $q->whereColumn('cantidad_entregada', '<', 'cantidad_solicitada');
        },
        'detalles.observaciones.usuario',
        'detalles.observaciones.proceso',
        'detalles.entregas',
    ])
    // 🔥 SOLO TRAE ÓRDENES QUE TIENEN FALTANTES
    ->whereHas('detalles', function ($q) {
        $q->whereColumn('cantidad_entregada', '<', 'cantidad_solicitada');
    })
    ->get();

    $faltantes = collect();

    foreach ($ordenes as $orden) {

        foreach ($orden->detalles as $detalle) {

            $cantidadFaltante = $detalle->cantidad_solicitada - $detalle->cantidad_entregada;
            $totalEntregado = $detalle->entregas->sum('cantidad_entregada');

            $faltantes->push([
                'orden_id' => $orden->id,
                'numero_orden' => $orden->numero_orden,
                'fecha_orden' => $orden->fecha,
                'proveedor' => $orden->proveedor->nombre ?? 'N/A',
                'descripcion' => $detalle->descripcion,
                'cantidad_solicitada' => $detalle->cantidad_solicitada,
                'cantidad_entregada' => $totalEntregado,
                'cantidad_faltante' => $cantidadFaltante,
                'item' => $detalle->item,
                'porcentaje_entregado' => $detalle->cantidad_solicitada > 0
                    ? round(($totalEntregado / $detalle->cantidad_solicitada) * 100, 2)
                    : 0,
                'observaciones' => $detalle->observaciones->map(fn ($obs) => [
                    'id' => $obs->id,
                    'observacion' => $obs->observacion,
                    'estado' => $obs->estado,
                    'fecha' => $obs->created_at,
                    'proveedor' => $obs->proveedor->nombre ?? 'N/A',
                    'usuario' => $obs->usuario->name ?? 'N/A',
                    'proceso' => $obs->proceso
                        ? [
                            'id' => $obs->proceso->id,
                            'nombre' => $obs->proceso->nombre,
                          ]
                        : null,
                ]),
            ]);
        }
    }

    return response()->json([
        'referencias_faltantes' => $faltantes->values(),
    ]);
}
public function referenciasFaltantesbyId($ordenId)
{
    $orden = \App\Models\Crm\OrdenCompraProveedor::with([
        'proveedor',
        'detalles' => function ($q) {
            // 🔥 SOLO TRAE DETALLES CON FALTANTES
            $q->whereColumn('cantidad_entregada', '<', 'cantidad_solicitada');
        },
        'detalles.observaciones.usuario',
        'detalles.observaciones.proceso',
        'detalles.entregas',
    ])->findOrFail($ordenId);

    $faltantes = collect();

    foreach ($orden->detalles as $detalle) {

        $totalEntregado = $detalle->entregas->sum('cantidad_entregada');
        $cantidadFaltante = $detalle->cantidad_solicitada - $totalEntregado;

        // 🔥 SEGURIDAD EXTRA (por si hay inconsistencias)
        if ($totalEntregado >= $detalle->cantidad_solicitada) {
            continue;
        }

        $faltantes->push([
            'orden_id' => $orden->id,
            'numero_orden' => $orden->numero_orden,
            'fecha_orden' => $orden->fecha,
            'proveedor' => $orden->proveedor->nombre ?? 'N/A',
            'descripcion' => $detalle->descripcion,
            'cantidad_solicitada' => $detalle->cantidad_solicitada,
            'cantidad_entregada' => $totalEntregado,
            'cantidad_faltante' => $cantidadFaltante,
            'item' => $detalle->item,
            'detalle_id' => $detalle->id,
            'porcentaje_entregado' => $detalle->cantidad_solicitada > 0
                ? round(($totalEntregado / $detalle->cantidad_solicitada) * 100, 2)
                : 0,
            'observaciones' => $detalle->observaciones->map(fn ($obs) => [
                'id' => $obs->id,
                'observacion' => $obs->observacion,
                'estado' => $obs->estado,
                'fecha' => $obs->updated_at != $obs->created_at 
                    ? $obs->updated_at 
                    : $obs->created_at,
                'proveedor' => $obs->proveedor->nombre ?? 'N/A',
                'usuario' => $obs->usuario->name ?? 'N/A',
                'proceso' => $obs->proceso
                    ? [
                        'id' => $obs->proceso->id,
                        'nombre' => $obs->proceso->nombre,
                    ]
                    : null,
            ]),
        ]);
    }

    return response()->json([
        'referencias_faltantes' => $faltantes->values(),
    ]);
}
    
    public function updateDetalle(Request $request, $id)
{
    $detalle = OrdenCompraProveedorDetalle::findOrFail($id);
    $detalle->update([
        'descripcion' => $request->descripcion,
        'cantidad_solicitada' => $request->cantidad_solicitada,
        'cantidad_entregada' => $request->cantidad_entregada,
        'proceso_bolsas_id' => $request->proceso_bolsas_id,
        'proveedor_id' => $request->proveedor_id,
    ]);

    return response()->json(['mensaje' => 'Detalle actualizado correctamente']);
}

    //Eliminar item de la orden 

   public function eliminarItem(Request $request, $id)
{
    $detalle = OrdenCompraProveedorDetalle::findOrFail($id);

    // Validación extra si necesitas proteger entregas ya realizadas
    if ($detalle->entregas()->exists()) {
        return response()->json([
            'message' => 'No se puede eliminar: ya tiene entregas registradas.'
        ], 422);
    }

    $detalle->delete();

    return response()->json(['message' => 'Ítem eliminado correctamente']);
}

public function descargarPendientes(Request $request)
{
    $proveedorId = $request->query('proveedor_id');

    $detalles = OrdenCompraProveedorDetalle::with([
        'orden.proveedor',
        'observaciones.usuario',
        'observaciones.proceso'
    ])
    ->whereColumn('cantidad_entregada', '<', 'cantidad_solicitada')
    ->when($proveedorId, function ($q) use ($proveedorId) {
        $q->whereHas('orden', function ($qq) use ($proveedorId) {
            $qq->where('proveedor_id', $proveedorId);
        });
    })
    ->limit(300) // 🔥 CONTROL DE CARGA
    ->get();

    if ($detalles->isEmpty()) {
        return response()->json(['mensaje' => 'No hay ítems pendientes.'], 404);
    }

    $itemsPendientes = $detalles->map(function ($d) {

        $observaciones = $d->observaciones
            ->sortByDesc('created_at')
            ->take(5) // 🔥 SOLO LAS MÁS RECIENTES
            ->map(function ($obs) {
                return [
                    'fecha'       => $obs->created_at->format('Y-m-d H:i'),
                    'estado'      => $obs->estado,
                    'observacion' => $obs->observacion,
                    'usuario'     => $obs->usuario->name ?? 'N/A',
                    'proceso'     => $obs->proceso->nombre ?? 'N/A',
                    'proveedor'   => optional($obs->proveedor)->nombre ?? 'N/A',
                ];
            })
            ->values();

        return [
            'orden_id'            => $d->orden->id,
            'numero_orden'        => $d->orden->numero_orden,
            'fecha_orden'         => $d->orden->fecha,
            'proveedor'           => $d->orden->proveedor->nombre ?? 'N/A',
            'item'                => $d->item,
            'descripcion'         => $d->descripcion,
            'cantidad_solicitada' => $d->cantidad_solicitada,
            'cantidad_entregada'  => $d->cantidad_entregada,
            'pendiente'           => $d->cantidad_solicitada - $d->cantidad_entregada,
            'observaciones'       => $observaciones,
        ];
    });

    // 🔥 VALIDACIÓN DE SEGURIDAD
    if ($itemsPendientes->count() > 300) {
        return response()->json([
            'error' => 'Demasiados datos para generar el PDF. Filtra por proveedor.'
        ], 400);
    }

    $pdf = Pdf::loadView('pdf.items_pendientes', [
        'items'    => $itemsPendientes,
        'generado' => now()->format('Y-m-d H:i:s'),
    ]);

    return response($pdf->output(), 200, [
        'Content-Type'        => 'application/pdf',
        'Content-Disposition' => 'attachment; filename="items_pendientes.pdf"',
    ]);
}

public function dashboardOrdenesMensual(Request $request)
{
    $mes = $request->query('mes', now()->month);
    $anio = $request->query('anio', now()->year);

    $inicio = \Carbon\Carbon::create($anio, $mes, 1)->startOfMonth();
    $fin    = \Carbon\Carbon::create($anio, $mes, 1)->endOfMonth();

    // 1️ Órdenes creadas en el mes
    $ordenesTotales = OrdenCompraProveedor::whereBetween('created_at', [$inicio, $fin])->count();

    // 2️Órdenes COMPLETADAS en el mes
    $ordenesCompletadas = OrdenCompraProveedor::where('estado_id', 2)
        ->whereBetween('updated_at', [$inicio, $fin])
        ->count();

    // 3️ Órdenes pendientes
    $ordenesPendientes = $ordenesTotales - $ordenesCompletadas;

    // 4️ Porcentaje de cumplimiento
    $porcentajeCumplimiento = $ordenesTotales > 0
        ? round(($ordenesCompletadas / $ordenesTotales) * 100, 2)
        : 0;

    return response()->json([
        'periodo' => [
            'mes' => $mes,
            'anio' => $anio,
        ],
        'ordenes' => [
            'totales'     => $ordenesTotales,
            'completadas' => $ordenesCompletadas,
            'pendientes'  => max($ordenesPendientes, 0),
            'porcentaje_cumplimiento' => $porcentajeCumplimiento,
        ],
    ]);
}

public function dashboardOrdenesAnual(Request $request)
{
    $user = auth()->user();
    $anio = $request->query('anio', now()->year);

    $sedeId = $request->query('sede_id');

    if (!$sedeId && $user?->sede_id) {
        $sedeId = $user->sede_id;
    }

    $resultado = collect();

    for ($mes = 1; $mes <= 12; $mes++) {

        $inicio = Carbon::create($anio, $mes, 1)->startOfMonth();
        $fin    = Carbon::create($anio, $mes, 1)->endOfMonth();

        $baseQuery = OrdenCompraProveedor::query()
            ->whereBetween('fecha', [
                $inicio->toDateString(),
                $fin->toDateString()
            ]);

        if (!empty($sedeId)) {
            $baseQuery->where('sede_id', $sedeId);
        }

        $totales = (clone $baseQuery)->count();

        $completadas = (clone $baseQuery)
            ->where('estado_id', 2)
            ->count();

        $pendientes = max($totales - $completadas, 0);

        $porcentaje = $totales > 0
            ? round(($completadas / $totales) * 100, 2)
            : 0;

        $resultado->push([
            'mes' => $mes,
            'mes_nombre' => ucfirst($inicio->translatedFormat('F')),
            'ordenes_totales' => $totales,
            'ordenes_completadas' => $completadas,
            'ordenes_pendientes' => $pendientes,
            'porcentaje_cumplimiento' => $porcentaje,
        ]);
    }

    return response()->json([
        'anio' => $anio,
        'sede_aplicada' => $sedeId,
        'resumen_mensual' => $resultado,
    ]);
}

}