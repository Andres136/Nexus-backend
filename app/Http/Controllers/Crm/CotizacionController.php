<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\CotizacionRequest;
use App\Models\Crm\Cotizacion;
use App\Models\Crm\CotizacionDetalles;
use App\Models\Crm\empresa;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\Crm\CotizacionCalculoService;

class CotizacionController extends Controller
{
    public function __construct(private readonly CotizacionCalculoService $calculo) {}
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CotizacionRequest $request)
    {
        DB::beginTransaction();

        try {
            // 1. Preparamos la colección de detalles con cálculos
            $detalles = collect($request->input('detalles', []))
                ->map(function (array $detalle, int $index) {
                    return array_merge($detalle, ['item' => $index + 1], $this->calculo->calcular(
                        $detalle,
                        (float) ($detalle['iva_porcentaje'] ?? 19)
                    ));
                });
    
            // 2. Sumamos el total global
            $valorTotalGlobal = $detalles->sum('valor_total');
    
            // 3. Creamos la cabecera de la cotización
            $empresa = empresa::findOrFail($request->empresa_id);
            $cotizacion = Cotizacion::create([
                'cliente_id'    => $request->cliente_id,
                'empresa_id'    => $empresa->id,
                'empresa'       => $empresa->nombre,
                'observaciones' => $request->observaciones,
                'user_id'       =>  auth()->id(),
                'valor_total'   => $valorTotalGlobal,
            ]);
    
            // 4. Creamos los detalles en masa usando la relación
            $cotizacion->detalles()->createMany($detalles->toArray());
    
            DB::commit();
    
            return response()->json([
                'message'    => 'Cotización registrada correctamente',
                'cotizacion' => $cotizacion->load('detalles'),
            ], 201);
    
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error al registrar cotización', ['exception' => $e]);
            return response()->json([
                'error' => 'Error al registrar la cotización',
            ], 500);
        }
        
    }

    /**
     * Display the specified resource.
     */


     public function descargarPDF($id)
     {
         $cotizacion = Cotizacion::with('cliente', 'user', 'detalles', 'empresaReal')->findOrFail($id);
         abort_if($cotizacion->chatbot_conversacion_id && $cotizacion->estado_aprobacion !== 'aprobada', 403, 'La cotización debe ser aprobada antes de generar el PDF.');
 
         $logo = $cotizacion->empresaReal?->logo
             ? storage_path('app/public/' . $cotizacion->empresaReal->logo)
             : public_path('images/SETAS.png');
 
         $pdf = Pdf::loadView('pdf.cotizacion', [
             'cotizacion' => $cotizacion,
             'logo' => $logo,
         ]);
 
         return $pdf->download("Cotizacion_{$cotizacion->id}.pdf");
     }

    public function show(string $id)
    {
        $cotizacion = Cotizacion::with(['detalles', 'cliente', 'user', 'empresaReal'])->findOrFail($id);
        return response()->json($cotizacion);
  
    }
    public function misCotizaciones(Request $request)
    {
        $user = auth()->user();
        $search = $request->input('search');
        
        $cotizaciones = Cotizacion::with('cliente')
            ->where('user_id', $user->id)
            ->when($search, function ($query, $search) {
                $query->whereHas('cliente', fn($q) => $q->where('nombre', 'like', "%$search%"));
            })
            ->orderByDesc('created_at')
            ->paginate(10);
    
        return response()->json($cotizaciones);
    }
    
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
{
    $cotizacion = Cotizacion::findOrFail($id);
    if ($cotizacion->chatbot_conversacion_id) {
        abort_unless($cotizacion->user_id === auth()->id(), 403, 'Solo quien creó la cotización puede editarla.');
        abort_if($cotizacion->estado_aprobacion === 'aprobada', 422, 'Una cotización aprobada no se puede modificar.');
    }
    $datosCabecera = $request->validate([
        'cliente_id' => 'required|exists:clientes,id',
        'empresa_id' => 'required|integer|exists:empresas,id',
        'observaciones' => 'nullable|string',
        'detalles' => 'required|array|min:1',
    ]);
    $empresa = empresa::findOrFail($datosCabecera['empresa_id']);

    DB::beginTransaction();

    try {
        // 1. Actualiza cabecera
        $cotizacion->update([
            'empresa_id'    => $empresa->id,
            'empresa'       => $empresa->nombre,
            'observaciones' => $request->observaciones,
            'cliente_id'    => $request->cliente_id,
        ]);

        // 2. Obtener IDs de los detalles recibidos
        $detallesRequest = collect($request->input('detalles', []));
        $idsRecibidos = $detallesRequest->pluck('id')->filter()->all();

        // 3. Eliminar detalles que ya no están
        $cotizacion->detalles()->whereNotIn('id', $idsRecibidos)->delete();

        $valorTotalGlobal = 0;

        // 4. Procesar cada detalle
        foreach ($detallesRequest as $index => $detalle) {
            $valores = $this->calculo->calcular($detalle, (float) ($detalle['iva_porcentaje'] ?? 19));

            $data = [
                'item'           => $index + 1,
                'descripcion'    => mb_strtoupper($detalle['descripcion'] ?? ''),
                'ancho_cm'       => $detalle['ancho_cm'] ?? null,
                'largo_cm'       => $detalle['largo_cm'] ?? null,
                'calibre'        => $detalle['calibre'] ?? null,
                'peso_bolsa'     => $valores['peso_bolsa'],
                'numero_bolsas'  => $valores['numero_bolsas'],
                'precio_total'   => $valores['precio_total'],
                'valor_unitario' => $valores['valor_unitario'],
                'valor_total'    => $valores['valor_total'],
                'valor_paquete'  => $valores['valor_paquete'],
                'cantidad'       => $detalle['cantidad'] ?? 0,
                'cliente_clb'    => $detalle['cliente_clb'] ?? null,
                'cantidad_requerida_kg' => $detalle['cantidad_requerida_kg'] ?? null,
                'observaciones'  => $detalle['observaciones'] ?? null,
            ];

            if (!empty($detalle['id'])) {
                // Si existe, actualiza
                CotizacionDetalles::where('id', $detalle['id'])->update($data);
            } else {
                // Si es nuevo, crea
                $cotizacion->detalles()->create($data);
            }

            $valorTotalGlobal += $valores['valor_total'];
        }

        // 5. Actualiza total global
        $cotizacion->update(array_filter([
            'valor_total' => $valorTotalGlobal,
            'estado_aprobacion' => $cotizacion->chatbot_conversacion_id ? 'pendiente' : null,
            'aprobado_por' => $cotizacion->chatbot_conversacion_id ? null : $cotizacion->aprobado_por,
            'aprobado_at' => $cotizacion->chatbot_conversacion_id ? null : $cotizacion->aprobado_at,
            'motivo_rechazo' => $cotizacion->chatbot_conversacion_id ? null : $cotizacion->motivo_rechazo,
        ], static fn ($value, $key) => $key === 'valor_total' || $cotizacion->chatbot_conversacion_id || $value !== null, ARRAY_FILTER_USE_BOTH));

        DB::commit();

        return response()->json([
            'message' => 'Cotización actualizada correctamente',
            'cotizacion' => $cotizacion->load('detalles'),
        ]);

    } catch (\Throwable $e) {
        DB::rollBack();
        Log::error('Error al actualizar cotización', ['exception' => $e]);
        return response()->json([
            'error' => 'Error al actualizar la cotización',
        ], 500);
    }
}


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
