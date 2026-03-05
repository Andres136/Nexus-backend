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
use App\Services\Crm\OrdenCompraService;
use App\Services\Crm\OrdenTrabajoService;
use App\Services\ProductService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class OrdenCompraController extends Controller
{
    /**
     * Display a listing of the resource.
     */
     protected $productService;
     protected $ordenTrabajoService;
     

    public function __construct()
    {
        $this->productService = app(ProductService::class);
        $this->ordenTrabajoService = app(OrdenTrabajoService::class);
    }


    public function verificarFaltantesPendientes()
    {
          $resultado = $this->productService->getFaltantesOrdenesPendientes();
    return response()->json($resultado);
    }

  public function index(Request $request)
{
    $search = $request->input('search');

    $ordenesCompra = Orden_Compra::with(
            'detalles',
            'cliente',
            'user',
            'estado',
            'detalles.product',
            'ordenTrabajo'
        )
        ->withExists('ordenTrabajo') // ✅ agrega flag booleano
        ->when($search, function ($query, $search) {
            return $query->whereHas('cliente', function ($query) use ($search) {
                $query->where('nombre', 'LIKE', "%$search%");
            })->orWhere('fecha_entrega', 'LIKE', "%$search%");
        })
        ->orderBy('orden_trabajo_exists', 'asc') // ✅ primero sin OT
        ->orderBy('created_at', 'desc') // ✅ más recientes dentro del grupo
        ->paginate(10)
        ->appends(request()->query());

    return response()->json($ordenesCompra);
}



    /**
     * Store a newly created resource in storage.
     */

    public function store(OrdenComprasRequest $request)
    {
        DB::beginTransaction();
    
   

        try {

            // Manejo de archivo subido
            $rutaArchivo = null;
            if ($request->hasFile('cliente_documento')) {
                $nombreArchivo = $request->file('cliente_documento')->getClientOriginalName();
            $rutaArchivo = $request->file('cliente_documento')
    ->storeAs('documentos_clientes', $nombreArchivo, 'public');

            }

            // Crear la orden sin valor total inicialmente
            $ordenCompra = Orden_Compra::create([
                'fecha_entrega' => $request->fecha_entrega,
                'cliente_id' => $request->cliente_id,
                'user_id' => auth()->id(),
                'estado_id' => 1,
                'ubicacion_entrega' => $request->ubicacion_entrega,
                'observaciones' => $request->observaciones,
                'empresa_id' => $request->empresa_id,
                'cliente_documento' => $rutaArchivo,
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
        
        $this->validarDocumentoCliente($ordenCompra);
        
        // Determinar sede (mantener lógica exacta)
        $sedeId = $this->determinarSede($request, $ordenCompra, $user);
        $ordenCompra->sede_id = $sedeId;
        $ordenCompra->save();
        
        // ✅ Usar el service con toda la lógica
        $ordenTrabajoService = app(OrdenTrabajoService::class);
        $resultado = $ordenTrabajoService->generarOrdenTrabajo(
            $ordenCompra, 
            $request->all(), // Pasar todos los datos del request
            $user->id
        );
        
        return response()->json([
            'message' => 'Orden de Trabajo generada/actualizada con éxito',
            'ordenTrabajo' => $resultado['ordenTrabajo'],
            'pdf_url' => $resultado['pdf_url'],
        ], 201);
        
    } catch (Exception $e) {
        return response()->json([
            'message' => 'Error al generar/actualizar la Orden de Trabajo',
            'error' => $e->getMessage(),
        ], 500);
    }
}

private function determinarSede($request, $ordenCompra, $user)
{
    if ($request->filled('sede_id')) {
        return $request->input('sede_id');
    } elseif ($ordenCompra->sede_id) {
        return $ordenCompra->sede_id;
    } elseif ($user->sede_id) {
        return $user->sede_id;
    } else {
        throw new \Exception('La sede es obligatoria y no se encontró en el request, en la orden o en el usuario');
    }
}
private function validarDocumentoCliente(Orden_Compra $ordenCompra): void
{
    $existeOT = OrdenDeTrabajo::where('orden_compra_id', $ordenCompra->id)->exists();

    if (!$existeOT && $ordenCompra->cliente_documento && !$ordenCompra->documento_revisado_at) {
        throw ValidationException::withMessages([
            'documento' => 'Debe revisar el documento del cliente antes de generar la Orden de Trabajo.'
        ]);
    }
}



public function obtenerOrdenesTrabajo(Request $request)
{
    $search        = $request->input('search');
    $fecha         = $request->input('fecha');           // filtro exacto
    $fechaInicio   = $request->input('fecha_inicio');    // rango desde
    $fechaFin      = $request->input('fecha_fin');       // rango hasta
    $sedeId        = $request->input('sede');

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

        // Restricción por rol (excepto admin)
        ->when(!in_array($user->role_id, [1, 4]), function ($query) use ($user) {
            $query->whereHas('ordenCompra', function ($q) use ($user) {
                $q->where(function ($sub) use ($user) {
                    $sub->where('sede_id', $user->sede_id)
                        ->orWhereNull('sede_id');
                });
            });
        })

        // Filtro por sede enviada
        ->when($sedeId, function ($query, $sedeId) {
            $query->whereHas('ordenCompra', function ($q) use ($sedeId) {
                $q->where('sede_id', $sedeId);
            });
        })

        // Filtro por texto
        ->when($search, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('cliente', function ($q2) use ($search) {
                    $q2->where('nombre', 'LIKE', "%{$search}%");
                })
                ->orWhereHas('ordenCompra.sede', function ($q3) use ($search) {
                    $q3->where('nombre', 'LIKE', "%{$search}%");
                })
                ->orWhere('id', $search);
            });
        })

        // Filtro por fecha EXACTA (si la usan aún)
        ->when($fecha, function ($query, $fecha) {
            $query->whereHas('ordenCompra', function ($q) use ($fecha) {
                $q->whereDate('fecha_entrega', $fecha);
            });
        })

        // Filtro por rango: fecha_inicio + fecha_fin
        ->when($fechaInicio && $fechaFin, function ($query) use ($fechaInicio, $fechaFin) {
            $query->whereHas('ordenCompra', function ($q) use ($fechaInicio, $fechaFin) {
                $q->whereBetween('fecha_entrega', [$fechaInicio, $fechaFin]);
            });
        })

        // Solo fecha_inicio
        ->when($fechaInicio && !$fechaFin, function ($query) use ($fechaInicio) {
            $query->whereHas('ordenCompra', function ($q) use ($fechaInicio) {
                $q->whereDate('fecha_entrega', '>=', $fechaInicio);
            });
        })

        // Solo fecha_fin
        ->when(!$fechaInicio && $fechaFin, function ($query) use ($fechaFin) {
            $query->whereHas('ordenCompra', function ($q) use ($fechaFin) {
                $q->whereDate('fecha_entrega', '<=', $fechaFin);
            });
        })

        // Ordenar por pendiente primero
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
            ->paginate(10) // Paginación
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
    $search = trim($request->input('search'));

    $ordenes = OrdenDeTrabajo::query()
        ->when(!empty($search), function ($q) use ($search) {
            // 🔎 Search SOLO por OT
            if (is_numeric($search)) {
                $q->where('id', $search);
            } else {
                $q->where('codigo', 'LIKE', "%{$search}%"); // si existe
            }
        })
        ->orderBy('id', 'desc') // o id desc
       ->limit(50)
        ->get();

    return response()->json($ordenes, 200);
}

public function previewDocumento(Orden_Compra $orden, OrdenCompraService $ordenCompraService)
{

    return $ordenCompraService->obtenerDocumentoPreview($orden);
}
public function mostrarDocumentoFirmado(Orden_Compra $orden)
{
    $path = $orden->cliente_documento;

    abort_if(
        !$path || !Storage::disk('public')->exists($path),
        404
    );

    return response(
        Storage::disk('public')->get($path),
        200,
        [
            'Content-Type' => Storage::disk('public')->mimeType($path),
            'Content-Disposition' => 'inline',
            'X-Content-Type-Options' => 'nosniff',
        ]
    );
}
public function marcarDocumentoRevisado($id)
{
    $orden = Orden_Compra::findOrFail($id);
    $orden->documento_revisado_at = now();
    $orden->save();

    return response()->json(['ok' => true]);
}




  
}