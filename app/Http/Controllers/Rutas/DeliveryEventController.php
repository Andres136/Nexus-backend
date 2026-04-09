<?php

namespace App\Http\Controllers\Rutas;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rutas\StoreDeliveryEventRequest;
use App\Mail\DeliveryStatusMail;
use App\Models\Rutas\DeliveryEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class DeliveryEventController extends Controller
{
    /**
     * Display a listing of the resource.
     */
 public function index()
{
    $query = DeliveryEvent::with(['orden.ordenTrabajo.cliente', 'vehiculo', 'lastRecord', 'records', 'usuario']);


    $deliveryEvents = $query->get();

    return response()->json(['data' => $deliveryEvents], 200);
}


    /**
     * Store a newly created resource in storage.
     */
public function store(StoreDeliveryEventRequest $request)
{
    $user = auth()->user();

    // Roles permitidos para crear planeación
    $rolesPermitidos = [1, 2, 4, 7,6]; // admin, logística, coordinador

    if (!in_array($user->role_id, $rolesPermitidos)) {
        return response()->json([
            'message' => 'No tiene permiso para crear eventos de entrega.'
        ], 403);
    }

    // Si pasa la validación, crea el evento
    $deliveryEvent = DeliveryEvent::create($request->validated());

$cliente = $deliveryEvent->orden->cliente;

if ($cliente && $cliente->email) {
    // Aquí puedes enviar una notificación o correo al cliente si es necesario
    Mail::to($cliente->email)->send(new DeliveryStatusMail($deliveryEvent,$cliente));
}

    return response()->json([
        'message' => 'Evento de entrega creado con éxito',
        'data' => $deliveryEvent
    ], 201);
}

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $deliveryEvent = DeliveryEvent::with(['orden', 'usuario', 'vehiculo', 'lastRecord', 'records'])->findOrFail($id);
        return response()->json(['data' => $deliveryEvent], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $deliveryEvent = DeliveryEvent::findOrFail($id);
        $deliveryEvent->update($request->all());
        return response()->json(['message' => 'Evento de entrega actualizado con éxito', 'data' => $deliveryEvent], 200);
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

    // 4️⃣ Enviar correo al cliente
    $cliente = $deliveryEvent->orden->cliente ?? null;

    if ($cliente && $cliente->email) {
        Mail::to($cliente->email)
            ->send(new DeliveryStatusMail($deliveryEvent, $cliente));
    }

    return response()->json([
        'message' => 'Estado actualizado correctamente',
        'event' => $deliveryEvent
    ], 200);
}


public function addRecord(StoreDeliveryEventRequest $request, DeliveryEvent $deliveryEvent)
{
    $data = $request->validated();

    $record = $deliveryEvent->records()->create([
        ...$data,
        'usuario_id' => auth()->id()
    ]);

    return response()->json([
        'message' => 'Registro de entrega añadido',
        'record' => $record
    ]);
}
//Listar  entregas por usuraio autenticado
public function listarEntregasPorUsuario()
{
    $user = auth()->user();
    
    $deliveryEvents = DeliveryEvent::with([
        'orden.ordenTrabajo.cliente',
        'vehiculo', 
        'lastRecord', 
        'records', 
        'usuario'
    ])
    ->where('usuario_id', $user->id)
    ->whereIn('estado', ['pendiente', 'en_ruta'])
    ->orderBy('fecha_entrega', 'desc')
    ->orderBy('hora', 'desc')
    ->get();

    return response()->json([
        'user' => $user,
        'data' => $deliveryEvents
    ], 200);
}
}         