<?php

namespace App\Services\Nomina;

use App\Models\Nomina\UsersFacePhoto;
use Illuminate\Support\Collection;

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

    public function create(array $data): UsersFacePhoto
    {
        return UsersFacePhoto::create($data);
    }

    public function update(int $id, array $data): UsersFacePhoto
    {
        $facePhoto = UsersFacePhoto::findOrFail($id);
        $facePhoto->update($data);
        return $facePhoto;
    }

    public function delete(int $id): void
    {
        $facePhoto = UsersFacePhoto::findOrFail($id);
        $facePhoto->delete();
    }
}
