<?php

namespace App\Services\comunicaciones;

use App\Models\comunicaciones\PublicacionMarketing;

class PublicacionMarketingService
{
    public function create(array $data)
    {
        return PublicacionMarketing::create([
            'titulo' => $data['titulo'],
            'red_social_id' => $data['red_social_id'],
            'tipo_post_id' => $data['tipo_post_id'],
            'fecha' => $data['fecha'],
            'estado' => $data['estado'] ?? 'programado',
            'link' => $data['link'] ?? null,
            'descripcion' => $data['descripcion'] ?? null,
            'responsable_id' => auth()->id(),
        ]);
    }

    public function find($id)
    {
        return PublicacionMarketing::findOrFail($id);
    }

    public function update($id, array $data)
    {
        $publicacion = $this->find($id);
        $publicacion->update($data);
        return $publicacion;
    }

    public function delete($id)
    {
        $publicacion = $this->find($id);
        $publicacion->delete();
        return $publicacion;
    }

    public function all($search = null, $perPage = 100)
    {
        $query = PublicacionMarketing::with([
            'responsable:id,name',
            'redSocial:id,nombre',
            'tipoPost:id,nombre',
        ]);

        if ($search) {
            $query->where('titulo', 'like', "%{$search}%");
        }

        return $query->orderBy('fecha', 'desc')->paginate($perPage);
    }
}
