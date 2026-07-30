<?php

namespace App\Services\comunicaciones;

use App\Models\comunicaciones\TipoPost;

class TipoPostService
{
    public function create(array $data)
    {
        return TipoPost::create($data);
    }

    public function update(TipoPost $tipoPost, array $data)
    {
        $tipoPost->update($data);
        return $tipoPost;
    }

    public function delete(TipoPost $tipoPost)
    {
        return $tipoPost->delete();
    }

    public function find($id)
    {
        return TipoPost::findOrFail($id);
    }

    public function all($search = null, $limit = 50)
    {
        $query = TipoPost::query();

        if ($search) {
            $query->where('nombre', 'like', "%{$search}%");
        }

        return $query->orderBy('nombre')->limit($limit)->get();
    }
}
