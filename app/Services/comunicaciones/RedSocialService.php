<?php

namespace App\Services\comunicaciones;

use App\Models\comunicaciones\RedSocial;

class RedSocialService
{
    public function create(array $data)
    {
        return RedSocial::create($data);
    }

    public function update(RedSocial $redSocial, array $data)
    {
        $redSocial->update($data);
        return $redSocial;
    }

    public function delete(RedSocial $redSocial)
    {
        return $redSocial->delete();
    }

    public function find($id)
    {
        return RedSocial::findOrFail($id);
    }

    public function all($search = null, $limit = 50)
    {
        $query = RedSocial::query();

        if ($search) {
            $query->where('nombre', 'like', "%{$search}%");
        }

        return $query->orderBy('nombre')->limit($limit)->get();
    }
}
