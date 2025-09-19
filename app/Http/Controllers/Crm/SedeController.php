<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\SedeRequest;
use App\Http\Requests\Crm\UpdateSedeRequest;
use App\Models\Crm\Sede;
use Illuminate\Http\Request;

class SedeController extends Controller
{
    public function index()
{
    $sedes = Sede::all();
    return response()->json($sedes);
}

public function store(SedeRequest $request)
{
   

    $sede = Sede::create([
        'nombre' => $request->nombre,
        'direccion' => $request->direccion,
     
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Sede creada correctamente',
        'data' => $sede
    ], 201);
}

public function update(UpdateSedeRequest $request, Sede $sede)
{


    $sede->update([
        'nombre' => $request->nombre,
        'direccion' => $request->direccion,

    ]);

    return response()->json([
        'success' => true,
        'message' => 'Sede actualizada correctamente',
        'data' => $sede
    ]);
}

//Desactivar
public function destroy(Sede $sede)
{
    $sede->delete();

    return response()->json([
        'success' => true,
        'message' => 'Sede eliminada correctamente',
    ]);
}
}
