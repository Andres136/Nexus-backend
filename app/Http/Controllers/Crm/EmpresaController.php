<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\StoreEmpresaRequest;
use App\Http\Requests\Crm\UpdateEmpresaRequest;
use App\Models\Crm\empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpParser\Node\Stmt\TryCatch;

class EmpresaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $empresas = empresa::all();
        return response()->json($empresas);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEmpresaRequest $request)
    {
        //si subieron un archivo, lo almacenamos y guardamos la ruta
     try {
        $rutaLogo = null;
        if ($request->hasFile('logo')) {
            $rutaLogo = $request->file('logo')
                ->store('logos', 'public'); // guarda en storage/app/public/logos
        }
    
        $empresa = empresa::create([
            'sede_id' => $request->sede_id,
            'nombre' => $request->nombre,
            'direccion' => $request->direccion,
            'telefono' => $request->telefono,
            'email' => $request->email,
            'nit' => $request->nit,
            'logo' => $rutaLogo,
        ]);
    
        return response()->json(['message' => 'Empresa creada exitosamente', 'data' => $empresa], 201);
     } catch (\Exception $e) {
        return response()->json(['message' => 'Error al crear la empresa', 'error' => $e->getMessage()], 500);
     }

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $empresa = empresa::findOrFail($id);
            return response()->json(['data' => $empresa], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al obtener la empresa', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
public function update(UpdateEmpresaRequest $request, string $id)
{
    try {
        $empresa = Empresa::findOrFail($id);

        $data = $request->validated();

        if ($request->hasFile('logo')) {
            if ($empresa->logo && Storage::disk('public')->exists($empresa->logo)) {
                Storage::disk('public')->delete($empresa->logo); // elimina logo anterior
            }
            $data['logo'] = $request->file('logo')->store('logos', 'public');
        }

        $empresa->update($data);

        return response()->json([
            'message' => 'Empresa actualizada exitosamente',
            'data' => $empresa
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Error al actualizar la empresa',
            'error' => $e->getMessage()
        ], 500);
    }
}


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $empresa = empresa::findOrFail($id);
            // Si tiene logo, eliminar el archivo
            if ($empresa->logo && Storage::disk('public')->exists($empresa->logo)) {
                Storage::disk('public')->delete($empresa->logo);
            }
            $empresa->delete();
            return response()->json(['message' => 'Empresa eliminada exitosamente'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al eliminar la empresa', 'error' => $e->getMessage()], 500);
        }
    }
}
