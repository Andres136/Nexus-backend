<?php

namespace App\Services\Hseq;


use App\Models\Hseq\HallazgoNovedad;
use App\Models\Hseq\SoporteTarea;
use App\Models\RegistroDiario\Novedades;
use App\Models\Tareas;
use App\Models\User;
use App\Notifications\NuevaTareaAsignada;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HallazgoNovedadService
{
   //crud para hallazgo novedad
public function create(array $data)
{
    return DB::transaction(function () use ($data) {

        //  Crear hallazgo
        $hallazgo = HallazgoNovedad::create($data);

        // Crear tarea automáticamente
      $usuario = User::find($data['responsable_id']);
      $novedad = Novedades::find($data['novedad_id']);


$tarea = Tareas::create([
     'nombre' => $novedad->descripcion ?? 'Tarea sin descripción', //  AQUÍ
    'descripcion' => $data['plan_accion'] ?? null,
    'fecha_fin' => $data['fecha_cierre'] ?? null,
    'estado_id' => 1,
    'departamento_id' => $usuario->departamento_id ?? 1,
    'user_id' => $usuario->id ?? null,
    'user_id_creo' => auth()->id()
]);



// Crear soporte vacío vinculado a tarea y hallazgo
SoporteTarea::create([
    'tarea_id'    => $tarea->id,
    'hallazgo_id' => $hallazgo->id,
]);

// Notificación
if ($usuario) {
    $usuario->notify(new NuevaTareaAsignada($tarea));
}

        return $hallazgo;
    });
}

    public function find($id)
    {
        return HallazgoNovedad::findOrFail($id);
    }

 public function update($id, array $data)
{
    return DB::transaction(function () use ($id, $data) {

        // =====================================================
        // 🔹 1. ACTUALIZAR HALLAZGO
        // =====================================================
        $hallazgo = $this->find($id);

        // No se puede cerrar un hallazgo sin al menos un soporte de cierre
        // adjunto (la fila de SoporteTarea se crea vacía al crear el hallazgo,
        // por eso se exige que soporte_tarea no sea null, no solo que exista la fila).
        // Solo se valida cuando la petición trae 'estado' explícitamente (se está
        // cerrando o reafirmando el cierre) — no en updates parciales que no
        // tocan el estado, como el reordenamiento por drag-and-drop, que de lo
        // contrario fallaría con 422 para cualquier hallazgo ya cerrado.
        if (isset($data['estado']) && strtoupper($data['estado']) === 'CERRADA') {
            $tieneSoporte = SoporteTarea::where('hallazgo_id', $hallazgo->id)
                ->whereNotNull('soporte_tarea')
                ->exists();

            if (! $tieneSoporte) {
                throw ValidationException::withMessages([
                    'estado' => 'No puedes cerrar este hallazgo sin al menos un soporte de cierre adjunto.',
                ]);
            }
        }

        $hallazgo->update($data);

        // =====================================================
        // 🔹 2. OBTENER NOVEDAD RELACIONADA
        // =====================================================
        $novedad = Novedades::with('hallazgos')->find($hallazgo->novedad_id);

        if ($novedad) {

            // =================================================
            // 🔹 3. VALIDAR SI TODOS LOS HALLAZGOS ESTÁN CERRADOS
            // =================================================
            $hallazgosPendientes = $novedad->hallazgos()
                ->where('estado', '!=', 'CERRADA')
                ->count();

            // =================================================
            // 🔹 4. SI TODOS ESTÁN CERRADOS → CERRAR NOVEDAD
            // =================================================
            if ($hallazgosPendientes === 0) {

                $novedad->update([
                    'estado' => 'CERRADA',
                    'fecha_terminado' => now(),
                ]);

            } else {

                // Si aún hay pendientes, mantener en proceso
                $novedad->update([
                    'estado' => 'EN_PROCESO',
                ]);
            }
        }

        return $hallazgo->load([
            'responsable',
            'novedad'
        ]);
    });
}

    public function delete($id)
    {
        $hallazgoNovedad = $this->find($id);
        return $hallazgoNovedad->delete();
    }

}