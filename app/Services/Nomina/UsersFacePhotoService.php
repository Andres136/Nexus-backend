<?php

namespace App\Services\Nomina;

use App\Models\Nomina\UsersFacePhoto;
use App\Http\Requests\Nomina\StoreUsersFacePhotoRequest;
use App\Http\Requests\Nomina\UpdateUsersFacePhotoRequest;
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
                'uuid'     => $facePhoto->uuid,                    // ← 'id' → 'uuid'
                'users_id' => $facePhoto->users_id,
            ]);

            return $facePhoto;
        });
    }

    public function update(UpdateUsersFacePhotoRequest $request, string $uuid): UsersFacePhoto  // ← int $id → string $uuid
    {
        return DB::transaction(function () use ($request, $uuid) {
            $facePhoto = $this->getByUuid($uuid);                  // ← getById($id) → getByUuid($uuid)
            $data      = $request->validated();

            if ($request->hasFile('photo')) {
                if ($facePhoto->photo) {
                    Storage::disk('public')->delete($facePhoto->photo);
                }
                $data['photo'] = $request->file('photo')
                    ->store('nomina/face_photos', 'public');
            }

            $facePhoto->update($data);

            Log::info('Foto facial actualizada', ['uuid' => $facePhoto->uuid]);  // ← 'id' → 'uuid'

            return $facePhoto->fresh('empleado');                  // ← agregado fresh() con relación
        });
    }

    public function delete(string $uuid): void                     // ← int $id → string $uuid
    {
        DB::transaction(function () use ($uuid) {
            $facePhoto = $this->getByUuid($uuid);                  // ← getById($id) → getByUuid($uuid)

            if ($facePhoto->photo) {
                Storage::disk('public')->delete($facePhoto->photo);
            }

            $facePhoto->delete();

            Log::info('Foto facial eliminada', ['uuid' => $facePhoto->uuid]);  // ← 'id' → 'uuid'
        });
    }
}
