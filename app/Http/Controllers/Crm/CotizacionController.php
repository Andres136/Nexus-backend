<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\CotizacionRequest;
use App\Models\Crm\Cotizacion;
use App\Models\Crm\CotizacionDetalles;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CotizacionController extends Controller
{
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
                    $precioTotal   = floatval($detalle['precio_total'] ?? 0);
                    $numeroBolsas  = max(1, intval($detalle['numero_bolsas'] ?? 1));
                    $valorUnitario = round($precioTotal / $numeroBolsas, 2);
                    $cantidad      = floatval($detalle['cantidad'] ?? 0);
                    $valorTotal    = round($cantidad * $valorUnitario * 1.19, 2);
    
                    return array_merge($detalle, [
                        'item'           => $index + 1,
                        'valor_unitario' => $valorUnitario,
                        'valor_total'    => $valorTotal,
                    ]);
                });
    
            // 2. Sumamos el total global
            $valorTotalGlobal = $detalles->sum('valor_total');
    
            // 3. Creamos la cabecera de la cotización
            $cotizacion = Cotizacion::create([
                'cliente_id'    => $request->cliente_id,
                'empresa'       => $request->empresa,
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
         $cotizacion = Cotizacion::with('cliente', 'user', 'detalles')->findOrFail($id);
 
         $logo = match ($cotizacion->empresa) {
             'global' => public_path('images/SETAS.PNG'),
             default => public_path('images/logo-setasplast.png'),
         };
 
         $pdf = Pdf::loadView('pdf.cotizacion', [
             'cotizacion' => $cotizacion,
             'logo' => $logo,
         ]);
 
         return $pdf->download("Cotizacion_{$cotizacion->id}.pdf");
     }

    public function show(string $id)
    {
        $cotizacion = Cotizacion::with(['detalles', 'cliente', 'user'])->findOrFail($id);
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
    DB::beginTransaction();

    try {
        $cotizacion = Cotizacion::findOrFail($id);

        // 1. Actualiza cabecera
        $cotizacion->update([
            'empresa'       => $request->empresa,
            'observaciones' => $request->observaciones,
        ]);

        // 2. Obtener IDs de los detalles recibidos
        $detallesRequest = collect($request->input('detalles', []));
        $idsRecibidos = $detallesRequest->pluck('id')->filter()->all();

        // 3. Eliminar detalles que ya no están
        $cotizacion->detalles()->whereNotIn('id', $idsRecibidos)->delete();

        $valorTotalGlobal = 0;

        // 4. Procesar cada detalle
        foreach ($detallesRequest as $index => $detalle) {
            $precioTotal   = floatval($detalle['precio_total'] ?? 0);
            $numeroBolsas  = max(1, intval($detalle['numero_bolsas'] ?? 1));
            $valorUnitario = round($precioTotal / $numeroBolsas, 2);
            $cantidad      = floatval($detalle['cantidad'] ?? 0);
            $valorTotal    = round($cantidad * $valorUnitario * 1.19, 2);

            $data = [
                'item'           => $index + 1,
                'descripcion'    => mb_strtoupper($detalle['descripcion'] ?? ''),
                'ancho_cm'       => $detalle['ancho_cm'] ?? null,
                'largo_cm'       => $detalle['largo_cm'] ?? null,
                'calibre'        => $detalle['calibre'] ?? null,
                'peso_bolsa'     => $detalle['peso_bolsa'] ?? null,
                'numero_bolsas'  => $numeroBolsas,
                'precio_total'   => $precioTotal,
                'valor_unitario' => $valorUnitario,
                'valor_total'    => $valorTotal,
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

            $valorTotalGlobal += $valorTotal;
        }

        // 5. Actualiza total global
        $cotizacion->update(['valor_total' => $valorTotalGlobal]);

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
