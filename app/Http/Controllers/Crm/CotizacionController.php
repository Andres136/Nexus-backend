<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\CotizacionRequest;
use App\Models\Crm\Cotizacion;
use App\Models\Crm\CotizacionDetalles;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
    { DB::beginTransaction();

        try {
            $detalles = $request->input('detalles', []);
            $valor_total = 0;

            foreach ($detalles as &$detalle) {
                $precio_total = floatval($detalle['precio_total'] ?? 0);
                $numero_bolsas = max(1, intval($detalle['numero_bolsas'] ?? 1));
                $valor_unitario = $precio_total / $numero_bolsas;
                $cantidad = floatval($detalle['cantidad'] ?? 0);

                $detalle['valor_unitario'] = $valor_unitario;
                $detalle['valor_total'] = $cantidad * $valor_unitario * 1.19;

                $valor_total += $detalle['valor_total'];
            }

            $cotizacion = Cotizacion::create([
                'cliente_id' => $request->cliente_id,
                'empresa' => $request->empresa,
                'observaciones' => $request->observaciones,
                //Usuario por el request para prueba con postman
                 'user_id' => $request->user_id,
              //  'user_id' => auth()->id(),
                'valor_total' => $valor_total,
            ]);

            foreach ($detalles as $index => $detalle) {
                $detalle['item'] = $index + 1;
                $detalle['cotizacion_id'] = $cotizacion->id;
                CotizacionDetalles::create($detalle);
            }

            DB::commit();

            return response()->json([
                'message' => 'Cotización registrada correctamente',
                'cotizacion' => $cotizacion->load('detalles')
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error al registrar la cotización', 'trace' => $e->getMessage()], 500);
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
