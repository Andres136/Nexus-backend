<?php

namespace App\Services;

use App\Models\DocumentoMaestro;
use Illuminate\Database\Eloquent\Collection;

class DocumentoMaestroService
{
    public function listarPorDepartamento(int $departamentoId): Collection
    {
        return DocumentoMaestro::with(['departamento', 'liderProceso'])
            ->where('departamento_id', $departamentoId)
            ->orderBy('codigo')
            ->get();
    }

    public function obtener(int $id): DocumentoMaestro
    {
        return DocumentoMaestro::with(['departamento', 'liderProceso'])->findOrFail($id);
    }

    public function crear(array $data): DocumentoMaestro
    {
        return DocumentoMaestro::create($data)->load(['departamento', 'liderProceso']);
    }

    public function actualizar(int $id, array $data): DocumentoMaestro
    {
        $documento = DocumentoMaestro::findOrFail($id);
        $documento->update($data);

        return $documento->load(['departamento', 'liderProceso']);
    }

    public function eliminar(int $id): void
    {
        DocumentoMaestro::findOrFail($id)->delete();
    }
}
