<?php

namespace App\Services\Tic;

use App\Models\Crm\Sede;
use App\Models\Tic\Asignaciones;
use App\Models\User;

class AsignacionesService
{

    //Consltar todas las asignaciones
   
   
public function getAllAsignaciones(array $filters = [])
{
    $query = Asignaciones::with([
        'usuario',
        'sede',
        'producto',
        'empresa',
        'usuarioRecibe',
    ]);

  if (!empty($filters['usuario_id'])) {
    $query->where(function ($q) use ($filters) {
        $q->where('id_usuario', $filters['usuario_id'])
          ->orWhere('usuario_asignacion_id', $filters['usuario_id']);
    });
}
    if (!empty($filters['usuario_nombre'])) {
        $query->whereHas('usuario', function ($q) use ($filters) {
            $q->where('name', 'like', '%' . $filters['usuario_nombre'] . '%');
        });
    }

    if (isset($filters['activo']) && $filters['activo'] !== '') {
        $query->where('activo', $filters['activo']);
    }

    if (!empty($filters['sede_id'])) {
        $query->where('sede_id', $filters['sede_id']);
    }

    if (!empty($filters['search'])) {
        $query->whereHas('producto', function ($q) use ($filters) {
            $q->where('name', 'like', '%' . $filters['search'] . '%');
        });
    }

    $perPage = $filters['per_page'] ?? 20;

    $paginado = $query->paginate($perPage);

$usuariosIds = Asignaciones::select('id_usuario as user_id')
    ->union(
        Asignaciones::select('usuario_asignacion_id as user_id')
    )
    ->pluck('user_id')
    ->unique()
    ->filter();

$usuariosFiltro = User::whereIn('id', $usuariosIds)
    ->select('id', 'name')
    ->orderBy('name')
    ->get();

    $sedesFiltro = Sede::select('id', 'nombre')
        ->orderBy('nombre')
        ->get();

    return [
        'data' => $paginado->items(),
        'current_page' => $paginado->currentPage(),
        'last_page' => $paginado->lastPage(),
        'per_page' => $paginado->perPage(),
        'total' => $paginado->total(),
        'usuarios_filtro' => $usuariosFiltro,
        'sedes_filtro' => $sedesFiltro,

    ];
}

    public function asignarProducto($data)
    {
        // Lógica para asignar un producto a un usuario
        $asignacion = new \App\Models\Tic\Asignaciones();
        $asignacion->id_usuario =auth()->id();
        $asignacion->sede_id = $data['sede_id'];
        $asignacion->producto_id = $data['producto_id'];
        $asignacion->empresa_id = $data['empresa_id'];
        $asignacion->fecha_asignacion = $data['fecha_asignacion'];
        $asignacion->usuario_asignacion_id = $data['usuario_asignacion_id'];
        $asignacion->observaciones = $data['observaciones'] ?? null;
        $asignacion->activo = true;
        $asignacion->save();

        return $asignacion;
    }
public function desactivarAsignacion($id, $observaciones = null)
{
    $asignacion = Asignaciones::with([
        'usuario',
        'usuarioRecibe',
        'empresa',
        'producto',
        'sede'
    ])->findOrFail($id);

    // 🔥 limpiar conflictos
    Asignaciones::where('producto_id', $asignacion->producto_id)
        ->where('activo', 0)
       ->update(['activo' => 2]);

    // 🔥 ahora sí desactivar
    $asignacion->activo = 0;
    $asignacion->fecha_devolucion = now();
    $asignacion->observaciones = $observaciones ?? $asignacion->observaciones;
    $asignacion->save();

    return $asignacion;
}
}