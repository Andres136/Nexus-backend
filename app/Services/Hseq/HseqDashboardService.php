<?php

namespace App\Services\Hseq;


use Illuminate\Support\Facades\DB;


class HseqDashboardService
{
    /**
     * 
     */
    public function getDashboard($filters = [])
    {
        return [
            'kpis' => $this->getKpis($filters),
            'estado_inspecciones' => $this->getEstadoInspecciones($filters),
            'por_sede' => $this->getPorSede($filters),
            'por_responsable' => $this->getPorResponsable($filters),
            'cumplimiento_inspecciones' => $this->getCumplimientoPorInspeccion($filters),
            'tendencia' => $this->getTendencia($filters),
        ];
    }
    

    /**
     * 🔹 Aplicar filtros reutilizables
     */
    private function applyFilters($query, $filters)
    {
        if (!empty($filters['fecha_inicio']) && !empty($filters['fecha_fin'])) {
            $query->whereBetween('i.fecha', [
                $filters['fecha_inicio'],
                $filters['fecha_fin']
            ]);
        }

        if (!empty($filters['sede_id'])) {
            $query->where('i.sede_id', $filters['sede_id']);
        }

        if (!empty($filters['responsable_id'])) {
            $query->where('i.responsable_id', $filters['responsable_id']);
        }

        if (!empty($filters['estado'])) {
            $query->where('i.estado', $filters['estado']);
        }

        return $query;
    }

    /**
     * 🔹 KPIs
     */
    private function getKpis($filters)
    {
        $baseQuery = DB::table('inspecciones_hseq as i');
        $baseQuery = $this->applyFilters($baseQuery, $filters);

        $total = (clone $baseQuery)->count();

        $finalizadas = (clone $baseQuery)
            ->where('i.estado', 'finalizada')
            ->count();

        $pendientes = (clone $baseQuery)
            ->where('i.estado', 'pendiente')
            ->count();

        $noCumple = DB::table('respuesta_inspecciones as r')
            ->join('inspecciones_hseq as i', 'i.id', '=', 'r.inspeccion_id')
            ->where('r.respuesta', 0);

        $noCumple = $this->applyFilters($noCumple, $filters)->count();

        return [
            'total_inspecciones' => $total,
            'finalizadas' => $finalizadas,
            'pendientes' => $pendientes,
            'porcentaje_cumplimiento' => $total > 0 ? round(($finalizadas / $total) * 100, 2) : 0,
            'total_fallas' => $noCumple,
        ];
    }

    /**
     * 🔹 Estado inspecciones
     */
    private function getEstadoInspecciones($filters)
    {
        $query = DB::table('inspecciones_hseq as i');
        $query = $this->applyFilters($query, $filters);

        return $query
            ->select('i.estado', DB::raw('COUNT(*) as total'))
            ->groupBy('i.estado')
            ->get();
    }

    /**
     * 🔹 Por sede
     */
private function getPorSede($filters)
{
    $query = DB::table('inspecciones_hseq as i')
        ->join('sedes as s', 's.id', '=', 'i.sede_id');

    $query = $this->applyFilters($query, $filters);

    return $query
        ->select(
            'i.sede_id',
            's.nombre as sede', // 🔥 nombre real
            DB::raw('COUNT(*) as total')
        )
        ->groupBy('i.sede_id', 's.nombre')
        ->get();
}

    /**
     * 🔹 Por responsable
     */
    private function getPorResponsable($filters)
    {
        $query = DB::table('inspecciones_hseq as i');
        $query = $this->applyFilters($query, $filters);

        return $query
            ->select(
                'i.responsable_id',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN i.estado = 'finalizada' THEN 1 ELSE 0 END) as finalizadas")
            )
            ->groupBy('i.responsable_id')
            ->get();
    }

    /**
     * 🔹 Cumplimiento por inspección
     */
    private function getCumplimientoPorInspeccion($filters)
    {
        $query = DB::table('respuesta_inspecciones as r')
            ->join('inspecciones_hseq as i', 'i.id', '=', 'r.inspeccion_id');

        $query = $this->applyFilters($query, $filters);

        return $query
            ->select(
                'r.inspeccion_id',
                DB::raw('SUM(CASE WHEN r.respuesta = 1 THEN 1 ELSE 0 END) as cumple'),
                DB::raw('SUM(CASE WHEN r.respuesta = 0 THEN 1 ELSE 0 END) as no_cumple'),
                DB::raw('COUNT(*) as total'),
                DB::raw('ROUND((SUM(CASE WHEN r.respuesta = 1 THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as porcentaje')
            )
            ->groupBy('r.inspeccion_id')
            ->get();
    }

    /**
     * 🔹 Hallazgos (fallas)
     */
   public function hallazgos($filters, $search = null)
{
    $query = DB::table('respuesta_inspecciones as r')
        ->join('inspecciones_hseq as i', 'i.id', '=', 'r.inspeccion_id')
        ->join('tipo_inspecciones as ti', 'ti.id', '=', 'i.tipo_inspeccion_id')
        ->join('preguntas_inspecciones as p', 'p.id', '=', 'r.pregunta_inspeccion_id')
        ->join('sedes as s', 's.id', '=', 'i.sede_id')
        ->join('users as u', 'u.id', '=', 'i.responsable_id')
        ->where('r.respuesta', 0);

    $query = $this->applyFilters($query, $filters);
        // 🔍 SEARCH
    if (!empty($search)) {
        $query->where(function ($q) use ($search) {
            $q->where('p.pregunta', 'like', "%{$search}%")
              ->orWhere('ti.nombre', 'like', "%{$search}%")
              ->orWhere('s.nombre', 'like', "%{$search}%")
              ->orWhere('u.name', 'like', "%{$search}%");
        });
    }

    return $query->select(
        'r.id',
        'r.inspeccion_id',
        'ti.nombre as tipo_inspeccion', // nombre de la inspección
        'p.pregunta',                   // 🔥 AQUÍ ESTÁ EL TEXTO REAL
        'r.observaciones',
        's.nombre as sede',             // nombre de la sede
        'u.name as responsable',        // nombre del responsable
        'i.fecha'
    )->orderByDesc('i.fecha')
    ->paginate(10);
}

    /**
     * 🔹 Tendencia
     */
   private function getTendencia($filters)
{
    $query = DB::table('respuesta_inspecciones as r')
        ->join('inspecciones_hseq as i', 'i.id', '=', 'r.inspeccion_id');

    $query = $this->applyFilters($query, $filters);

    return $query
        ->select(
            'i.fecha',
            DB::raw('ROUND(AVG(r.respuesta) * 100, 2) as cumplimiento')
        )
        ->groupBy('i.fecha')
        ->orderBy('i.fecha')
        ->get();
}

public function hallazgosParaPdf($filters)
{
    $query = DB::table('respuesta_inspecciones as r')
        ->join('inspecciones_hseq as i', 'i.id', '=', 'r.inspeccion_id')
        ->join('tipo_inspecciones as ti', 'ti.id', '=', 'i.tipo_inspeccion_id')
        ->join('preguntas_inspecciones as p', 'p.id', '=', 'r.pregunta_inspeccion_id')
        ->join('sedes as s', 's.id', '=', 'i.sede_id')
        ->join('users as u', 'u.id', '=', 'i.responsable_id')
        ->where('r.respuesta', 0);

    // 🔥 SOLO ESTE FILTRO
    if (!empty($filters['inspeccion_id'])) {
        $query->where('i.id', $filters['inspeccion_id']);
    }

    return $query->select(
        'ti.nombre as tipo_inspeccion',
        'p.pregunta',
        'r.observaciones',
        's.nombre as sede',
        'u.name as responsable',
        'i.fecha'
    )
    ->orderByDesc('i.fecha')
    ->get();
}
public function getInspeccionesFinalizadas()
{
    return DB::table('inspecciones_hseq as i')
        ->join('tipo_inspecciones as ti', 'ti.id', '=', 'i.tipo_inspeccion_id')
        ->join('respuesta_inspecciones as r', 'r.inspeccion_id', '=', 'i.id')
        ->where('i.estado', 'finalizada')
        ->where('r.respuesta', 0) // 🔥 SOLO LAS QUE TIENEN FALLAS
        ->select(
            'i.id',
            'ti.nombre as tipo',
            'i.fecha'
        )
        ->distinct()
        ->orderByDesc('i.fecha')
        ->limit(50)
        ->get();
}
}