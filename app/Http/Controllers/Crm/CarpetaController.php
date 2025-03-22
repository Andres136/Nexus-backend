<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\CarpetaRequest;
use App\Models\Crm\Carpeta;

use GuzzleHttp\Promise\Create;
use Illuminate\Http\Request;

class CarpetaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
    
        $carpetas = Carpeta::with('documentos')
            ->when($search, function ($query, $search) {
                return $query->where('nombre', 'LIKE', "%$search%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);
    
        return response()->json($carpetas);
    }
    
 
    /**
     * Store a newly created resource in storage.
     */
    public function store(CarpetaRequest $request)
    {
        //
    $carpeta =Carpeta::create([
        'nombre' => $request->nombre
    ]);
    return response()->json(['data' => $carpeta, 'message' => 'Carpeta creada correctamente']);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
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
