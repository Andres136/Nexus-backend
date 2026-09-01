<?php

namespace App\Services\Bsc;

use App\Models\Bsc\BscPerspectiva;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class BscPerspectivaService
{
    public function listar()
    {
        return BscPerspectiva::withCount('indicadores')
            ->with('departamentos:id,nombre')
            ->orderBy('orden')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $datos          nombre/color/orden ya validados
     * @param  int[]|null             $departamentos  ids responsables; null = no tocar
     */
    public function actualizar(
        BscPerspectiva $perspectiva,
        array $datos,
        ?UploadedFile $icono = null,
        ?array $departamentos = null
    ): BscPerspectiva {
        if ($icono) {
            $this->borrarIcono($perspectiva);
            $datos['icono'] = $icono->store('bsc/iconos', 'public');
        }

        if ($datos !== []) {
            $perspectiva->update($datos);
        }

        if ($departamentos !== null) {
            $perspectiva->departamentos()->sync($departamentos);
        }

        return $perspectiva->fresh(['departamentos:id,nombre']);
    }

    public function eliminarIcono(BscPerspectiva $perspectiva): void
    {
        if ($this->borrarIcono($perspectiva)) {
            $perspectiva->update(['icono' => null]);
        }
    }

    private function borrarIcono(BscPerspectiva $perspectiva): bool
    {
        if (!$perspectiva->icono) {
            return false;
        }

        Storage::disk('public')->delete($perspectiva->icono);

        return true;
    }
}
