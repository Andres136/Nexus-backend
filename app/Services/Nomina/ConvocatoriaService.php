<?php

namespace App\Services\Nomina;

use App\Models\Nomina\Convocatoria;
use App\Models\Nomina\PostulacionConvocatoria;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ConvocatoriaService
{
    public function listar(): Collection
    {
        return Convocatoria::with(['sede', 'creador'])
            ->withCount('postulaciones')
            ->latest()
            ->get();
    }

    public function mostrar(string $uuid): Convocatoria
    {
        return Convocatoria::with(['sede', 'creador'])->where('uuid', $uuid)->firstOrFail();
    }

    public function activaParaUsuario(User $user): ?Convocatoria
    {
        return Convocatoria::where('activa', true)
            ->where(function ($query) use ($user) {
                $query->whereNull('sede_id')
                    ->orWhere('sede_id', $user->sede_id);
            })
            ->orderByRaw('sede_id IS NULL')
            ->latest()
            ->first();
    }

    public function activasParaUsuario(User $user): Collection
    {
        return Convocatoria::with('sede')
            ->where('activa', true)
            ->where(function ($query) use ($user) {
                $query->whereNull('sede_id')
                    ->orWhere('sede_id', $user->sede_id);
            })
            ->latest()
            ->get();
    }

    public function crear(array $data, UploadedFile $imagen, int $creadoPor): Convocatoria
    {
        return DB::transaction(function () use ($data, $imagen, $creadoPor) {
            $path = $imagen->store('nomina/convocatorias', 'public');

            return Convocatoria::create([
                'titulo' => $data['titulo'],
                'descripcion' => $data['descripcion'] ?? null,
                'imagen' => $path,
                'sede_id' => $data['sede_id'] ?? null,
                'activa' => $data['activa'] ?? true,
                'creado_por' => $creadoPor,
            ]);
        });
    }

    public function actualizar(string $uuid, array $data, ?UploadedFile $imagen): Convocatoria
    {
        return DB::transaction(function () use ($uuid, $data, $imagen) {
            $convocatoria = Convocatoria::where('uuid', $uuid)->firstOrFail();
            $imagenAnterior = $convocatoria->imagen;

            $convocatoria->update([
                'titulo' => $data['titulo'],
                'descripcion' => $data['descripcion'] ?? null,
                'sede_id' => $data['sede_id'] ?? null,
                'imagen' => $imagen ? $imagen->store('nomina/convocatorias', 'public') : $convocatoria->imagen,
            ]);

            if ($imagen && $imagenAnterior) {
                Storage::disk('public')->delete($imagenAnterior);
            }

            return $convocatoria->fresh(['sede', 'creador']);
        });
    }

    public function cambiarEstado(string $uuid, bool $activa): Convocatoria
    {
        $convocatoria = Convocatoria::where('uuid', $uuid)->firstOrFail();
        $convocatoria->update(['activa' => $activa]);

        return $convocatoria->fresh(['sede', 'creador']);
    }

    public function eliminar(string $uuid): void
    {
        $convocatoria = Convocatoria::where('uuid', $uuid)->firstOrFail();

        DB::transaction(function () use ($convocatoria) {
            if ($convocatoria->imagen) {
                Storage::disk('public')->delete($convocatoria->imagen);
            }

            $convocatoria->delete();
        });
    }

    public function postularse(string $convocatoriaUuid, int $userId, string $cargoInteres): PostulacionConvocatoria
    {
        $convocatoria = Convocatoria::where('uuid', $convocatoriaUuid)->firstOrFail();

        $existente = PostulacionConvocatoria::where('convocatoria_id', $convocatoria->id)
            ->where('user_id', $userId)
            ->first();

        if ($existente) {
            return $existente;
        }

        return PostulacionConvocatoria::create([
            'convocatoria_id' => $convocatoria->id,
            'user_id' => $userId,
            'cargo_interes' => $cargoInteres,
        ]);
    }

    public function miPostulacion(string $convocatoriaUuid, int $userId): ?PostulacionConvocatoria
    {
        $convocatoria = Convocatoria::where('uuid', $convocatoriaUuid)->firstOrFail();

        return PostulacionConvocatoria::where('convocatoria_id', $convocatoria->id)
            ->where('user_id', $userId)
            ->first();
    }

    public function listarPostulantes(string $convocatoriaUuid, array $filters = []): LengthAwarePaginator
    {
        $convocatoria = Convocatoria::where('uuid', $convocatoriaUuid)->firstOrFail();
        $perPage = min(max((int) ($filters['per_page'] ?? 15), 1), 100);

        return PostulacionConvocatoria::with('usuario')
            ->where('convocatoria_id', $convocatoria->id)
            ->latest()
            ->paginate($perPage);
    }
}
