<?php

namespace App\Http\Controllers\Vsm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vsm\AlistamientoCreateRequest;
use App\Models\Crm\OrdenDeTrabajo;
use App\Models\Estados;
use App\Models\Vsm\Alistamiento;
use App\Models\Vsm\AlistamientoDetalle;
use App\Models\Vsm\AlistamientoTiempo;
use App\Models\Vsm\AlistamientoUsuario;
use App\Services\Vsm\VsmRuntimeService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AlistamientoController extends Controller
{
    /**
     * Display a listing of the resource.
     */


    protected $service;
   public function __construct()
    {
        $this->service = new VsmRuntimeService();
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
    // 1. Crear alistamiento principal
    $alist = Alistamiento::create([
        'orden_trabajo_id' => $request->orden_trabajo_id,
        'producto_id'      => $request->producto_id, // puede venir null
        'usuario_id'       => auth()->id(),          // usuario que inicia
        'cantidad'         => $request->cantidad,
        'estado'           => 'INICIADO',
        'fecha'            => now()->toDateString(),
    ]);

    // 2. Registrar usuarios asignados (tabla pivot)
    foreach ($request->usuarios as $usuarioId) {
        AlistamientoUsuario::create([
            'alistamiento_id' => $alist->id,
            'usuario_id'      => $usuarioId,
        ]);
    }

    // 3. Crear detalles de alistamiento (UNO POR CADA ITEM DE LA OT)
    $ordenTrabajo = OrdenDeTrabajo::with('ordenCompra.detalles')->find($request->orden_trabajo_id);

    foreach ($ordenTrabajo->ordenCompra->detalles as $item) {
        AlistamientoDetalle::create([
            'alistamiento_id'      => $alist->id,
            'product_id'           => $item->product_id, // puede venir null
            'cantidad_programada'  => $item->cantidad,
            'cantidad_alistada'    => 0,
            'cantidad_faltante'    => $item->faltantes ?? null,
        ]);
    }

    // 4. Registrar evento de INICIO
    AlistamientoTiempo::create([
        'alistamiento_id' => $alist->id,
        'tipo'            => 'INICIO',
        'fecha_hora'      => now(),
    ]);

    return response()->json($alist, 201);
}

    // ------------------------------
    // PAUSAR ALISTAMIENTO
    // ------------------------------
    public function pausar($id)
    {
        $alist = Alistamiento::findOrFail($id);
        $alist->estado = 'PAUSADO';
        $alist->save();

        AlistamientoTiempo::create([
            'alistamiento_id' => $id,
            'tipo'            => 'PAUSA',
            'fecha_hora'      => now(),
            'razon'           => request('razon')
        ]);

        return response()->json(['status' => 'PAUSADO']);
    }

    // ------------------------------
    // REANUDAR ALISTAMIENTO
    // ------------------------------
    public function reanudar($id)
    {
        $alist = Alistamiento::findOrFail($id);
        $alist->estado = 'REANUDADO';
        $alist->save();

        AlistamientoTiempo::create([
            'alistamiento_id' => $id,
            'tipo'            => 'REANUDACION',
            'fecha_hora'      => now()
        ]);

        return response()->json(['status' => 'REANUDADO']);
    }

    // ------------------------------
    // FINALIZAR
    // ------------------------------
    public function finalizar($id)
    {
        $alist = Alistamiento::findOrFail($id);
        $alist->estado = 'FINALIZADO';
        $alist->save();

        AlistamientoTiempo::create([
            'alistamiento_id' => $id,
            'tipo'            => 'FINALIZACION',
            'fecha_hora'      => now()
        ]);

        // Calcular tiempo total productivo
        $alist->calcularDuracion();

        // Distribuir tiempo entre detalles del service
        $this->service->distribuirTiempoPorDetalles($alist);
     

        return response()->json([
            'status' => 'FINALIZADO',
            'tiempo_total' => $alist->duracion_formateada
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
    // Estados que representan un alistamiento en curso
    $estadosActivos = ['INICIADO', 'PAUSADO', 'REANUDADO'];

    $alistamientos = Alistamiento::with([
        'ordenTrabajo.ordenCompra.cliente',
        'ordenTrabajo.ordenCompra.sede',
        'usuarios',            // relación pivot user <-> alistamiento
        'tiempos',             // historial de eventos
        'detalles.product'     // si usas alistamiento_detalles
    ])
    ->whereIn('estado', $estadosActivos)
    ->orderBy('updated_at', 'desc')
    ->get()
    ->map(function ($alist) {

        // -------------------------------
        // TIEMPO GENERAL DEL ALISTAMIENTO
        // -------------------------------
        $tiempoTotal = $alist->segundos_en_vivo;

        // -----------------------------------
        // TIEMPO POR CADA USUARIO ASIGNADO
        // -----------------------------------
        $usuarios = $alist->usuarios->map(function ($u) {

            // Pivot trae los datos de tiempo
            $pivot = $u->pivot;

            return [
                'id' => $u->id,
                'name' => $u->name,
                'estado' => $pivot->estado,   // EN_PROGRESO o PAUSADO
                'inicio_usuario' => $pivot->inicio,
                'pausado_en' => $pivot->pausado_en,
                'segundos_usuario' => $pivot->tiempo_segundos ?? 0,
              
            ];
        });

        // -------------------------------
        // DETALLES POR PRODUCTO (opcional)
        // -------------------------------
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
            'segundos_transcurridos' => $tiempoTotal,
            'usuarios' => $usuarios,
            'detalles' => $detalles,
            'orden_trabajo' => $alist->ordenTrabajo,
            'sede'=>[
                'id'=>$alist->ordenTrabajo->ordenCompra->sede->id,
                'nombre'=>$alist->ordenTrabajo->ordenCompra->sede->nombre,
            ],
            'cliente'=>[
                'id'=>$alist->ordenTrabajo->ordenCompra->cliente->id,
                'nombre'=>$alist->ordenTrabajo->ordenCompra->cliente->nombre,
            ]
        ];

    });

     //Ordenar por sede nombre ascendente
    $alistamientos = $alistamientos->sortBy(function($alistamiento) {
        return $alistamiento['sede']['nombre'];
    })->values()->all();

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


public function pausarUsuario($alistId, $userId, Request $request)
{
    $razon = $request->razon ?? null;

    $pivot = AlistamientoUsuario::where('alistamiento_id', $alistId)
        ->where('usuario_id', $userId)
        ->firstOrFail();

    // Guardar tiempo acumulado
    if ($pivot->inicio) {
        $pivot->tiempo_segundos += now()->diffInSeconds($pivot->inicio);
    }

    $pivot->estado = "PAUSADO";
    $pivot->pausado_en = now();
    $pivot->inicio = null;
    $pivot->razon = $razon;  // <-- SE GUARDA SOLO AQUÍ, EN EL PIVOT
    $pivot->save();

    return response()->json(["message" => "Usuario pausado"]);
}


public function reanudarUsuario($alistId, $userId)
{
    $pivot = AlistamientoUsuario::where('alistamiento_id', $alistId)
        ->where('usuario_id', $userId)
        ->firstOrFail();

    $pivot->estado = "EN_PROGRESO";
    $pivot->inicio = now();
    $pivot->pausado_en = null;
    $pivot->save();

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
