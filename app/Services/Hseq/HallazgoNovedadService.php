<?php

namespace App\Services\Hseq;


use App\Models\Hseq\HallazgoNovedad;
use App\Models\Hseq\SoporteTarea;
use App\Models\RegistroDiario\Novedades;
use App\Models\Tareas;
use App\Models\User;
use App\Notifications\NuevaTareaAsignada;
use Illuminate\Support\Facades\DB;

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