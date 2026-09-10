<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\InspeccionesRequest;
use App\Http\Requests\Crm\InspeccionUpdateRequest;
use App\Models\Crm\Inspeccion;
use App\RolEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class InspeccionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $inspecciones = Inspeccion::with(['vehiculo', 'fotos'])
            ->orderBy('fecha')
            ->get();

        return response()->json($inspecciones);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(InspeccionesRequest $request)
    {
        $rutaDocumento = null;

        if ($request->hasFile('documento')) {
            $uniqueName = time() . $request->file('documento')->getClientOriginalName();
            $rutaDocumento = $request->file('documento')->storeAs('inspecciones', $uniqueName, 'public');
        }

        $inspeccion = Inspeccion::create([
            'vehiculo_id' => $request->vehiculo_id,
            'fecha' => $request->fecha,
            'fecha_realizado' => $request->fecha_realizado,
            'responsable' => $request->responsable,
            'observaciones' => $request->observaciones,
            'estado_general' => $request->estado_general,
            'documento' => $rutaDocumento,
        ]);

        $this->guardarFotos($request, $inspeccion);

        return response()->json([
            'message' => 'Inspección creada correctamente',
            'inspeccion' => $inspeccion->load('fotos'),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $inspeccion = Inspeccion::with(['vehiculo', 'fotos'])->findOrFail($id);

        return response()->json($inspeccion);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(InspeccionUpdateRequest $request, string $id)
    {
        $inspeccion = Inspeccion::findOrFail($id);

        // Verificar si se ha subido un nuevo documento
        if ($request->hasFile('documento')) {
            if ($inspeccion->documento && Storage::disk('public')->exists($inspeccion->documento)) {
                Storage::disk('public')->delete($inspeccion->documento);
            }

            $uniqueName = time() . $request->file('documento')->getClientOriginalName();
            $inspeccion->documento = $request->file('documento')->storeAs('inspecciones', $uniqueName, 'public');
        }

        $inspeccion->vehiculo_id = $request->vehiculo_id;
        $inspeccion->fecha = $request->fecha;
        $inspeccion->fecha_realizado = $request->fecha_realizado;
        $inspeccion->responsable = $request->responsable;
        $inspeccion->observaciones = $request->observaciones;
        $inspeccion->estado_general = $request->estado_general;

        $inspeccion->save();

        // Las fotos nuevas se agregan a las existentes (no reemplazan).
        $this->guardarFotos($request, $inspeccion);

        return response()->json([
            'message' => 'Inspección actualizada correctamente',
            'inspeccion' => $inspeccion->load('fotos'),
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        if (auth()->user()?->role_id !== RolEnum::ADMINISTRADOR->value) {
            return response()->json([
                'message' => 'No tienes permiso para eliminar inspecciones',
            ], 403);
        }

        $inspeccion = Inspeccion::with('fotos')->findOrFail($id);

        // Eliminar el archivo del sistema de archivos
        if ($inspeccion->documento && Storage::disk('public')->exists($inspeccion->documento)) {
            Storage::disk('public')->delete($inspeccion->documento);
        }

        // Eliminar las fotos asociadas del disco
        foreach ($inspeccion->fotos as $foto) {
            if ($foto->ruta && Storage::disk('public')->exists($foto->ruta)) {
                Storage::disk('public')->delete($foto->ruta);
            }
        }

        // Eliminar el registro de la base de datos (las fotos caen por cascade)
        $inspeccion->delete();

        return response()->json([
            'message' => 'Inspección eliminada correctamente',
        ]);
    }

    /**
     * Elimina una foto puntual de una inspección.
     */
    public function destroyFoto(string $id, string $fotoId)
    {
        $inspeccion = Inspeccion::findOrFail($id);
        $foto = $inspeccion->fotos()->findOrFail($fotoId);

        if ($foto->ruta && Storage::disk('public')->exists($foto->ruta)) {
            Storage::disk('public')->delete($foto->ruta);
        }

        $foto->delete();

        return response()->json([
            'message' => 'Foto eliminada correctamente',
        ]);
    }

    /**
     * Guarda las fotos enviadas en `fotos[]` asociándolas a la inspección.
     */
    private function guardarFotos(Request $request, Inspeccion $inspeccion): void
    {
        if (! $request->hasFile('fotos')) {
            return;
        }

        foreach ($request->file('fotos') as $foto) {
            $uniqueName = time() . '_' . uniqid() . '.' . $foto->getClientOriginalExtension();
            $ruta = $foto->storeAs('inspecciones/fotos', $uniqueName, 'public');
            $inspeccion->fotos()->create(['ruta' => $ruta]);
        }
    }
}
