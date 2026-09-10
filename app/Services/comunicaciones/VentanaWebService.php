<?php

namespace App\Services\comunicaciones;

use App\Models\comunicaciones\VentanaWeb;
use App\Models\comunicaciones\VentanaWebRegistro;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class VentanaWebService
{
    public function obtener(): VentanaWeb
    {
        return VentanaWeb::singleton();
    }

    public function actualizar(array $data): VentanaWeb
    {
        $ventana = $this->obtener();

        if (($data['imagen'] ?? null) instanceof UploadedFile) {
            $this->eliminarImagen($ventana->imagen);
            $data['imagen'] = $data['imagen']->store('ventana-web', 'public');
        } else {
            unset($data['imagen']);
        }

        if (($data['boton_activo'] ?? null) === false) {
            $data['boton_texto'] = null;
        }

        $ventana->update($data);

        return $ventana->fresh();
    }

    public function registrar(array $data): VentanaWebRegistro
    {
        $ventana = $this->obtener();

        return $ventana->registros()->create($data);
    }

    public function registros(?string $search = null, ?bool $leido = null, int $perPage = 50)
    {
        $query = VentanaWebRegistro::query()->latest();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                    ->orWhere('correo', 'like', "%{$search}%")
                    ->orWhere('telefono', 'like', "%{$search}%");
            });
        }

        if ($leido !== null) {
            $query->where('leido', $leido);
        }

        return $query->paginate($perPage);
    }

    public function buscarRegistro($id): VentanaWebRegistro
    {
        return VentanaWebRegistro::where('uuid', $id)
            ->orWhere('id', $id)
            ->firstOrFail();
    }

    public function marcarLeido(VentanaWebRegistro $registro, bool $leido = true): VentanaWebRegistro
    {
        $registro->update(['leido' => $leido]);

        return $registro;
    }

    public function eliminarRegistro(VentanaWebRegistro $registro): bool
    {
        return (bool) $registro->delete();
    }

    private function eliminarImagen(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
