<?php

namespace App\Http\Controllers\Rutas;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rutas\RegistrarRecogidaRequest;
use App\Http\Requests\Rutas\StoreDeliveryEventRequest;
use App\Http\Requests\Rutas\UpdateDeliveryEventRequest;
use App\Mail\DeliveryStatusMail;
use App\Models\Rutas\DeliveryEvent;
use App\Models\Crm\OrdenCompraProveedorDetalle;
use App\Services\Crm\Orden_servicio\OrdenesServicioService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class DeliveryEventController extends Controller
{
    // Closures (conEstadoStock) no pueden vivir en una const de clase, por eso es un método.
    private static function eagerLoad(): array
    {
        return [
            'orden.ordenTrabajo.cliente',
            'orden.ordenTrabajo' => fn ($query) => $query->conEstadoStock(),
            'proveedor',
            'ordenServicio.proveedor',
            'ordenesCompraProveedor.detalles',
            'vehiculo',
            'lastRecord',
            'records.detalles.detalle.orden',
            'records.archivos',
            'records.usuario',
            'usuario',
        ];
    }

    /**
     * Marca en cada evento la orden de trabajo con si ya se le descontó el stock por completo,
     * para que el calendario pueda resaltar las entregas con inventario pendiente.
     */
    private function annotateStockStatus(Model|Collection $deliveryEvents): Model|Collection
    {
        $eventos = $deliveryEvents instanceof Collection ? $deliveryEvents : collect([$deliveryEvents]);

        foreach ($eventos as $evento) {
            $ordenTrabajo = $evento->orden->ordenTrabajo ?? null;
            if ($ordenTrabajo) {
                $ordenTrabajo->stock_descontado_completo = $ordenTrabajo->calcularStockDescontadoCompleto();
            }
        }

        return $deliveryEvents;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = DeliveryEvent::with(self::eagerLoad());

        if ($request->filled('desde')) {
            $query->whereDate('fecha_entrega', '>=', $request->input('desde'));
        }

        if ($request->filled('hasta')) {
            $query->whereDate('fecha_entrega', '<=', $request->input('hasta'));
        }

        $deliveryEvents = $this->annotateStockStatus($query->get());

        return response()->json(['data' => $deliveryEvents], 200);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDeliveryEventRequest $request)
    {
        $user = auth()->user();

        // Roles permitidos para crear planeación
        $rolesPermitidos = [1, 2, 4, 7, 6]; // admin, logística, coordinador

        if (!in_array($user->role_id, $rolesPermitidos)) {
            return response()->json([
                'message' => 'No tiene permiso para crear eventos de entrega.'
            ], 403);
        }

        $data = $request->validated();
        $ordenesCompraProveedorIds = $data['ordenes_compra_proveedor_ids'] ?? [];
        unset($data['ordenes_compra_proveedor_ids']);

        $deliveryEvent = DeliveryEvent::create($data);

        if ($deliveryEvent->tipo === 'recogida') {
            $deliveryEvent->ordenesCompraProveedor()->sync($ordenesCompraProveedorIds);
        }

        if ($deliveryEvent->orden) {
            $cliente = $deliveryEvent->orden->cliente;

            if ($cliente && $cliente->email) {
                Mail::to($cliente->email)->send(new DeliveryStatusMail($deliveryEvent, $cliente));
            }
        }

        return response()->json([
            'message' => 'Evento de entrega creado con éxito',
            'data' => $this->annotateStockStatus($deliveryEvent->load(self::eagerLoad()))
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $deliveryEvent = DeliveryEvent::with(self::eagerLoad())->findOrFail($id);
        return response()->json(['data' => $this->annotateStockStatus($deliveryEvent)], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDeliveryEventRequest $request, string $id)
    {
        $deliveryEvent = DeliveryEvent::findOrFail($id);

        $data = $request->validated();
        $ordenesCompraProveedorIds = $data['ordenes_compra_proveedor_ids'] ?? [];
        unset($data['ordenes_compra_proveedor_ids']);

        $deliveryEvent->update($data);

        if ($deliveryEvent->tipo === 'recogida') {
            $deliveryEvent->ordenesCompraProveedor()->sync($ordenesCompraProveedorIds);
        } else {
            $deliveryEvent->ordenesCompraProveedor()->sync([]);
        }

        return response()->json([
            'message' => 'Evento de entrega actualizado con éxito',
            'data' => $this->annotateStockStatus($deliveryEvent->load(self::eagerLoad()))
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $deliveryEvent = DeliveryEvent::findOrFail($id);
        $deliveryEvent->delete();
        return response()->json(['message' => 'Evento de entrega eliminado con éxito'], 200);
    }


    public function changeStatus(Request $request, DeliveryEvent $deliveryEvent)
    {
        $request->validate([
            'estado' => 'required|in:pendiente,en_ruta,completado,cancelado'
        ]);

        // Para recogidas, "completado" es automático: solo lo marca EntregasService cuando
        // bodega registra la entrega de lo que llegó. No se puede forzar manualmente.
        if ($deliveryEvent->tipo === 'recogida' && $request->estado === 'completado') {
            return response()->json([
                'message' => 'Esta recogida se marca como completada automáticamente cuando bodega registra la entrega. No se puede completar manualmente.',
            ], 422);
        }

        // 1️⃣ Estado anterior
        $estadoAnterior = $deliveryEvent->estado;

        // 2️⃣ Evitar reprocesar el mismo estado
        if ($estadoAnterior === $request->estado) {
            return response()->json([
                'message' => 'El estado ya es el mismo, no se realizaron cambios.',
                'event' => $deliveryEvent
            ], 200);
        }

        // 3️⃣ Actualizar estado
        $deliveryEvent->update([
            'estado' => $request->estado
        ]);

        // 4️⃣ Enviar correo al cliente (solo aplica a entregas ligadas a una orden de cliente)
        if ($deliveryEvent->orden) {
            $cliente = $deliveryEvent->orden->cliente ?? null;

            if ($cliente && $cliente->email) {
                Mail::to($cliente->email)
                    ->send(new DeliveryStatusMail($deliveryEvent, $cliente));
            }
        }

        return response()->json([
            'message' => 'Estado actualizado correctamente',
            'event' => $deliveryEvent
        ], 200);
    }


    /**
     * Vincula una OC a una recogida ya creada, sin desvincular las que ya tenía.
     * Se usa cuando el conductor busca un item por nombre/código (porque la recogida se
     * agendó sin OC conocida) y el sistema encuentra en qué OC está: esa OC queda asociada
     * al evento para que el calendario y el listado de "mis entregas" reflejen el número
     * de órdenes real, sin esperar a que se registre la recogida completa.
     */
    public function anexarOrden(Request $request, DeliveryEvent $deliveryEvent)
    {
        $data = $request->validate([
            'orden_compra_proveedor_id' => 'required|exists:orden_compra_proveedores,id',
        ]);

        $deliveryEvent->ordenesCompraProveedor()->syncWithoutDetaching([$data['orden_compra_proveedor_id']]);

        return response()->json([
            'message' => 'Orden vinculada a la recogida',
            'data' => $deliveryEvent->load(self::eagerLoad()),
        ], 200);
    }

    public function addRecord(StoreDeliveryEventRequest $request, DeliveryEvent $deliveryEvent)
    {
        $data = $request->validated();
        unset($data['ordenes_compra_proveedor_ids']);

        $record = $deliveryEvent->records()->create([
            ...$data,
            'usuario_id' => auth()->id()
        ]);

        return response()->json([
            'message' => 'Registro de entrega añadido',
            'record' => $record
        ]);
    }

    /**
     * Registra lo que el conductor recogió físicamente en el proveedor.
     * Es puramente informativo: NO toca `orden_compra_proveedor_detalles.cantidad_entregada`
     * ni el inventario — esa recepción formal sigue siendo el flujo existente de bodega
     * (EntregaProveedorController -> EntregasService).
     * El evento queda en "en_ruta" (ya recogido, va camino a bodega); solo pasa a
     * "completado" cuando bodega registra la entrega de lo que llegó (ver
     * EntregasService::marcarRecogidaComoCompletada).
     */
    public function registrarRecogida(
        RegistrarRecogidaRequest $request,
        DeliveryEvent $deliveryEvent,
        OrdenesServicioService $ordenesServicioService
    )
    {
        $data = $request->validated();

        if ($deliveryEvent->tipo !== 'recogida') {
            throw ValidationException::withMessages([
                'recogida' => ['El evento seleccionado no corresponde a una recogida.'],
            ]);
        }

        if (!empty($data['generar_orden_servicio']) && $deliveryEvent->orden_servicio_id) {
            throw ValidationException::withMessages([
                'generar_orden_servicio' => ['Esta recogida ya tiene una orden de servicio.'],
            ]);
        }

        if (!empty($data['generar_orden_servicio'])
            && (int) $data['proveedor_destino_id'] === (int) $deliveryEvent->proveedor_id) {
            throw ValidationException::withMessages([
                'proveedor_destino_id' => ['El proveedor destino debe ser diferente al proveedor donde se recoge.'],
            ]);
        }

        $detalleIds = collect($data['detalles'])
            ->pluck('orden_compra_proveedor_detalle_id')
            ->map(fn ($id) => (int) $id);

        if ($detalleIds->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages([
                'detalles' => ['No puedes registrar el mismo ítem más de una vez.'],
            ]);
        }

        $detallesCompra = OrdenCompraProveedorDetalle::with('orden')
            ->whereIn('id', $detalleIds)
            ->get()
            ->keyBy('id');

        $detalleNoValido = $detalleIds->first(function ($id) use ($detallesCompra, $deliveryEvent) {
            $detalle = $detallesCompra->get($id);
            return !$detalle || (int) optional($detalle->orden)->proveedor_id !== (int) $deliveryEvent->proveedor_id;
        });

        if ($detalleNoValido) {
            throw ValidationException::withMessages([
                'detalles' => ['Todos los ítems deben pertenecer a órdenes del proveedor donde se realiza la recogida.'],
            ]);
        }

        $resultado = DB::transaction(function () use (
            $request,
            $data,
            $deliveryEvent,
            $ordenesServicioService,
            $detallesCompra
        ) {
            $record = $deliveryEvent->records()->create([
                'fecha_real' => now()->toDateString(),
                'hora_real' => now()->toTimeString(),
                'resultado' => 'entregado',
                'observaciones' => $data['observaciones'] ?? null,
                'usuario_id' => auth()->id(),
            ]);

            foreach ($data['detalles'] as $detalle) {
                $record->detalles()->create([
                    'orden_compra_proveedor_detalle_id' => $detalle['orden_compra_proveedor_detalle_id'],
                    'cantidad_recogida' => $detalle['cantidad_recogida'],
                ]);
            }

            foreach ($request->file('archivos', []) as $archivo) {
                $ruta = $archivo->store('recogidas-proveedor', 'public');

                $record->archivos()->create([
                    'archivo' => $ruta,
                    'tipo' => 'evidencia',
                ]);
            }

            $deliveryEvent->update(['estado' => 'en_ruta']);

            $ordenServicio = null;
            $pdfUrl = null;

            if (!empty($data['generar_orden_servicio'])) {
                $ordenServicio = $ordenesServicioService->createOrdenServicio([
                    'empresa_id' => $data['empresa_id'],
                    'fecha' => now('America/Bogota')->toDateString(),
                    'proveedor_id' => $data['proveedor_destino_id'],
                    'observaciones' => $data['os_observaciones'] ?? $data['observaciones'] ?? null,
                    'detalles' => collect($data['detalles'])->map(fn ($detalle) => [
                        'orden_compra_detalle_id' => $detalle['orden_compra_proveedor_detalle_id'],
                        'cantidad' => $detalle['cantidad_recogida'],
                    ])->all(),
                ]);

                $fileName = "orden_servicio_{$ordenServicio->numero_os}.pdf";
                $pdf = $ordenesServicioService->generarPdf($ordenServicio);
                Storage::disk('public')->put("ordenes_servicio/{$fileName}", $pdf->output());
                $pdfUrl = asset("storage/ordenes_servicio/{$fileName}");

                $deliveryEvent->update(['orden_servicio_id' => $ordenServicio->id]);

                $ordenIds = $detallesCompra->pluck('orden_id')->unique()->all();
                $deliveryEvent->ordenesCompraProveedor()->syncWithoutDetaching($ordenIds);
            }

            return compact('ordenServicio', 'pdfUrl');
        });

        return response()->json([
            'message' => $resultado['ordenServicio']
                ? 'Recogida registrada y orden de servicio generada correctamente'
                : 'Recogida registrada correctamente',
            'data' => $deliveryEvent->fresh()->load(self::eagerLoad()),
            'orden_servicio' => $resultado['ordenServicio'],
            'pdf_url' => $resultado['pdfUrl'],
        ], 200);
    }

    //Listar  entregas por usuraio autenticado
    public function listarEntregasPorUsuario()
    {
        $user = auth()->user();

        $deliveryEvents = $this->annotateStockStatus(
            DeliveryEvent::with(self::eagerLoad())
                ->where('usuario_id', $user->id)
                ->whereIn('estado', ['pendiente', 'en_ruta'])
                ->orderBy('fecha_entrega', 'desc')
                ->orderBy('hora', 'desc')
                ->get()
        );

        return response()->json([
            'user' => $user,
            'data' => $deliveryEvents
        ], 200);
    }
}
