<?php

namespace App\Services\Hseq;

use App\Models\Hseq\ClausulaIso;
use Illuminate\Support\Facades\DB;

class ClausulaIsoService
{
    //crud para clausulas iso

    public function create(array $data)
    {
        return ClausulaIso::create($data);
    }

    public function createForNormas(array $normas, array $data)
    {
        return DB::transaction(function () use ($normas, $data) {
            return collect($normas)->map(fn ($norma) => ClausulaIso::create([
                'norma_iso_id' => $norma['norma_iso_id'],
                'codigo' => $norma['codigo'],
                'descripcion' => $data['descripcion'],
                'activa' => $data['activa'] ?? true,
            ]));
        });
    }

    public function update(ClausulaIso $clausulaIso, array $data)
    {
        $clausulaIso->update($data);
        return $clausulaIso;
    }

    public function delete(ClausulaIso $clausulaIso)
    {
        return $clausulaIso->delete();
    }

    public function find($id)
    {
        return ClausulaIso::with('norma')->findOrFail($id);
    }

    public function all($search = null, $limit = 100)
    {
        $query = ClausulaIso::with('norma');

        if ($search) {
            $query->where('codigo', 'like', "%{$search}%")
                ->orWhere('descripcion', 'like', "%{$search}%");
        }

        return $query->orderBy('codigo')->limit($limit)->get();
    }

    public function porNorma($normaIsoId, $perPage = 15)
    {
        return ClausulaIso::where('norma_iso_id', $normaIsoId)
            ->where('activa', true)
            ->orderBy('codigo')
            ->paginate($perPage);
    }
}
