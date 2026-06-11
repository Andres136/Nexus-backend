<?php

namespace App\Http\Controllers\Vsm;

use App\EstadoEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vsm\AlistamientoCreateRequest;
use App\Http\Requests\Vsm\StoreRegistrarProduccionRequest;
use App\Models\Crm\OrdenDeTrabajo;
use App\Models\User;
use App\Models\Vsm\Alistamiento;
use App\Services\Vsm\AlistamientoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AlistamientoController extends Controller
{
    /**
     * Display a listing of the resource.
     */


    protected AlistamientoService $alistamientoService;

    public function __construct()
    {
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
   $alist = $this->alistamientoService->crearAlistamiento($request->validated(), auth()->id());

    return response()->json($alist, 201);
}

    // ------------------------------
    // PAUSAR ALISTAMIENTO
    // ------------------------------
public function pausar($id, Request $request)
{
    $this->alistamientoService->pausarAlistamiento($id, $request->razon);

    return response()->json([
        'status'  => 'PAUSADO',
        'message' => 'Alistamiento y usuarios pausados correctamente',
    ]);
}

    // ------------------------------
    // REANUDAR ALISTAMIENTO
    // ------------------------------
public function reanudar($id)
{
    $this->alistamientoService->reanudarAlistamiento($id);

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

    $user = Auth::user();

    $ordenes = OrdenDeTrabajo::with([
        'ordenCompra.cliente',
        'ordenCompra.detalles.product',
        'estado',
        'entregas'
    ])

    ->whereIn('estado_id', [
        EstadoEnum::PENDIENTE->value,
        EstadoEnum::ENTREGA_PARCIAL->value,
    ])

    // 🔥 FILTRO POR SEDE
    ->when(
        $user->sede_id,
        function ($query) use ($user) {

            $query->whereHas('ordenCompra', function ($q) use ($user) {

                $q->where('sede_id', $user->sede_id);

            });

        }
    )

    ->when($search, function ($q) use ($search) {

        $q->whereHas('ordenCompra.cliente', function ($c) use ($search) {

            $c->where('nombre', 'LIKE', "%{$search}%");

        });

    })

    ->orderByDesc('updated_at')

    ->limit(300)

    ->get();

    $ordenes = $ordenes->map(function ($ot) {

        $detalles = $ot->ordenCompra->detalles;

        $primerDetalle = $detalles->first();

        return [

            'id'                  => $ot->id,
            'numero_ot'           => $ot->numero_ot,
            'estado'              => $ot->estado,
            'orden_compra'        => $ot->ordenCompra,
            'entregas'            => $ot->entregas,

            'product_id'          => $primerDetalle?->product_id,

            'producto_nombre'     => $primerDetalle?->producto?->name,

            'cantidad_programada' => $detalles->sum('cantidad'),

            'total_enviada'       => $detalles->sum('cantidad_enviada'),

            'faltantes'           => $detalles->sum('faltantes'),

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
    ->whereNot('estado_id', 4)
    ->orderBy('name')
    ->get(['id', 'name']);
}



public function agregarUsuario(Request $request, $alistamientoId)
{
    try {
        $this->alistamientoService->agregarUsuario($alistamientoId, $request->input('usuario_id'));
        return response()->json(['message' => 'Usuario agregado correctamente'], 201);
    } catch (\Exception $e) {
        return response()->json(['message' => $e->getMessage()], 422);
    }
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

//Eliminar usuario del alistamiento
public function eliminarUsuario($alistId, $userId)
{
    try {
        $this->alistamientoService->eliminarUsuario($alistId, $userId);
        return response()->json(['message' => 'Usuario eliminado del alistamiento']);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json(['message' => 'Usuario no encontrado en el alistamiento'], 404);
    }
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

    public function pausarPorSede(Request $request)
{
    $user = auth()->user();

    // SIEMPRE desde el usuario
    $sedeId = $user->sede_id;

    if (!$sedeId) {
        return response()->json([
            'message' => 'El usuario no tiene sede asignada'
        ], 422);
    }

    $total = $this->alistamientoService->pausarPorSede(
        $sedeId,
        $request->razon
    );

    return response()->json([
        'message' => 'Órdenes pausadas correctamente',
        'total_afectadas' => $total
    ]);
}


public function reanudarPorSede()
{
    $sedeId = auth()->user()->sede_id;

    if (!$sedeId) {
        return response()->json([
            'message' => 'Usuario sin sede'
        ], 422);
    }

    $total = $this->alistamientoService->reanudarPorSede($sedeId);

    return response()->json([
        'message' => 'Órdenes reanudadas correctamente',
        'total_afectadas' => $total
    ]);
}
}
