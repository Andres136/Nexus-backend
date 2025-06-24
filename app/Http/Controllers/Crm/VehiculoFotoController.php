<?php
namespace App\Http\Controllers\Crm;
use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\RequestFotoVehiculo;
use App\Models\Crm\Vehiculo;
use App\Models\Crm\VehiculoFoto;
use Illuminate\Http\Request;

class VehiculoFotoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
public function index($vehiculo_id)
{
    $fotos = VehiculoFoto::where('vehiculo_id', $vehiculo_id)->get();

    return response()->json($fotos, 200);
}


    /**
     * Store a newly created resource in storage.
     */
    public function store(RequestFotoVehiculo $request, $vehiculo_id)
    {
       
        $vehiculo = Vehiculo::findOrFail($vehiculo_id);

        if ($request->hasFile('fotos')) {
            foreach ($request->file('fotos') as $foto) {
                $ruta = $foto->store('vehiculos', 'public');
                VehiculoFoto::create([
                    'vehiculo_id' => $vehiculo->id,
                    'ruta_foto' => $ruta
                ]);
            }
        }

        return response()->json([
            'message' => 'Fotos cargadas correctamente.'
        ], 201);
    }
    

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        
        $foto = VehiculoFoto::with('vehiculo')->findOrFail($id);

        return response()->json($foto, 200);
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
