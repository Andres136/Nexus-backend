<?php

namespace App\Http\Controllers;

use App\Http\Requests\MacroProcesoRequest;

use App\Models\Macroprocesos;
use Illuminate\Http\Request;

class MacroProcesoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $macroprocesos = Macroprocesos::all();
        return response()->json($macroprocesos);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(MacroProcesoRequest $request)
    {
       

    }

    /**
     * Display the specified resource.  
     */
    public function show(string $id)
    {
        $macroproceso = Macroprocesos::with('departamentos')->find($id);

        if (!$macroproceso) {
            return response()->json(["Error" => "Macroproceso no encontrado"], 404);
        }
    
        return response()->json($macroproceso);
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
