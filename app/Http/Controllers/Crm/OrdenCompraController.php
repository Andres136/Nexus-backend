<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Controllers\NotificacionOrdenController;
use App\Http\Requests\Crm\OrdenComprasRequest;
use App\Http\Requests\Crm\OrdenComprasUpdateRequest;
use App\Http\Requests\Crm\OrdenTrabajoRequest;
use App\Http\Requests\Crm\RequestMeta;
use App\Models\Crm\MetaMensual;
use App\Models\Crm\Orden_Compra;
use App\Models\Crm\OrdenDeTrabajo;
use App\Models\Crm\OrdenTrabajoEntrega;
use App\Models\Departamentos;
use App\Models\Estados;
use App\Models\User;
use App\Notifications\OrdenCompraNotificacion;
use App\Notifications\OrdenTrabajoCreada;
use App\Notifications\OrdenTrabajoGeneradaParaCreador;
use App\Notifications\OrdenTrabajoListaParcial;
use App\Services\ProductService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class OrdenCompraController extends Controller
{
    /**
     * Display a listing of the resource.
     */
     protected $productService;

    public function __construct()
    {
        $this->productService = app(ProductService::class);
    }


    public function verificarFaltantesPendientes()
    {
          $resultado = $this->productService->getFaltantesOrdenesPendientes();
    return response()->json($resultado);
    }

    public function index(Request $request)
    {
        $search = $request->input('search');

        // Obtener órdenes de compra con paginación y ordenarlas por fecha de creación (las más recientes primero)
        $ordenesCompra = Orden_Compra::with('detalles', 'cliente', 'user', 'estado','detalles.product')
            ->when($search, function ($query, $search) {
                return $query->whereHas('cliente', function ($query) use ($search) {
                    $query->where('nombre', 'LIKE', "%$search%");
                })->orWhere('fecha_entrega', 'LIKE', "%$search%");
            })
            ->orderBy('created_at', 'desc') // 🔹 Ordenar por fecha de creación más reciente
            ->paginate(10) // Mantiene la paginación
            ->appends(request()->query()); // Mantiene los parámetros de búsqueda en la URL


        return response()->json($ordenesCompra);
    }


    /**
     * Store a newly created resource in storage.
     */

    public function store(OrdenComprasRequest $request)
    {
        DB::beginTransaction();
        try {
            // Crear la orden sin valor total inicialmente
            $ordenCompra = Orden_Compra::create([
                'fecha_entrega' => $request->fecha_entrega,
                'cliente_id' => $request->cliente_id,
                'user_id' => auth()->id(),
                'estado_id' => 1,
                'ubicacion_entrega' => $request->ubicacion_entrega,
                'observaciones' => $request->observaciones,
                'valor_total' => 0, // Inicialmente 0

            ]);

            $valorTotal = 0;

            // ✅ Insertar detalles si existen
            if (!empty($request->detalles)) {
                $detalles = $ordenCompra->detalles()->createMany($request->detalles);

                // ✅ Calcular el valor total sumando los valores de los detalles recién creados
                $valorTotal = $ordenCompra->detalles()->sum('valor_total');
            }

            // ✅ Actualizar el valor total en la orden de compra
            $ordenCompra->update(['valor_total' => $valorTotal]);

            DB::commit();

            $notificacionController = app(NotificacionOrdenController::class);
            $notificacionController->enviarOrdenCreada($ordenCompra);


            return response()->json([
                'message' => 'Orden de compra creada con éxito',
                'orden_compra_id' => $ordenCompra->id,
                'orden_compra' => $ordenCompra->load('detalles'),
 
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Error al crear la orden: ' . $e->getMessage()], 500);
        }
    }



    public function generarOrdenTrabajo(OrdenTrabajoRequest $request, $id)
    {

       $user = auth()->user(); 
        try {
            $ordenCompra = Orden_Compra::findOrFail($id);
        // Verificar de dónde sacar el sede_id
        if ($request->filled('sede_id')) {
            // 1. Si viene en el request
            $sedeId = $request->input('sede_id');
        } elseif ($ordenCompra->sede_id) {
            // 2. Si ya está en la orden de compra
            $sedeId = $ordenCompra->sede_id;
        } elseif ($user->sede_id) {
            // 3. Si no hay en request ni en orden, usar la del usuario
            $sedeId = $user->sede_id;
        } else {
            // 4. Si no hay ninguna → error
            return response()->json(['error' => 'La sede es obligatoria y no se encontró en el request, en la orden o en el usuario'], 422);
        }

        // Asignar sede a la orden
        $ordenCompra->sede_id = $sedeId;
$ordenCompra->save();




            // 2. Crear u obtener la Orden de Trabajo asociada a la Orden de Compra
            $ordenTrabajo = OrdenDeTrabajo::firstOrCreate(
                ['orden_compra_id' => $ordenCompra->id], // Condición para "buscar" existente
                [
                    'cliente_id'    => $ordenCompra->cliente_id,
                    'user_id'       => $ordenCompra->user_id,  // Usuario actual
                    'fecha_entrega' => Carbon::parse($ordenCompra->fecha_entrega, 'America/Bogota'),
                    // Observaciones a nivel de Orden de Trabajo
                    'observaciones' => $request->input('observaciones', ''),
                    'valor_total'   => $ordenCompra->valor_total,
                    'estado_id'     => 1, // estado inicial
                    'fecha_despacho' => null, // Inicialmente nulo



                ]
            );

            $fueCreada = $ordenTrabajo->wasRecentlyCreated;

            // Variables para revisar si la orden queda completa
            $ordenCompleta = true;
            $totalFaltantes = 0;

            // 3. Procesar los detalles recibidos
            if ($request->has('detalles')) {
                foreach ($request->input('detalles') as $detalleData) {

                    // a) Determinar si se está ACTUALIZANDO un detalle o CREANDO uno nuevo
                    if (!empty($detalleData['id'])) {
                        // BUSCAR DETALLE EXISTENTE
                        $detalle = $ordenCompra->detalles()->find($detalleData['id']);

                        // Si no se encuentra, podrías ignorarlo o lanzar excepción
                        if (!$detalle) {
                            continue;
                        }

                        // PREPARAR CAMPOS A ACTUALIZAR
                        // Se incluyen todos los campos que pueden cambiar en un detalle existente
                        $updatedFields = [
                            'product_id'    => $detalleData['product_id']    ?? $detalle->product_id,
                            'largo_cm'       => $detalleData['largo_cm']       ?? $detalle->largo_cm,
                            'ancho_cm'       => $detalleData['ancho_cm']       ?? $detalle->ancho_cm,
                            'calibre'        => $detalleData['calibre']        ?? $detalle->calibre,
                            'cliente_clb'    => $detalleData['cliente_clb']    ?? $detalle->cliente_clb,
                            'peso_bolsa'     => $detalleData['peso_bolsa']     ?? $detalle->peso_bolsa,
                            'numero_bolsas'  => $detalleData['numero_bolsas']  ?? $detalle->numero_bolsas,
                            'cantidad_requerida_kg' => $detalleData['cantidad_requerida_kg'] ?? $detalle->cantidad_requerida_kg,
                            'descripcion'    => $detalleData['descripcion']    ?? $detalle->descripcion,
                            'cantidad'       => $detalleData['cantidad']       ?? $detalle->cantidad,
                            'observaciones'  => $detalleData['observaciones']  ?? $detalle->observaciones,
                            'valor_unitario' => $detalleData['valor_unitario'] ?? $detalle->valor_unitario,
                            'valor_total'    => $detalleData['valor_total']    ?? $detalle->valor_total,

                            // 'observaciones' => $detalleData['observaciones'] ?? $detalle->observaciones, // si lo requieres
                        ];
                        // b) Manejo de cantidad enviada y faltantes (siempre definido)
                        $nuevaCantidadEnviada    = (int) ($detalleData['cantidad_enviada'] ?? 0);
                        $cantidadAnteriorEnviada = (int) $detalle->cantidad_enviada;
                        // Usa el valor nuevo de cantidad si viene en la petición; si no, el actual
                        $cantidadRequerida       = (int) ($detalleData['cantidad'] ?? $detalle->cantidad);

                        // Calcula siempre totales y faltantes
                        $totalCantidadEnviada = min($cantidadAnteriorEnviada + $nuevaCantidadEnviada, $cantidadRequerida);
                        $faltantes            = max(0, $cantidadRequerida - $totalCantidadEnviada);

                        // Actualiza campos calculados
                        $updatedFields['cantidad_enviada'] = $totalCantidadEnviada;
                        $updatedFields['faltantes']        = $faltantes;

                        // Si hay faltantes, la orden no está completa
                        if ($faltantes > 0) {
                            $ordenCompleta = false;
                        }
                        $totalFaltantes += $faltantes;

                        // Registrar historial solo si hay envío nuevo
                        if ($nuevaCantidadEnviada > 0) {
                            OrdenTrabajoEntrega::create([
                                'orden_trabajo_id' => $ordenTrabajo->id,
                                'detalle_id'       => $detalle->id,
                                'cantidad'         => $nuevaCantidadEnviada,
                                'faltante'         => $faltantes,
                                'fecha_entrega'    => Carbon::now('America/Bogota'),
                                'usuario_id'       => auth()->id(),
                                'observaciones'    => $detalleData['observaciones'] ?? '',
                            ]);
                        }

                        // Finalmente, actualizamos el detalle
                        $detalle->update($updatedFields);
                    } else {
                        // CREAR UN NUEVO DETALLE
                        $detalle = $ordenCompra->detalles()->create([
                            'product_id'    => $detalleData['product_id']    ?? null,
                            'largo_cm'       => $detalleData['largo_cm']       ?? 0,
                            'ancho_cm'       => $detalleData['ancho_cm']       ?? 0,
                            'calibre'        => $detalleData['calibre']        ?? 0,
                            'cliente_clb'    => $detalleData['cliente_clb']    ?? 0,
                            'peso_bolsa'     => $detalleData['peso_bolsa']     ?? 0,
                            'numero_bolsas'  => $detalleData['numero_bolsas']  ?? 0,
                            'descripcion'    => $detalleData['descripcion']    ?? '',
                            'cantidad'       => $detalleData['cantidad']       ?? 0,
                            'cantidad_requerida_kg' => $detalleData['cantidad_requerida_kg'] ?? 0,
                            'valor_unitario' => $detalleData['valor_unitario'] ?? 0,
                            'valor_total'    => $detalleData['valor_total']    ?? 0,


                            // 'observaciones' => $detalleData['observaciones'] ?? '', // si lo requieres
                        ]);




                        // Manejo de cantidad_enviada y faltantes para el nuevo detalle
                        $nuevaCantidadEnviada = (int) ($detalleData['cantidad_enviada'] ?? 0);
                        $cantidadRequerida    = (int) ($detalleData['cantidad'] ?? 0);

                        // Calculamos
                        $totalCantidadEnviada = min($nuevaCantidadEnviada, $cantidadRequerida);
                        $faltantes = max(0, $cantidadRequerida - $totalCantidadEnviada);

                        // Actualizamos el nuevo registro con estos valores
                        $detalle->update([
                            'cantidad_enviada' => $totalCantidadEnviada,
                            'faltantes'        => $faltantes,
                        ]);
                        // Registrar historial solo si se envió algo
                        if ($nuevaCantidadEnviada > 0) {
                            OrdenTrabajoEntrega::create([
                                'orden_trabajo_id' => $ordenTrabajo->id,
                                'detalle_id'       => $detalle->id,
                                'cantidad'         => $nuevaCantidadEnviada,
                                'faltante'         => $faltantes,
                                'fecha_entrega'    => Carbon::now('America/Bogota'),
                                'usuario_id'       => auth()->id(),
                                'observaciones'    => $detalleData['observaciones'] ?? '',
                            ]);
                        }
                        // Si hay faltantes, la orden no está completa
                        if ($faltantes > 0) {
                            $ordenCompleta = false;
                        }
                        $totalFaltantes += $faltantes;
                    }
                }
            }

            $fueCreada = $ordenTrabajo->wasRecentlyCreated;

            // 4. Actualizar la Orden de Trabajo con la suma de faltantes
            $ordenTrabajo->update([
                'faltantes' => $totalFaltantes,
                'observaciones' => $request->input('observaciones', '')
            ]);

            // 5. Notificaciones
            $user = $ordenCompra->user;
            //notificar  a usuarios por sedes 
            // 5. Notificaciones de Orden de Trabajo Creada - SISTEMA MEJORADO

            // Obtener IDs de departamentos
            $operacionesId = Departamentos::where('nombre', 'Operaciones')->value('id');


            // 1. Notificar al usuario que creó la orden de compra (mensaje específico)
            if ($ordenCompra->user) {
                $ordenCompra->user->notify(new OrdenTrabajoGeneradaParaCreador($ordenTrabajo));
            }

            // 2. Notificar a usuarios de Inventario de la sede específica para preparar materiales
            $usuariosInventarioSede = User::where('sede_id', $ordenCompra->sede_id)
                ->where('departamento_id', $operacionesId)
                ->where('role_id', 6)
                ->whereNotNull('email') // Solo usuarios con email válido
                ->get();

            if ($usuariosInventarioSede->count() > 0) {
                Notification::send($usuariosInventarioSede, new OrdenTrabajoCreada($ordenTrabajo));
            }

            
            // Log para seguimiento
            Log::info('Notificaciones de Orden de Trabajo enviadas', [
                'orden_trabajo_id' => $ordenTrabajo->id,
                'orden_compra_id' => $ordenCompra->id,
                'usuario_creador' => $ordenCompra->user->name ?? 'N/A',
                'inventario_notificados' => $usuariosInventarioSede->count(),
                'sede' => $ordenCompra->sede->nombre ?? 'N/A'
            ]);


            // 6. Determinar y actualizar el estado final
            $estadoPendiente = Estados::where('nombre', 'Pendiente')->first()->id;
            $estadoParcial   = Estados::where('nombre', 'Entrega Parcial')->first()->id;
            $estadoCompleto  = Estados::where('nombre', 'Completado')->first()->id;

            $totalSolicitado = $ordenCompra->detalles()->sum('cantidad');
            $totalEntregado  = $ordenCompra->detalles()->sum('cantidad_enviada');
            $forzarParcial   = $request->boolean('forzar_entrega_parcial');

            if ($ordenCompleta) {
                $nuevoEstado = $estadoCompleto;
                if(!$ordenCompra->fecha_despacho){
                    $ordenCompra->fecha_despacho = Carbon::now('America/Bogota');
                }
            } elseif ($forzarParcial || ($totalEntregado > 0 && $totalEntregado < $totalSolicitado)) {
                $nuevoEstado = $estadoParcial;
            } else {
                $nuevoEstado = $estadoPendiente;
            }

            $ordenTrabajo->update(['estado_id' => $nuevoEstado]);
            $ordenCompra->update(['estado_id' => $nuevoEstado]);

            // Solo notificar si hay productos alistados
           if ($user && $totalFaltantes < $ordenCompra->detalles->sum('cantidad')) {
    $faltantes = $ordenCompleta ? 0 : $totalFaltantes;
    $user->notify(new OrdenTrabajoListaParcial($ordenTrabajo, $faltantes));
}

 // 🔹 GENERAR Y GUARDAR PDF AUTOMÁTICAMENTE
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.orden_trabajo', [
                'orden'        => $ordenTrabajo->load([
                    'ordenCompra.detalles.product',
                    'ordenCompra.estado',
                    'cliente',
                    'user',
                    'entregas.usuario'
                ]),
                'detalles'     => $ordenCompra->detalles,
                'totalKg'      => $ordenCompra->detalles->sum('cantidad_requerida_kg'),
                'valorTotal'   => $ordenCompra->valor_total,
                'observaciones'=> $ordenTrabajo->observaciones ?? 'Sin observaciones',
                'observaciones_oc'=> $ordenCompra->observaciones ?? 'Sin observaciones',
            ]);

            $fileName = "ordenes_trabajo/orden_trabajo_{$ordenTrabajo->id}.pdf";
            Storage::disk('public')->put($fileName, $pdf->output());

            $ordenTrabajo->update(['pdf_path' => $fileName]);


           

            return response()->json([
                'message'      => 'Orden de Trabajo generada/actualizada con éxito',
                'ordenTrabajo' => $ordenTrabajo,
                'pdf_url'      => asset("storage/{$fileName}"),
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al generar/actualizar la Orden de Trabajo',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }



    public function obtenerOrdenesTrabajo(Request $request)
    {
        $search = $request->input('search');
        $fecha  = $request->input('fecha');
        $sedeId = $request->input('sede');

        $user = auth()->user();

        $ordenesTrabajo = OrdenDeTrabajo::with([
            'ordenCompra.sede',
            'ordenCompra.usuario',
            'ordenCompra',
            'cliente',
            'estado',
            'user',
            'entregas.usuario:id,name',
            'movimientosStock:id,orden_trabajo_id,created_at,usuario_id',
        ])
            ->when(!in_array($user->role_id, [1,4]), function ($query) use ($user) {
    $query->whereHas('ordenCompra', function ($q) use ($user) {
        $q->where(function ($sub) use ($user) {
            $sub->where('sede_id', $user->sede_id)
                ->orWhereNull('sede_id'); // ✅ incluir órdenes sin sede
        });
    });
})

            ->when($sedeId, function ($query, $sedeId) {
                $query->whereHas('ordenCompra', function ($q) use ($sedeId) {
                    $q->where('sede_id', $sedeId);
                });
            })
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    // Buscar por nombre del cliente
                    $q->whereHas('cliente', function ($q2) use ($search) {
                        $q2->where('nombre', 'LIKE', "%$search%");
                    })
                        // Buscar por nombre de la sede
                        ->orWhereHas('ordenCompra.sede', function ($q3) use ($search) {
                            $q3->where('nombre', 'LIKE', "%$search%");
                        })
                        // Buscar por ID de orden si se escribe un número exacto
                        ->orWhere('id', $search);
                });
            })

            ->when($fecha, function ($query, $fecha) {
                $query->whereHas('ordenCompra', function ($q) use ($fecha) {
                    $q->whereDate('fecha_entrega', $fecha);
                });
            })
            
            ->orderByRaw("CASE WHEN estado_id = 1 THEN 0 ELSE 1 END")
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->appends(request()->query());



        return response()->json($ordenesTrabajo);
    }


    //Traer Entregas
    public function obtenerEntregas(Request $request)
    {
        $id = $request->route('id');

        $entregas = OrdenTrabajoEntrega::with(['usuario:id,name', 'detalle:id,descripcion'])
            ->where('orden_trabajo_id', $id)
            ->orderBy('created_at', 'asc') // historial cronológico
            ->get();

        return response()->json([
            'orden_trabajo_id' => $id,
            'total'            => $entregas->count(),
            'entregas'         => $entregas
        ]);
    }

    /**
     * Enviar notificación a los usuarios con el rol de inventarios y al usuario que creó la orden de compra y orden de trabajo que la esta vencida 
     */
    public function show(string $id)
    {
        // 1. Buscar la orden de compra con sus detalles y usuario asociado
        $orden = Orden_Compra::with('detalles', 'user')->findOrFail($id);

        // 2. Verificar si la orden NO está completada (estado_id !== 2)
        if ($orden->estado_id !== 2) {
            $fechaEntrega = Carbon::parse($orden->fecha_entrega);
            $hoy = Carbon::now();
            $dosDiasAntes = $fechaEntrega->subDays(2); // Resta 2 días antes de la entrega

            // 3. Si la orden está vencida o faltan 2 días para vencer, enviamos notificación
            if ($hoy->greaterThanOrEqualTo($dosDiasAntes)) {
                // Buscar usuarios con los roles: Administrativo, Compras e Inventario
                $usuariosNotificar = User::whereHas('roles', function ($query) {
                    $query->whereIn('name', ['Administrativo', 'Compras', 'Inventario']);
                })->get();

                // Enviar notificación a los usuarios seleccionados
                Notification::send($usuariosNotificar, new OrdenCompraNotificacion($orden));

                // Verificar que la orden tenga un usuario antes de notificarlo
                if ($orden->user) {
                    $orden->user->notify(new OrdenCompraNotificacion($orden));
                }

                // 4. (Opcional) Marcar la orden como notificada para no repetir notificación
                if (!$orden->notificado_vencida) {
                    $orden->notificado_vencida = true;
                    $orden->save();
                }
            }
        }

        // 5. Retornar la orden al frontend
        return response()->json($orden, 200);
    }

    /**
     * Update the specified resource in storage.
     */


    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            /** @var Orden_Compra $oc */
            $oc = Orden_Compra::with('ordenTrabajo')->findOrFail($id);

            /*⛔️ Bloqueamos la edición si ya existe OT
            if ($oc->ordenTrabajo) {
                return response()->json([
                    'error' => 'La Orden de Trabajo ya fue generada; esta OC no puede modificarse.'
                ], 422);
            }*/

            // 1. Actualizar cabecera
            $oc->update([
                'fecha_entrega'     => $request->fecha_entrega,
                'cliente_id'        => $request->cliente_id,
                'ubicacion_entrega' => $request->ubicacion_entrega,
                'observaciones'     => $request->observaciones,

            ]);

            // 2. Reconciliar detalles  (3 estrategias posibles)
            //    A) destruir todos y volver a crear
            //    B) upsert por ID (con createMany/update/delete faltantes)  ✅
            //    C) solo updateCampos permitidos
            $idsEnRequest = collect($request->detalles)->pluck('id')->filter()->all();

            // B-1) Eliminar detalles que ya no vienen
            $oc->detalles()->whereNotIn('id', $idsEnRequest)->delete();

            foreach ($request->detalles as $d) {
                $oc->detalles()->updateOrCreate(
                    ['id' => $d['id'] ?? null],
                    [
                        'largo_cm'       => $d['largo_cm'],
                        'ancho_cm'       => $d['ancho_cm'],
                        'calibre'        => $d['calibre'],
                        'cliente_clb'    => $d['cliente_clb']    ?? 0,
                        'peso_bolsa'     => $d['peso_bolsa']     ?? 0,
                        'numero_bolsas'  => $d['numero_bolsas']  ?? 0,
                        'cantidad_requerida_kg' => $d['cantidad_requerida_kg'] ?? 0,
                        'descripcion'    => $d['descripcion']    ?? '',
                        'cantidad'       => $d['cantidad'],
                        'valor_unitario' => $d['valor_unitario'] ?? 0,
                        'valor_total'    => $d['valor_total']    ?? 0,
                        'observaciones'  => $d['observaciones']  ?? '',
                    ]
                );
            }

            // 3. Recalcular valor_total
            $valorTotal = $oc->detalles()->sum('valor_total');
            $oc->update(['valor_total' => $valorTotal]);

            DB::commit();

            return response()->json([
                'message'      => 'Orden de compra actualizada',
                'orden_compra' => $oc->load('detalles')
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'No se pudo actualizar la OC: ',
                'message' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ], 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $ordenCompra = Orden_Compra::findOrFail($id);

        if ($ordenCompra->estado_id !== 1) {
            return response()->json([
                'error' => 'Solo se pueden eliminar órdenes en estado Pendiente.'
            ], 403);
        }

        $ordenCompra->detalles()->delete(); // Eliminar detalles asociados
        $ordenCompra->delete(); // Eliminar la orden de compra

        return response()->json(['message' => 'Orden de compra eliminada con éxito'], 200);
    }


    //Listar todas las ordenes de compra con cantidad enviada para facturar
    public function ordenesFacturar(Request $request)
    {
        $search = $request->input('search');

        // Obtener órdenes de compra con detalles, cliente, usuario y estado
        $ordenesCompra = Orden_Compra::with(['detalles', 'cliente', 'user', 'estado', 'ordenesTrabajo'])
            ->whereHas('detalles', function ($query) {
                // Filtrar solo las órdenes con cantidad enviada mayor a 0
                $query->where('cantidad_enviada', '>', 0);
            })
            ->when($search, function ($query, $search) {
                $query->whereHas('cliente', function ($query) use ($search) {
                    $query->where('nombre', 'LIKE', "%$search%");
                })->orWhere('fecha_entrega', 'LIKE', "%$search%")
                    ->orWhereHas('user', function ($query) use ($search) {
                        $query->where('name', 'LIKE', "%$search%");
                    });
            })
            ->orderBy('created_at', 'desc') // Ordenar por fecha de creación (más reciente)
            ->paginate(5) // Paginación
            ->appends(request()->query()); // Mantiene parámetros en la URL

        return response()->json($ordenesCompra);
    }

    public function generarPDF(Request $request, $id)
    {

        $orden = Orden_Compra::with('cliente', 'detalles', 'usuario')->findOrFail($id);

        $pdf = Pdf::loadView('pdf.orden_compra', compact('orden'));

        return $pdf->download("orden_compra_{$orden->id}.pdf");
    }




    public function misOrdenes(Request $request)
    {
        $search = $request->input('search');

        $ordenes = Orden_Compra::with('detalles', 'cliente', 'user', 'estado')
            ->where('user_id', auth()->id())
            ->when($search, function ($query, $search) {
                return $query->whereHas('cliente', function ($q) use ($search) {
                    $q->where('nombre', 'like', "%$search%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(5)
            ->appends(request()->query());

        return response()->json($ordenes);
    }

    public function edit(string $id)
    {
        $orden = Orden_Compra::with('detalles', 'cliente', 'user', 'estado')
            ->findOrFail($id);

        return response()->json($orden, 200);
    }




    public function registrarMeta(RequestMeta $request)
    {
        $meta = MetaMensual::updateOrCreate(
            ['anio' => $request->anio, 'mes' => $request->mes],
            ['valor_meta' => $request->valor_meta]
        );
        return response()->json([
            'message' => 'Meta mensual registrada exitosamente',
            'meta' => $meta
        ], 201);
    }
    public function graficoMetaMensual(Request $request)
    {
        $anio = $request->input('anio', now()->year);

        // Traer todas las metas registradas en el año
        $metas = MetaMensual::where('anio', $anio)
            ->orderBy('mes')
            ->get();

        if ($metas->isEmpty()) {
            return response()->json(['error' => 'No hay metas registradas para este año.'], 404);
        }

        $data = $metas->map(function ($meta) use ($anio) {
            // Obtener valor total de órdenes del mes
            $ordenesDelMes = Orden_Compra::whereYear('created_at', $anio)
                ->whereMonth('created_at', $meta->mes)
                ->sum('valor_total');

            // Calcular cumplimiento
            $cumplimiento = $meta->valor_meta > 0
                ? round(($ordenesDelMes / $meta->valor_meta) * 100, 2)
                : 0;

            return [
                'mes' => ucfirst(Carbon::create()->month($meta->mes)->locale('es')->isoFormat('MMM')),
                'meta_millones' => (float) $meta->valor_meta,
                'ordenes_millones' => round($ordenesDelMes, 2),
                'cumplimiento' => $cumplimiento,
            ];
        });

        return response()->json([
            'anio' => $anio,
            'data' => $data,
        ]);
    }

public function ordenesTrabajoEntregas(Request $request)
{
    $search = $request->input('search');

    // IDs de estados necesarios
    $estadoCompletado = Estados::where('nombre', 'Completado')->value('id');
    $estadoParcial    = Estados::where('nombre', 'Entrega Parcial')->value('id');

    $ordenes = OrdenDeTrabajo::with([
        'ordenCompra.cliente',
        'ordenCompra',
        'estado',
        'entregas'
    ])
    ->where(function ($q) use ($estadoCompletado, $estadoParcial) {
        $q->where('estado_id', $estadoCompletado)         // completadas
          ->orWhere('estado_id', $estadoParcial)          // parciales
          ->orWhereHas('ordenCompra.detalles', function ($d) {
              $d->where('cantidad_enviada', '>', 0);      // cantidad enviada > 0
          });
    })
    ->when($search, function ($q) use ($search) {
        $q->whereHas('ordenCompra.cliente', function ($c) use ($search) {
            $c->where('nombre', 'LIKE', "%$search%");
        });
    })
    ->orderBy('updated_at', 'desc')
    ->limit(50)
    ->get();

    return response()->json($ordenes, 200);
}


  
}