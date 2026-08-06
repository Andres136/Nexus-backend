<?php

namespace App\Services\Tic;

use App\Models\Crm\Sede;
use App\Models\Tic\Asignaciones;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class AsignacionesService
{
    public function __construct(private readonly AsignacionActaService $actaService) {}

    //Consltar todas las asignaciones

public function getAllAsignaciones(array $filters = [])
{
    $query = Asignaciones::with([
        'usuario',
        'sede',
        'producto',
        'empresa',
        'usuarioRecibe',
        'actaAsignacion',
        'actaDevolucion',
        'salidaTemporalAbierta',
    ]);

    /*
    |--------------------------------------------------------------------------
    | Filtro por usuario
    |--------------------------------------------------------------------------
    */
    if (!empty($filters['usuario_id'])) {
        $query->where(function ($q) use ($filters) {
            $q->where('id_usuario', $filters['usuario_id'])
              ->orWhere('usuario_asignacion_id', $filters['usuario_id']);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Filtro por nombre usuario
    |--------------------------------------------------------------------------
    */
    if (!empty($filters['usuario_nombre'])) {
        $query->whereHas('usuario', function ($q) use ($filters) {
            $q->where('name', 'like', '%' . $filters['usuario_nombre'] . '%');
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Estado activo/inactivo
    |--------------------------------------------------------------------------
    */
    if (isset($filters['activo']) && $filters['activo'] !== '') {
        $query->where('activo', $filters['activo']);
    }

    /*
    |--------------------------------------------------------------------------
    | Filtro por sede
    |--------------------------------------------------------------------------
    */
    if (!empty($filters['sede_id'])) {
        $query->where('sede_id', $filters['sede_id']);
    }

    /*
    |--------------------------------------------------------------------------
    | Búsqueda por producto
    |--------------------------------------------------------------------------
    */
    if (!empty($filters['search'])) {
        $query->whereHas('producto', function ($q) use ($filters) {
            $q->where('name', 'like', '%' . $filters['search'] . '%');
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Orden
    |--------------------------------------------------------------------------
    */
    $query->latest();

    /*
    |--------------------------------------------------------------------------
    | Paginación
    |--------------------------------------------------------------------------
    */
    $perPage = $filters['per_page'] ?? 20;
    $paginado = $query->paginate($perPage);

    $data = collect($paginado->items());

    /*
    |--------------------------------------------------------------------------
    | Usuarios filtro
    |--------------------------------------------------------------------------
    */
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

    /*
    |--------------------------------------------------------------------------
    | Sedes filtro
    |--------------------------------------------------------------------------
    */
    $sedesFiltro = Sede::select('id', 'nombre')
        ->orderBy('nombre')
        ->get();

    /*
    |--------------------------------------------------------------------------
    | Respuesta final
    |--------------------------------------------------------------------------
    */
    return [
        'data' => $data,
        'current_page' => $paginado->currentPage(),
        'last_page' => $paginado->lastPage(),
        'per_page' => $paginado->perPage(),
        'total' => $paginado->total(),
        'from' => $paginado->firstItem(),
        'to' => $paginado->lastItem(),

        'usuarios_filtro' => $usuariosFiltro,
        'sedes_filtro' => $sedesFiltro,
    ];
}

public function getAsignacionesByUsuario(int $userId, bool $soloActivas = true): array
{
    $asignaciones = Asignaciones::with([
        'usuario',
        'sede',
        'producto',
        'empresa',
        'usuarioRecibe',
        'actaAsignacion',
        'actaDevolucion',
        'salidaTemporalAbierta',
    ])
        ->where('usuario_asignacion_id', $userId)
        ->when($soloActivas, fn ($query) => $query->where('activo', true))
        ->latest()
        ->get();

    return [
        'tiene_asignaciones' => $asignaciones->isNotEmpty(),
        'total' => $asignaciones->count(),
        'data' => $asignaciones,
    ];
}

    public function asignarProducto($data)
    {
        $tieneActiva = Asignaciones::where('producto_id', $data['producto_id'])
            ->where('activo', 1)
            ->exists();

        if ($tieneActiva) {
            throw ValidationException::withMessages([
                'producto_id' => 'Este equipo ya tiene una asignación activa. Desactívala antes de asignarlo de nuevo.',
            ]);
        }

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

        $this->actaService->generarAutomatico($asignacion, 'asignacion', auth()->user());

        return $asignacion->fresh(['usuario', 'usuarioRecibe', 'empresa', 'producto', 'sede', 'actaAsignacion']);
    }
public function desactivarAsignacion($id, $observaciones = null)
{
    return \Illuminate\Support\Facades\DB::transaction(function () use ($id, $observaciones) {
        $asignacion = Asignaciones::with([
            'usuario',
            'usuarioRecibe',
            'empresa',
            'producto',
            'sede'
        ])->lockForUpdate()->findOrFail($id);

    $asignacion->activo = 0;
    $asignacion->fecha_devolucion = now();
    $asignacion->observaciones = $observaciones ?? $asignacion->observaciones;
    $asignacion->save();

    return $asignacion;
}
}
