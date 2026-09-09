<?php

namespace App\Services\Hseq;


use App\Models\Hseq\RespuestaInspeccion;
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

        if (!empty($filters['bodega_id'])) {
            $query->where('i.bodega_id', $filters['bodega_id']);
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
            ->where('r.respuesta', 0)
            ->whereNull('r.cerrado_en'); // 🔥 solo fallas abiertas (sin cerrar)

        $noCumple = $this->applyFilters($noCumple, $filters)->count();

        $noCumpleCerradas = DB::table('respuesta_inspecciones as r')
            ->join('inspecciones_hseq as i', 'i.id', '=', 'r.inspeccion_id')
            ->where('r.respuesta', 0)
            ->whereNotNull('r.cerrado_en'); // 🔥 fallas ya cerradas

        $noCumpleCerradas = $this->applyFilters($noCumpleCerradas, $filters)->count();

        return [
            'total_inspecciones' => $total,
            'finalizadas' => $finalizadas,
            'pendientes' => $pendientes,
            'porcentaje_cumplimiento' => $total > 0 ? round(($finalizadas / $total) * 100, 2) : 0,
            'total_fallas' => $noCumple,
            'total_fallas_cerradas' => $noCumpleCerradas,
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
        ->leftJoin('users as cu', 'cu.id', '=', 'r.cerrado_por')
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

    if (!empty($filters['estado_cierre']) && $filters['estado_cierre'] === 'abierto') {
        $query->whereNull('r.cerrado_en');
    } elseif (!empty($filters['estado_cierre']) && $filters['estado_cierre'] === 'cerrado') {
        $query->whereNotNull('r.cerrado_en');
    }

    return $query->select(
        'r.id',
        'r.inspeccion_id',
        'ti.nombre as tipo_inspeccion', // nombre de la inspección
        'p.pregunta',                   // 🔥 AQUÍ ESTÁ EL TEXTO REAL
        'r.observaciones',
        's.nombre as sede',             // nombre de la sede
        'u.name as responsable',        // nombre del responsable
        'i.fecha',
        'r.foto_cierre',
        'r.observaciones_cierre',
        'r.cerrado_en',
        'cu.name as cerrado_por_nombre'
    )->orderByRaw('r.cerrado_en IS NOT NULL')
    ->orderByDesc('i.fecha')
    ->paginate(10);
}

/**
 * 🔹 Cerrar un hallazgo (falla detectada) con foto obligatoria
 */
public function cerrarHallazgo($id, $fotoFile, $observaciones = null)
{
    $hallazgo = RespuestaInspeccion::where('respuesta', 0)->findOrFail($id);

    if ($hallazgo->cerrado_en) {
        throw new \RuntimeException('Este hallazgo ya fue cerrado.');
    }

    $path = $fotoFile->store('hseq/hallazgos', 'public');

    $hallazgo->update([
        'foto_cierre' => $path,
        'observaciones_cierre' => $observaciones,
        'cerrado_en' => now(),
        'cerrado_por' => auth()->id(),
    ]);

    return $hallazgo;
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
        ->leftJoin('bodegas as bo', 'bo.id', '=', 'i.bodega_id')
        ->where('r.respuesta', 0);

    // 🔥 Respeta los mismos filtros del dashboard (fecha, sede, bodega, responsable, estado)
    $query = $this->applyFilters($query, $filters);

    if (!empty($filters['inspeccion_id'])) {
        $query->where('i.id', $filters['inspeccion_id']);
    }

    return $query->select(
        'ti.nombre as tipo_inspeccion',
        'p.pregunta',
        'r.respuesta',
        'r.observaciones',
        's.nombre as sede',
        'bo.nombre as bodega',
        'u.name as responsable',
        'i.fecha',
        'r.cerrado_en'
    )
    ->orderByDesc('i.fecha')
    ->get();
}
/**
 * 🔹 Checklist completo de cada inspección para el PDF (todas las preguntas
 * con su resultado, no solo los hallazgos). Respeta los mismos filtros.
 */
public function checklistParaPdf($filters)
{
    $inspeccionesQuery = DB::table('inspecciones_hseq as i')
        ->join('tipo_inspecciones as ti', 'ti.id', '=', 'i.tipo_inspeccion_id')
        ->join('sedes as s', 's.id', '=', 'i.sede_id')
        ->leftJoin('users as u', 'u.id', '=', 'i.responsable_id')
        ->leftJoin('bodegas as bo', 'bo.id', '=', 'i.bodega_id');

    $inspeccionesQuery = $this->applyFilters($inspeccionesQuery, $filters);

    if (!empty($filters['inspeccion_id'])) {
        $inspeccionesQuery->where('i.id', $filters['inspeccion_id']);
    }

    $inspecciones = $inspeccionesQuery->select(
        'i.id',
        'i.fecha',
        'i.observaciones',
        'i.estado',
        'ti.nombre as tipo_inspeccion',
        's.nombre as sede',
        'bo.nombre as bodega',
        'u.name as responsable'
    )
        ->orderByDesc('i.fecha')
        ->limit(50) // tope de seguridad para no generar un PDF gigante
        ->get();

    if ($inspecciones->isEmpty()) {
        return collect();
    }

    $respuestasPorInspeccion = DB::table('respuesta_inspecciones as r')
        ->join('preguntas_inspecciones as p', 'p.id', '=', 'r.pregunta_inspeccion_id')
        ->whereIn('r.inspeccion_id', $inspecciones->pluck('id'))
        ->select('r.inspeccion_id', 'p.pregunta', 'p.orden', 'r.respuesta', 'r.observaciones', 'r.cerrado_en')
        ->orderBy('p.orden')
        ->orderBy('p.id')
        ->get()
        ->groupBy('inspeccion_id');

    return $inspecciones->map(function ($insp) use ($respuestasPorInspeccion) {
        $items = $respuestasPorInspeccion->get($insp->id, collect())->values();
        $total = $items->count();
        $cumple = $items->where('respuesta', 1)->count();

        $insp->items = $items;
        $insp->total_preguntas = $total;
        $insp->cumple = $cumple;
        $insp->no_cumple = $total - $cumple;
        $insp->cumplimiento = $total > 0 ? round(($cumple / $total) * 100, 1) : null;

        return $insp;
    });
}

public function getInspeccionesFinalizadas($search = null, $filters = [])
{
    $query = DB::table('inspecciones_hseq as i')
        ->join('tipo_inspecciones as ti', 'ti.id', '=', 'i.tipo_inspeccion_id')
        ->leftJoin('users as u', 'u.id', '=', 'i.responsable_id')
        ->where('i.estado', 'finalizada')
        ->select(
            'i.id',
            'ti.nombre as tipo',
            'i.fecha',
            'u.name as responsable_nombre',
            'u.apellidos as responsable_apellidos'
        )
        ->orderByDesc('i.fecha');

    $query = $this->applyFilters($query, $filters);

    if (!empty($search)) {
        $query->where(function ($q) use ($search) {
            $q->where('ti.nombre', 'like', "%{$search}%")
                ->orWhere('u.name', 'like', "%{$search}%")
                ->orWhere('u.apellidos', 'like', "%{$search}%")
                ->orWhere('i.fecha', 'like', "%{$search}%");
        });

        return $query->limit(200)->get();
    }

    return $query->limit(50)->get();
}
}