<?php

namespace App\Services\RegistroDiario;

use App\Models\RegistroDiario\Novedades;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class NovedadService
{
    //Retornar las novedades  con filtro por fecha proceso


    public function getNovedades($filters = [])
{
    return Novedades::with(['registroDiario.departamento', 'responsable', ])
        ->whereHas('registroDiario', function ($query) use ($filters) {

            // 🔹 Filtro por rango de fechas
            if (!empty($filters['fecha_inicio']) && !empty($filters['fecha_fin'])) {
                $query->whereBetween('fecha_proceso', [
                    $filters['fecha_inicio'],
                    $filters['fecha_fin'],
                    
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
        return Novedades::with(['registroDiario.departamento',  'hallazgos.responsable'])->find($id);
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





public function getIndicadorSemestral($fechaInicio = null, $fechaFin = null)
{
    // Si no envían fechas → usar semestre actual automático
    if (!$fechaInicio || !$fechaFin) {
        $now = Carbon::now();

        if ($now->month <= 6) {
            // Primer semestre
            $fechaInicio = $now->copy()->startOfYear()->format('Y-m-d');
            $fechaFin = $now->copy()->startOfYear()->addMonths(5)->endOfMonth()->format('Y-m-d');
        } else {
            // Segundo semestre
            $fechaInicio = $now->copy()->startOfYear()->addMonths(6)->format('Y-m-d');
            $fechaFin = $now->copy()->endOfYear()->format('Y-m-d');
        }
    }

    // 🔹 Query base
    $query = Novedades::whereHas('registroDiario', function ($q) use ($fechaInicio, $fechaFin) {
        $q->whereBetween('fecha', [$fechaInicio, $fechaFin]);
    });

    // 🔹 Conteos
    $total = (clone $query)->count();

    $cerradas = (clone $query)
        ->where('estado', 'CERRADA')
        ->count();

    $enProceso = (clone $query)
        ->where('estado', 'EN_PROCESO')
        ->count();

    $abiertas = (clone $query)
        ->where('estado', 'ABIERTA')
        ->count();

    // 🔹 KPI
    $porcentaje = $total > 0
        ? round(($cerradas / $total) * 100, 2)
        : 0;

    return [
        'fecha_inicio' => $fechaInicio,
        'fecha_fin' => $fechaFin,
        'total' => $total,
        'cerradas' => $cerradas,
        'en_proceso' => $enProceso,
        'abiertas' => $abiertas,
        'porcentaje' => $porcentaje
    ];
}
}