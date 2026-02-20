<?php

namespace App\Services\RegistroDiario;

use App\Models\RegistroDiario\Novedades;
use Illuminate\Support\Facades\Storage;

class NovedadService
{
    //Retornar las novedades  con filtro por fecha proceso


    public function getNovedades($filters = [])
{
    return Novedades::with(['registroDiario.departamento', 'responsable'])
        ->whereHas('registroDiario', function ($query) use ($filters) {

            // 🔹 Filtro por rango de fechas
            if (!empty($filters['fecha_inicio']) && !empty($filters['fecha_fin'])) {
                $query->whereBetween('fecha_proceso', [
                    $filters['fecha_inicio'],
                    $filters['fecha_fin']
                ]);
            }

            // 🔹 Filtro por departamento
            if (!empty($filters['departamento_id'])) {
                $query->where('departamento_id', $filters['departamento_id']);
            }

        })
        ->paginate(10); // Puedes ajustar el número de resultados por página
}

    public function getNovedadesByFechaProceso($fechaProceso)
    {
        return Novedades::with(['registroDiario.departamento', 'responsable'])
            ->whereHas('registroDiario', function ($query) use ($fechaProceso) {
                $query->where('fecha_proceso', $fechaProceso);
            })
            ->paginate(10); // Puedes ajustar el número de resultados por página
    }


    //Traerlas novedades Por   id
    public function getNovedadesById($id)
    {
        return Novedades::with(['registroDiario.departamento'])->find($id);
    }


    //Update estado de la novedad
public function updateNovedad($id, $data, $request)
{
    $novedad = Novedades::find($id);

    if (!$novedad) {
        return null;
    }

    // 🔥 Manejo de archivo soporte
    if ($request->hasFile('soporte')) {

        // Eliminar soporte anterior si existe
        if ($novedad->soporte) {
            Storage::disk('public')->delete($novedad->soporte);
        }

        // Guardar nuevo archivo
        $ruta = $request->file('soporte')
                        ->store('soportes', 'public');

        $data['soporte'] = $ruta;
    }

    $novedad->update($data);

    // Recargar relaciones
    $novedad->load([
        'registroDiario.departamento',
        'responsable'
    ]);

    return $novedad;
}
}