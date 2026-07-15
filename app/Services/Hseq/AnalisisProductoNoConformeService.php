<?php

namespace App\Services\Hseq;

use App\Models\Hseq\AnalisisProductoNoConforme;
use Illuminate\Http\UploadedFile;

class AnalisisProductoNoConformeService
{
    private array $with = [
        'productoNoConforme.cliente:id,nombre',
        'productoNoConforme.producto:id,name',
        'analista:id,name',
        'estado:id,nombre',
        'responsableCierre:id,name',
    ];

    public function crear(array $data, ?UploadedFile $archivo = null): AnalisisProductoNoConforme
    {
        if ($archivo) {
            $data['archivo_evidencia'] = $archivo->store('analisis_productos_no_conformes', 'public');
        }

        $analisis = AnalisisProductoNoConforme::create($data);

        return $analisis->load($this->with);
    }

    public function show(int $id): AnalisisProductoNoConforme
    {
        return AnalisisProductoNoConforme::with($this->with)->findOrFail($id);
    }

    public function showByProducto(int $productoNoConformeId): AnalisisProductoNoConforme
    {
        return AnalisisProductoNoConforme::with($this->with)
            ->where('producto_no_conforme_id', $productoNoConformeId)
            ->firstOrFail();
    }

    public function actualizar(int $id, array $data): AnalisisProductoNoConforme
    {
        $analisis = AnalisisProductoNoConforme::findOrFail($id);
        $analisis->update($data);

        return $analisis->load($this->with);
    }

    public function cambiarEstado(int $id, int $estadoId): AnalisisProductoNoConforme
    {
        $analisis = AnalisisProductoNoConforme::findOrFail($id);
        $analisis->update(['estado_id' => $estadoId]);

        return $analisis->load($this->with);
    }
}
