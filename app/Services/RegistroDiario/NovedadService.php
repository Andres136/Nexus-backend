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
    return Novedades::with(['registroDiario.departamento', 'responsable'])

        ->where(function ($query) use ($filters) {

            // 🔹 FILTROS DE RELACIÓN
            $query->whereHas('registroDiario', function ($q) use ($filters) {

                if (!empty($filters['fecha_inicio']) && !empty($filters['fecha_fin'])) {
                    $q->whereBetween('fecha', [
                        $filters['fecha_inicio'],
                        $filters['fecha_fin'],
                    ]);
                }

                if (!empty($filters['departamento_id'])) {
                    $q->where('departamento_id', $filters['departamento_id']);
                }
            });

            // 🔹 FILTRO POR USUARIO
            if (!empty($filters['usuario'])) {
                $query->whereHas('responsable', function ($q) use ($filters) {
                    $q->where('name', 'LIKE', '%' . $filters['usuario'] . '%');
                });
            }

            // 🔹 FILTRO POR NÚMERO DE NO CONFORMIDAD
            if (!empty($filters['numero_no_conformidad'])) {
                $query->where('numero_no_conformidad', 'LIKE', '%' . $filters['numero_no_conformidad'] . '%');
            }

            // 🔹 FILTRO POR FUENTE
            if (!empty($filters['fuentes'])) {
                $query->where('fuentes', $filters['fuentes']);
            }

            // 🔥 SEARCH GLOBAL BIEN AGRUPADO
            if (!empty($filters['search'])) {
                $query->where(function ($q) use ($filters) {

                    $q->where('descripcion', 'LIKE', '%' . $filters['search'] . '%')
                      ->orWhereHas('responsable', function ($q2) use ($filters) {
                          $q2->where('name', 'LIKE', '%' . $filters['search'] . '%');
                      })
                      ->orWhereHas('registroDiario.departamento', function ($q3) use ($filters) {
                          $q3->where('nombre', 'LIKE', '%' . $filters['search'] . '%');
                      });

                });
            }

        })

        ->orderByRaw("
            CASE 
                WHEN estado = 'ABIERTA' THEN 1
                WHEN estado = 'EN_PROCESO' THEN 2
                WHEN estado = 'CERRADA' THEN 3
                ELSE 4
            END
        ")

        ->orderByDesc('created_at')

        ->paginate($filters['per_page'] ?? 3);
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
    $novedad = Novedades::with('hallazgos')->find($id);

    if (!$novedad) {
        return null;
    }

    // =====================================================
    // 🔹 VALIDAR CIERRE GLOBAL
    // =====================================================
    if (
        isset($data['estado']) &&
        strtoupper($data['estado']) === 'CERRADA'
    ) {

        $hallazgosPendientes = $novedad->hallazgos()
            ->where('estado', '!=', 'CERRADA')
            ->count();

        if ($hallazgosPendientes > 0) {
            throw new \Exception(
                'Debes cerrar todos los planes de acción (hallazgos) antes de cerrar esta novedad.'
            );
        }
    }

    // =====================================================
    // 🔹 MANEJO DE ARCHIVO SOPORTE
    // =====================================================
    if ($request->hasFile('soporte')) {

        if ($novedad->soporte) {
            Storage::disk('public')->delete($novedad->soporte);
        }

        $ruta = $request->file('soporte')
            ->store('soportes', 'public');

        $data['soporte'] = $ruta;
    }

    // =====================================================
    // 🔹 ACTUALIZAR NOVEDAD
    // =====================================================
    $novedad->update($data);

    // =====================================================
    // 🔹 RECARGAR RELACIONES
    // =====================================================
    $novedad->load([
        'registroDiario.departamento',
        'responsable',
        'hallazgos.responsable'
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


// Método para eliminar una novedad
public function deleteNovedad($id)
{
    $novedad = Novedades::find($id);
    if (!$novedad) {
        return false;
    }
    // Eliminar soporte si existe
    if ($novedad->soporte) {
        Storage::disk('public')->delete($novedad->soporte);
    }
    $novedad->delete();
    return true;
}
}