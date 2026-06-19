<?php

namespace App\Services\Nomina;

use App\Models\Nomina\Incapacidad;
use App\Http\Requests\Nomina\StoreIncapacidadRequest;
use App\Http\Requests\Nomina\UpdateIncapacidadRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class IncapacidadService
{
    // =====================
    // TRAER TODAS
    // =====================
public function getAll(array $filters = [])
{
    $query = Incapacidad::with([
        'empleado.sede',
        'revisor',
        'entidadMedica',
     
    ]);

    // Search
    if (!empty($filters['search'])) {
        $search = $filters['search'];

        $query->where(function ($q) use ($search) {
            $q->where('tipo_incapacidad', 'like', "%{$search}%")
              ->orWhereHas('empleado', function ($empleado) use ($search) {
                  $empleado->where('name', 'like', "%{$search}%");
              });
        });
    }

    // Usuario
    if (!empty($filters['user_id'])) {
        $query->where('user_id', $filters['user_id']);
    }

    if (!empty($filters['sede_id'])) {
        $query->whereHas('empleado', fn ($empleado) => $empleado->where('sede_id', $filters['sede_id']));
    }

    if (!empty($filters['fecha_desde'])) {
        $query->whereDate('fin', '>=', $filters['fecha_desde']);
    }

    if (!empty($filters['fecha_hasta'])) {
        $query->whereDate('inicio', '<=', $filters['fecha_hasta']);
    }

    // Estado
    if (isset($filters['status']) && $filters['status'] !== '') {
        $query->where('status', $filters['status']);
    }

    if (isset($filters['estado_revision']) && $filters['estado_revision'] !== '') {
        if ($filters['estado_revision'] === 'pendiente') {
            $query->where(function ($q) {
                $q->where('estado_revision', 'pendiente')
                  ->orWhereNull('estado_revision');
            });
        } else {
            $query->where('estado_revision', $filters['estado_revision']);
        }
    }

    $query->orderByDesc('created_at');

    $perPage = $filters['per_page'] ?? 20;

    $incapacidades = $query->paginate($perPage);

    // Transformar resultados
    $incapacidades->getCollection()->transform(function ($item) {

        // URL completa PDF
        $item->soporte_url = $item->soporte
            ? asset('storage/' . $item->soporte)
            : null;
        $item->soporte_embed_url = $item->soporte
            ? url("api/nomina/incapacidades/{$item->uuid}/soporte")
            : null;

        // Estado automático según fecha fin
        $item->estado_actual = now()->gt($item->fin)
            ? 'finalizada'
            : 'activa';

        // Opcional: actualizar campo status en memoria
        $item->status = now()->gt($item->fin)
            ? false
            : true;
        $item->estado_revision = $item->estado_revision ?? 'pendiente';

        return $item;
    });

    return $incapacidades;
} // =====================
    // TRAER UNA
    // =====================
public function getByUuid(string $uuid): Incapacidad
{
    $incapacidad = Incapacidad::with([
        'empleado',
        'revisor',
        'entidadMedica',
    ])
    ->where('uuid', $uuid)
    ->firstOrFail();

    // URL pública del PDF
    $incapacidad->soporte_url = $incapacidad->soporte
        ? asset('storage/' . $incapacidad->soporte)
        : null;
    $incapacidad->soporte_embed_url = $incapacidad->soporte
        ? url("api/nomina/incapacidades/{$incapacidad->uuid}/soporte")
        : null;

    // Estado automático
    $incapacidad->estado_actual = now()->gt($incapacidad->fin)
        ? 'finalizada'
        : 'activa';

    // Estado booleano sincronizado
    $incapacidad->status = now()->gt($incapacidad->fin)
        ? false
        : true;
    $incapacidad->estado_revision = $incapacidad->estado_revision ?? 'pendiente';

    return $incapacidad;
}

    // =====================
    // CREAR
    // =====================
 // SERVICE
public function store(array $data, $soporte = null): Incapacidad
{
    return DB::transaction(function () use ($data, $soporte) {

        $data['user_id'] = Auth::id();
        $data['user_reviso_id'] = null;

        // Estado automático
        $data['status'] = Carbon::parse($data['fin'])->isFuture();

        // Guardar PDF
        if ($soporte instanceof \Illuminate\Http\UploadedFile) {
            $data['soporte'] = $soporte->store(
                'nomina/incapacidades',
                'public'
            );
        }

        $incapacidad = Incapacidad::create($data);

        Log::info('Incapacidad creada', [
            'uuid'    => $incapacidad->uuid,
            'user_id' => $incapacidad->user_id,
        ]);

        $incapacidad = $incapacidad->fresh([
            'empleado.sede',
            'revisor',
            'entidadMedica',
        ]);

        // URL pública
        $incapacidad->soporte_url = $incapacidad->soporte
            ? asset('storage/' . $incapacidad->soporte)
            : null;
        $incapacidad->soporte_embed_url = $incapacidad->soporte
            ? url("api/nomina/incapacidades/{$incapacidad->uuid}/soporte")
            : null;

        // Estado visual
        $incapacidad->estado_actual = now()->gt($incapacidad->fin)
            ? 'finalizada'
            : 'activa';
        $incapacidad->estado_revision = $incapacidad->estado_revision ?? 'pendiente';

        return $incapacidad;
    });
}

    // =====================
    // ACTUALIZAR
    // =====================
    public function update(string $uuid, array $data): Incapacidad
{
    return DB::transaction(function () use ($uuid, $data) {

        // Buscar limpio SIN mutaciones extras
        $incapacidad = Incapacidad::where('uuid', $uuid)->firstOrFail();

        // Eliminar campos virtuales
        unset($data['soporte_url']);

        // Si llega nuevo PDF
        if (
            isset($data['soporte']) &&
            $data['soporte'] instanceof \Illuminate\Http\UploadedFile
        ) {

            // Borrar PDF anterior
            if (
                $incapacidad->soporte &&
                Storage::disk('public')->exists($incapacidad->soporte)
            ) {
                Storage::disk('public')->delete($incapacidad->soporte);
            }

            // Guardar nuevo
            $data['soporte'] = $data['soporte']->store(
                'nomina/incapacidades',
                'public'
            );
        } else {
            // Si no suben nuevo archivo, no tocar soporte
            unset($data['soporte']);
        }

        // Estado automático según fecha fin
        if (isset($data['fin'])) {
            $data['status'] = Carbon::parse($data['fin'])->isFuture();
        }

        Log::info('Datos update incapacidad', $data);

        // Actualizar
        $incapacidad->fill($data);
        $incapacidad->save();

        // Recargar relaciones
        $incapacidad = $incapacidad->fresh([
            'empleado',
            'revisor',
            'entidadMedica',
        ]);

        // URL pública
        $incapacidad->soporte_url = $incapacidad->soporte
            ? asset('storage/' . $incapacidad->soporte)
            : null;
        $incapacidad->soporte_embed_url = $incapacidad->soporte
            ? url("api/nomina/incapacidades/{$incapacidad->uuid}/soporte")
            : null;
        $incapacidad->estado_revision = $incapacidad->estado_revision ?? 'pendiente';

        return $incapacidad;
    });
}
    // =====================
    // REVISAR DOCUMENTO
    // =====================
    public function revisar(string $uuid, string $estadoRevision, ?string $observacion = null): Incapacidad
    {
        return DB::transaction(function () use ($uuid, $estadoRevision, $observacion) {

            $incapacidad = Incapacidad::where('uuid', $uuid)->firstOrFail();

            $incapacidad->user_reviso_id = Auth::id();
            $incapacidad->estado_revision = $estadoRevision;
            $incapacidad->observacion_revision = $observacion;
            $incapacidad->fecha_revision = now();
            $incapacidad->status = $estadoRevision === 'aprobada';
            $incapacidad->save();

            Log::info('Incapacidad revisada', [
                'uuid'           => $incapacidad->uuid,
                'revisado_por'   => Auth::id(),
                'estado_revision' => $estadoRevision,
            ]);

            $incapacidad = $incapacidad->fresh(['empleado', 'revisor', 'entidadMedica']);

            $incapacidad->soporte_url = $incapacidad->soporte
                ? asset('storage/' . $incapacidad->soporte)
                : null;
            $incapacidad->soporte_embed_url = $incapacidad->soporte
                ? url("api/nomina/incapacidades/{$incapacidad->uuid}/soporte")
                : null;

            return $incapacidad;
        });
    }

    // =====================
    // ELIMINAR (soft delete)
    // =====================
    public function destroy(string $uuid): bool  
    {
        return DB::transaction(function () use ($uuid) {

            $incapacidad = $this->getByUuid($uuid);   

            $incapacidad->delete();

            Log::info('Incapacidad eliminada', ['uuid' => $incapacidad->uuid]); 

            return true;
        });
    }
}
