<?php

namespace App\Services\Hseq;

use App\Models\Hseq\SoporteTarea;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class SoporteTareaService
{
    /**
     * Crear múltiples soportes para tarea o hallazgo
     */
    public function crear(array $data)
    {
        return DB::transaction(function () use ($data) {

            $soportesGuardados = [];
            $archivos = [];

            // =====================================================
            // 🔹 Normalizar archivos
            // =====================================================
            if (!empty($data['soporte_tarea'])) {

                if (is_array($data['soporte_tarea'])) {
                    $archivos = $data['soporte_tarea'];
                } else {
                    $archivos = [$data['soporte_tarea']];
                }
            }

            // =====================================================
            // 🔹 Detectar relación
            // =====================================================
            $tareaId = $data['tarea_id'] ?? null;
            $hallazgoId = $data['hallazgo_id'] ?? null;

            // =====================================================
            // 🔹 Guardar archivos
            // =====================================================
            foreach ($archivos as $archivo) {

                if (!$archivo instanceof UploadedFile) {
                    continue;
                }

                $rutaArchivo = $archivo->store(
                    'soportes_tareas',
                    'public'
                );

                $soportesGuardados[] = SoporteTarea::create([
                    'soporte_tarea' => $rutaArchivo,
                    'nombre_original' => $archivo->getClientOriginalName(),

                    // Solo uno de los dos
                    'tarea_id' => $tareaId,
                    'hallazgo_id' => $hallazgoId,
                ]);
            }

            return $soportesGuardados;
        });
    }

    /**
     * Obtener soportes por tarea
     */
    public function getByTarea(int $tareaId)
    {
        return SoporteTarea::where('tarea_id', $tareaId)
            ->latest()
            ->get();
    }

    /**
     * Obtener soportes por hallazgo
     */
    public function getByHallazgo(int $hallazgoId)
    {
        return SoporteTarea::where('hallazgo_id', $hallazgoId)
            ->latest()
            ->get();
    }

    /**
     * Obtener soportes dinámicamente
     */
    public function getByRelacion(?int $id, string $tipo = 'tarea')
    {
        if (!$id) {
            return collect();
        }

        return match ($tipo) {
            'hallazgo' => $this->getByHallazgo($id),
            default => $this->getByTarea($id),
        };
    }
}