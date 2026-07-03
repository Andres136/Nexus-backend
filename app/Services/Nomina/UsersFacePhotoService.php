<?php

namespace App\Services\Nomina;

use App\Models\Nomina\UsersFacePhoto;
use App\Models\User;
use App\Http\Requests\Nomina\StoreUsersFacePhotoRequest;
use App\Http\Requests\Nomina\UpdateUsersFacePhotoRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UsersFacePhotoService
{
    public function getAll(): Collection
    {
        return UsersFacePhoto::with('empleado')->get();
    }

    public function getEmpleadosConContrato(array $filters = []): array
    {
        $perPage = min(max((int) ($filters['per_page'] ?? 10), 1), 100);
        $baseQuery = $this->empleadosConContratoQuery($filters);

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'con_foto' => (clone $baseQuery)->whereHas('fotoFacialNomina')->count(),
            'sin_foto' => (clone $baseQuery)->whereDoesntHave('fotoFacialNomina')->count(),
        ];

        $paginator = $baseQuery
            ->when(($filters['foto'] ?? 'todos') === 'con_foto', fn ($query) =>
                $query->whereHas('fotoFacialNomina'))
            ->when(($filters['foto'] ?? 'todos') === 'sin_foto', fn ($query) =>
                $query->whereDoesntHave('fotoFacialNomina'))
            ->orderBy('name')
            ->paginate($perPage)
            ->through(fn (User $empleado) => [
                'userId' => $empleado->id,
                'nombre' => $empleado->name,
                'email' => $empleado->email,
                'contrato' => $empleado->contratacionActivaNomina,
                'foto' => $empleado->fotoFacialNomina,
            ]);

        return [
            'items' => $paginator,
            'stats' => $stats,
        ];
    }

    private function empleadosConContratoQuery(array $filters): Builder
    {
        return User::query()
            ->select('id', 'name', 'email')
            ->with([
                'fotoFacialNomina:users_face_photos.id,users_face_photos.uuid,users_face_photos.users_id,users_face_photos.photo',
                'contratacionActivaNomina:contrataciones.id,contrataciones.uuid,contrataciones.users_id,contrataciones.numero_documento,contrataciones.cargo,contrataciones.status,contrataciones.inicio_contratacion,contrataciones.fin_contrato',
            ])
            ->whereHas('contratacionActivaNomina')
            ->when(!empty($filters['search']), function ($query) use ($filters) {
                $search = trim($filters['search']);

                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereHas('contratacionActivaNomina', function ($contrato) use ($search) {
                            $contrato->where('numero_documento', 'like', "%{$search}%")
                                ->orWhere('cargo', 'like', "%{$search}%");
                        });
                });
            });
    }

    public function getByUuid(string $uuid): UsersFacePhoto        // ← getById(int $id) → getByUuid(string $uuid)
    {
        return UsersFacePhoto::with('empleado')
            ->where('uuid', $uuid)                                 // ← findOrFail($id) → where + firstOrFail
            ->firstOrFail();
    }

    public function getByUser(int $userId): Collection             // ← sin cambios, usa userId del usuario
    {
        return UsersFacePhoto::where('users_id', $userId)->get();
    }

    public function store(StoreUsersFacePhotoRequest $request): UsersFacePhoto
    {
        return DB::transaction(function () use ($request) {
            $data = $request->validated();

            if ($request->hasFile('photo')) {
                $data['photo'] = $request->file('photo')
                    ->store('nomina/face_photos', 'public');
            }

            $facePhoto = UsersFacePhoto::create($data);

            Log::info('Foto facial registrada', [
                'uuid'     => $facePhoto->uuid,                    
                'users_id' => $facePhoto->users_id,
            ]);

            KioskoDeviceService::clearBootstrapCache();

            return $facePhoto;
        });
    }

    public function update(UpdateUsersFacePhotoRequest $request, string $uuid): UsersFacePhoto  
    {
        return DB::transaction(function () use ($request, $uuid) {
            $facePhoto = $this->getByUuid($uuid);

            $data = $request->validated();

            if ($request->hasFile('photo')) {
                if ($facePhoto->photo) {
                    Storage::disk('public')->delete($facePhoto->photo);
                }
                $data['photo'] = $request->file('photo')
                    ->store('nomina/face_photos', 'public');
            } else {
                unset($data['photo']);
            }

            $facePhoto->update($data);

            Log::info('Foto facial actualizada', ['uuid' => $facePhoto->uuid]);  

            KioskoDeviceService::clearBootstrapCache();

            return $facePhoto->fresh('empleado');                  
        });
    }

    public function delete(string $uuid): void                 
    {
        DB::transaction(function () use ($uuid) {
            $facePhoto = $this->getByUuid($uuid);                  

            if ($facePhoto->photo) {
                Storage::disk('public')->delete($facePhoto->photo);
            }

            $facePhoto->delete();

            Log::info('Foto facial eliminada', ['uuid' => $facePhoto->uuid]); 

            KioskoDeviceService::clearBootstrapCache();
        });
    }
}
