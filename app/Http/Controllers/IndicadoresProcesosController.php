<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIndicadoresRequest;
use App\Models\Indicadores;
use Illuminate\Http\Request;
use PhpParser\Node\Stmt\TryCatch;

class IndicadoresProcesosController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
          $user = $request->user();
    $perPage = $request->input('per_page', 30);

    // Solo mostrar indicadores si el usuario es responsable de su departamento
    $departamento = $user->departamento;
    if (!$departamento || $departamento->responsable_id !== $user->id) {
        return response()->json(['message' => 'No autorizado.'], 403);
    }

    $indicadores = Indicadores::with('departamento', 'user')
        ->where('departamento_id', $departamento->id)
        ->paginate($perPage);

    return response()->json($indicadores);
}

    /**
     * Store a newly created resource in storage.
     */
public function store(StoreIndicadoresRequest $request)
{
    $user = auth()->user();
    $departamento = $user->departamento;

    // Autoriza: solo el responsable de su departamento
    if (!$departamento || $departamento->responsable_id !== $user->id) {
        return response()->json(['message' => 'No autorizado para crear indicadores en este departamento.'], 403);
    }

    try {
        $indicador = Indicadores::create([
            'departamento_id' => $request->departamento_id, // ya viene forzado del auth en el Request
            'formula'         => $request->formula,
            'meta'            => $request->meta,
            'frecuencia'      => $request->frecuencia,
            'nombre'          => $request->nombre,
            'descripcion'     => $request->descripcion,
            'user_id'         => $request->user_id,         // del auth
            'tipo_meta'       => $request->tipo_meta,       // 'mayor' o 'menor'
        ])->load('departamento','user');

        return response()->json([
            'message' => 'Indicador creado con éxito',
            'data'    => $indicador
        ], 201);

    } catch (\Illuminate\Database\QueryException $e) {
        // si tienes índice único por (departamento_id,nombre)
        return response()->json([
            'message' => 'No se pudo crear el indicador (posible duplicado).',
            'error'   => $e->getMessage(),
        ], 422);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Error al crear el indicador',
            'error'   => $e->getMessage()
        ], 500);
    }
}


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $query = Indicadores::with('departamento', 'user', 'registros.user')->find($id);
        if (!$query) {
            return response()->json(['message' => 'Indicador no encontrado'], 404);
        }
        return response()->json(['data' => $query], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $indicador = Indicadores::find($id);
        if (!$indicador) {
            return response()->json(['message' => 'Indicador no encontrado'], 404);
        }
        $indicador->update($request->all());
        return response()->json(['message' => 'Indicador actualizado con éxito', 'data' => $indicador], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $indicador = Indicadores::find($id);
        if (!$indicador) {
            return response()->json(['message' => 'Indicador no encontrado'], 404);
        }
        $indicador->delete();
        return response()->json(['message' => 'Indicador eliminado con éxito'], 200);
    }



public function indexAdmin(Request $request)
{
    $user = $request->user();
    $perPage = $request->input('per_page', 10);
    $departamentoId = $request->input('departamento_id');
    $search = $request->query('search');

    /*
    |--------------------------------------------------------------------------
    | ADMIN / SUPERVISOR
    |--------------------------------------------------------------------------
    */
    if (in_array($user->role_id, [1, 2])) {

        $query = Indicadores::with('departamento', 'user');

        // 🔍 Filtro por departamento
        if ($departamentoId) {
            $query->where('departamento_id', $departamentoId);
        } elseif ($user->departamento_id) {
            $query->where('departamento_id', $user->departamento_id);
        }

        // 🔍 Búsqueda general
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('descripcion', 'like', "%{$search}%")
                  ->orWhere('formula', 'like', "%{$search}%")
                  ->orWhere('frecuencia', 'like', "%{$search}%")
                  ->orWhere('tipo_meta', 'like', "%{$search}%");
            });
        }

        $indicadores = $query
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        return response()->json($indicadores);
    }

    /*
    |--------------------------------------------------------------------------
    | RESPONSABLE DE DEPARTAMENTO
    |--------------------------------------------------------------------------
    */
    $departamento = $user->departamento;

    if (!$departamento || $departamento->responsable_id !== $user->id) {
        return response()->json([
            'message' => 'No autorizado.'
        ], 403);
    }

    $query = Indicadores::with('departamento', 'user', 'registros')
        ->where('departamento_id', $departamento->id);

    // 🔍 Búsqueda para responsables
    if ($search) {
        $query->where(function ($q) use ($search) {
            $q->where('nombre', 'like', "%{$search}%")
              ->orWhere('descripcion', 'like', "%{$search}%")
              ->orWhere('formula', 'like', "%{$search}%")
              ->orWhere('frecuencia', 'like', "%{$search}%")
              ->orWhere('tipo_meta', 'like', "%{$search}%");
        });
    }

    $indicadores = $query
        ->orderBy('id', 'desc')
        ->paginate($perPage);

    return response()->json($indicadores);
}
    
}
