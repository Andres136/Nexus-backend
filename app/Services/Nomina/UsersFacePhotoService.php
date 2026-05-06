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

    public function getById(int $id): UsersFacePhoto
    {
        return UsersFacePhoto::with('empleado')->findOrFail($id);
    }

    public function getByUser(int $userId): Collection
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
                'id'       => $facePhoto->id,
                'users_id' => $facePhoto->users_id,
            ]);

            return $facePhoto;
        });
    }

    public function update(UpdateUsersFacePhotoRequest $request, int $id): UsersFacePhoto
    {
        return DB::transaction(function () use ($request, $id) {
            $facePhoto = $this->getById($id);
            $data      = $request->validated();

            if ($request->hasFile('photo')) {
                if ($facePhoto->photo) {
                    Storage::disk('public')->delete($facePhoto->photo);
                }
                $data['photo'] = $request->file('photo')
                    ->store('nomina/face_photos', 'public');
            }

            $facePhoto->update($data);

            Log::info('Foto facial actualizada', ['id' => $facePhoto->id]);

            return $facePhoto;
        });
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            $facePhoto = $this->getById($id);

            if ($facePhoto->photo) {
                Storage::disk('public')->delete($facePhoto->photo);
            }

            $facePhoto->delete();

            Log::info('Foto facial eliminada', ['id' => $facePhoto->id]);
        });
    }
}
