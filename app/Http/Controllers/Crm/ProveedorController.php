<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\ProveedorRequest;
use App\Models\Crm\Proveedor;
use Illuminate\Http\Request;

class ProveedorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Proveedor::query();
    
        // Búsqueda por nombre o NIT
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nombre', 'LIKE', "%{$search}%")
                  ->orWhere('nit', 'LIKE', "%{$search}%");
            });
        }
    
        // Paginación (10 por página por defecto)
        $proveedores = $query->orderBy('id', 'desc')->paginate(10);
    
        return response()->json([
            'message' => 'Lista paginada de proveedores',
            'proveedores' => $proveedores
        ], 200);
    }
    

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProveedorRequest $request)
    {
        $proveedor= Proveedor::create([
            'nombre' => $request->nombre,
            'nit' => $request->nit,
            'telefono' => $request->telefono,
            'correo' => $request->correo,
            'direccion' => $request->direccion,
            'ciudad' => $request->ciudad,
            'estado_id' => 1, // Estado activo por defecto
            'observaciones' => $request->observaciones
        ]);
        $proveedor->save();
        return response()->json([
            'message' => 'Proveedor creado exitosamente',
            'proveedor' => $proveedor
        ], 201);

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
        $proveedor = Proveedor::findOrFail($id);
        $proveedor->update([
            'nombre' => $request->nombre,
            'nit' => $request->nit,
            'telefono' => $request->telefono,
            'correo' => $request->correo,
            'direccion' => $request->direccion,
            'ciudad' => $request->ciudad,
            'estado_id' => $request->estado_id,
            'observaciones' => $request->observaciones
        ]);
        return response()->json([
            'message' => 'Proveedor actualizado exitosamente',
            'proveedor' => $proveedor
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
        $proveedor = Proveedor::findOrFail($id);
        $proveedor->delete();
        return response()->json([
            'message' => 'Proveedor eliminado exitosamente',
            'proveedor' => $proveedor
        ], 200);
    }
}
