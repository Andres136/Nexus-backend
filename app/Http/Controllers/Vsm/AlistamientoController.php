<?php

namespace App\Http\Controllers\Vsm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vsm\AlistamientoCreateRequest;
use App\Http\Requests\Vsm\StoreRegistrarProduccionRequest;
use App\Models\Crm\OrdenDeTrabajo;
use App\Models\Estados;
use App\Models\User;
use App\Models\Vsm\Alistamiento;
use App\Models\Vsm\AlistamientoDetalle;
use App\Models\Vsm\AlistamientoTiempo;
use App\Models\Vsm\AlistamientoUsuario;
use App\Services\Vsm\AlistamientoService;
use App\Services\Vsm\VsmRuntimeService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AlistamientoController extends Controller
{
    /**
     * Display a listing of the resource.
     */


    protected $service;
    protected $runtimeService;
    protected $alistamientoService;
   public function __construct()
    {
        $this->service = new VsmRuntimeService();
        $this->runtimeService = new AlistamientoService();
        $this->alistamientoService = new AlistamientoService();
    }


    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
 // ------------------------------
    // CREAR ALISTAMIENTO + INICIO
    // ------------------------------
public function store(AlistamientoCreateRequest $request)
{
   $alist = $this->runtimeService->crearAlistamiento($request->validated(), auth()->id());

    return response()->json($alist, 201);
}

    // ------------------------------
    // PAUSAR ALISTAMIENTO
    // ------------------------------
public function pausar($id, Request $request)
{
    $alist = Alistamiento::with('usuarios')->findOrFail($id);

    // Pausar alistamiento principal
    $alist->estado = 'PAUSADO';
    $alist->save();

    // Evento general
    AlistamientoTiempo::create([
        'alistamiento_id' => $alist->id,
        'tipo'            => 'PAUSA',
        'fecha_hora'      => now(),
        'razon'           => $request->razon
    ]);

    // Pausar todos los usuarios asociados
    foreach ($alist->usuarios as $usuario) {
        // 1. Actualizar pivot
        $pivot = $usuario->pivot;

    if ($pivot->inicio) {
        $segundos = now()->timestamp - strtotime($pivot->inicio);

        $alist->usuarios()->updateExistingPivot($usuario->id, [
            'estado' => 'PAUSADO',
            'pausado_en' => now(),
            'inicio' => null, // 🔥 CLAVE
            'tiempo_segundos' => $pivot->tiempo_segundos + $segundos // 🔥 CLAVE
        ]);
    }

        // 2. Registrar evento individual
        AlistamientoTiempo::create([
            'alistamiento_id' => $alist->id,
            'user_id'         => $usuario->id,
            'tipo'            => 'PAUSA',
            'fecha_hora'      => now(),
            'razon'           => $request->razon
        ]);
    }

    return response()->json([
        'status' => 'PAUSADO',
        'message' => 'Alistamiento y usuarios pausados correctamente'
    ]);
}

    // ------------------------------
    // REANUDAR ALISTAMIENTO
    // ------------------------------
public function reanudar($id)
{
    $alist = Alistamiento::with('usuarios')->findOrFail($id);

    $alist->estado = 'REANUDADO';
    $alist->save();

    AlistamientoTiempo::create([
        'alistamiento_id' => $alist->id,
        'tipo'            => 'REANUDACION',
        'fecha_hora'      => now()
    ]);

    foreach ($alist->usuarios as $usuario) {

    $alist->usuarios()->updateExistingPivot($usuario->id, [
        'estado' => 'EN_PROGRESO',
        'inicio' => now(), // 🔥 CLAVE
        'pausado_en' => null,
    ]);

        AlistamientoTiempo::create([
            'alistamiento_id' => $alist->id,
            'user_id'         => $usuario->id,
            'tipo'            => 'REANUDACION',
            'fecha_hora'      => now()
        ]);
    }

    return response()->json(['status' => 'REANUDADO']);
}
    // ------------------------------
    // FINALIZAR
    // ------------------------------
public function finalizar($id)
{
    $result = $this->alistamientoService->finalizarAlistamiento($id);

    return response()->json([
        'status' => 'FINALIZADO',
        'data' => $result
    ]);
}



    // ------------------------------
    // HISTORIAL
    // ------------------------------
    public function historial($id)
    {
        $alist = Alistamiento::with('tiempos')->findOrFail($id);
        return response()->json($alist);
    }

public function alistamientosActivos()
{
      $user = auth()->user();
    $sedeIdFiltro = request('sede_id');
    $alistamientos = $this->alistamientoService->obtenerAlistamientosActivos($user, $sedeIdFiltro);
    return response()->json($alistamientos);
}


//Alistamientos Finalizados
public function alistamientosFinalizados(Request $request)
{
    $perPage   = $request->input('per_page', 10);
    $usuarioId = $request->input('usuario_id');
    $clienteId = $request->input('cliente_id');
    $sedeId    = $request->input('sede_id');

    $query = Alistamiento::with([
        'ordenTrabajo.ordenCompra.cliente',
        'ordenTrabajo.ordenCompra.sede',
        'usuarios',
        'tiempos',
        'detalles.product'
    ])
    ->where('estado', 'FINALIZADO');

    // 🔎 Filtro por usuario que participó
    if ($usuarioId) {
        $query->whereHas('usuarios', function ($q) use ($usuarioId) {
            $q->where('users.id', $usuarioId);
        });
    }

    // 🔎 Filtro por cliente
    if ($clienteId) {
        $query->whereHas('ordenTrabajo.ordenCompra.cliente', function ($q) use ($clienteId) {
            $q->where('id', $clienteId);
        });
    }

    // 🔎 Filtro por sede
    if ($sedeId) {
        $query->whereHas('ordenTrabajo.ordenCompra.sede', function ($q) use ($sedeId) {
            $q->where('id', $sedeId);
        });
    }

    $alistamientos = $query
        ->orderBy('updated_at', 'desc')
        ->paginate($perPage);

    // 🔄 Transformación (MISMA estructura que activos)
    $alistamientos->getCollection()->transform(function ($alist) {

        $usuarios = $alist->usuarios->map(function ($u) {
            $pivot = $u->pivot;

            return [
                'id' => $u->id,
                'name' => $u->name,
                'estado' => $pivot->estado,
                'inicio_usuario' => $pivot->inicio,
                'pausado_en' => $pivot->pausado_en,
                'segundos_usuario' => $pivot->tiempo_segundos ?? 0,
            ];
        });

        $detalles = $alist->detalles->map(function ($d) {
            return [
                'product_id' => $d->product_id,
                'producto' => $d->product->name ?? null,
                'programada' => $d->cantidad_programada,
                'alistada' => $d->cantidad_alistada,
                'faltante' => $d->cantidad_faltante,
            ];
        });

        return [
            'id' => $alist->id,
            'orden_trabajo_id' => $alist->orden_trabajo_id,
            'estado' => $alist->estado,
            'inicio' => $alist->inicio,
            'fin' => $alist->updated_at,
            'segundos_transcurridos' => $alist->segundos_en_vivo,
            'usuarios' => $usuarios,
            'detalles' => $detalles,
            'orden_trabajo' => $alist->ordenTrabajo,
            'sede' => [
                'id' => $alist->ordenTrabajo->ordenCompra->sede->id,
                'nombre' => $alist->ordenTrabajo->ordenCompra->sede->nombre,
            ],
            'cliente' => [
                'id' => $alist->ordenTrabajo->ordenCompra->cliente->id,
                'nombre' => $alist->ordenTrabajo->ordenCompra->cliente->nombre,
            ]
        ];
    });

    return response()->json($alistamientos);
}


public function ordenesTrabajoAlistamiento(Request $request)
{
    $search = $request->input('search');

    $estadoPendiente = Estados::where('nombre', 'Pendiente')->value('id');
    $estadoParcial   = Estados::where('nombre', 'Entrega Parcial')->value('id');

    $ordenes = OrdenDeTrabajo::with([
        'ordenCompra.cliente',
        'ordenCompra.detalles.product', // Necesario para product_id
        'estado',
        'entregas'
    ])
    ->where(function ($q) use ($estadoPendiente, $estadoParcial) {
        $q->where('estado_id', $estadoPendiente)
          ->orWhere('estado_id', $estadoParcial);
    })
    ->when($search, function ($q) use ($search) {
        $q->whereHas('ordenCompra.cliente', function ($c) use ($search) {
            $c->where('nombre', 'LIKE', "%$search%");
        });
    })
    ->orderBy('updated_at', 'desc')
    ->limit(50)
    ->get();

    // ---------------------------------------------
    // 🔥 Extraer product_id y cantidad desde detalles
    // ---------------------------------------------
    $ordenes = $ordenes->map(function ($ot) {

        $detalles = $ot->ordenCompra->detalles;

        $primerDetalle = $detalles->first();  // Usamos el primero (tu flujo usual)

        return [
            'id'                    => $ot->id,
            'numero_ot'             => $ot->numero_ot,
            'estado'                => $ot->estado,
            'orden_compra'          => $ot->ordenCompra,
            'entregas'              => $ot->entregas,

            // 🔥 CAMPOS NUEVOS CORRECTOS
            'product_id'            => $primerDetalle?->product_id,
            'producto_nombre'       => $primerDetalle?->producto?->name,
            'cantidad_programada'   => $detalles->sum('cantidad'),  // cantidad total solicitada
            'total_enviada'         => $detalles->sum('cantidad_enviada'),
            'faltantes'             => $detalles->sum('faltantes'),
        ];
    });

    return response()->json($ordenes, 200);
}

public function registrarProduccion(StoreRegistrarProduccionRequest $request)
{
  

    $data = $this->alistamientoService->registrarProduccion(
        $request->alistamiento_id,
        $request->detalle_id,
        $request->cantidad_alistada,
        $request->usuario_id,
    );

    return response()->json($data);
}


public function usuariosDisponibles($alistamientoId)
{
    $alist = Alistamiento::with('usuarios')->findOrFail($alistamientoId);

    $usuariosAsignados = $alist->usuarios->pluck('id');

    return User::whereNotIn('id', $usuariosAsignados)
        ->orderBy('name')
        ->get(['id', 'name']);
}



public function agregarUsuario(Request $request, $alistamientoId)
{
    $usuarioId = $request->input('usuario_id');

    // Verificar si el usuario ya está asignado
    $existe = AlistamientoUsuario::where('alistamiento_id', $alistamientoId)
        ->where('usuario_id', $usuarioId)
        ->exists();

    if ($existe) {
        return response()->json(['message' => 'Usuario ya asignado'], 400);
    }

    // Asignar el usuario al alistamiento
    AlistamientoUsuario::create([
        'alistamiento_id' => $alistamientoId,
        'usuario_id'      => $usuarioId,
    ]);

    return response()->json(['message' => 'Usuario agregado correctamente'], 201);  
}


public function pausarUsuario($alistId, $userId, Request $request)
{
    $this->alistamientoService->pausarUsuario(
        $alistId,
        $userId,
        $request->razon
    );

    return response()->json(['message' => 'Usuario pausado']);
}


public function reanudarUsuario($alistId, $userId)
{
    $this->alistamientoService->reanudarUsuario($alistId, $userId);

    return response()->json(['message' => 'Usuario reanudado']);
}


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
