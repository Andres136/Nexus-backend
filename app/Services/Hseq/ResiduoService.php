<?php

namespace App\Services\Hseq;

use App\Models\Hseq\Residuo;
use App\Models\User;

class ResiduoService
{
    public function create(array $data)
    {
        return Residuo::create($data);
    }
public function getAll(array $filters)
{
    $query = Residuo::with(['tipoResiduo', 'sede']);

    // 🔎 SEARCH
    if (!empty($filters['search'])) {
        $search = $filters['search'];

        $query->where(function ($q) use ($search) {
            $q->where('nombre', 'like', "%{$search}%")
              ->orWhere('descripcion', 'like', "%{$search}%");
        });
    }

    // 🏢 FILTRO POR SEDE
    if (!empty($filters['sede_id'])) {
        $query->where('sede_id', $filters['sede_id']);
    }

    // 📅 FILTRO POR FECHAS
    if (!empty($filters['fecha_inicio']) && !empty($filters['fecha_fin'])) {
        $query->whereBetween('fecha', [
            $filters['fecha_inicio'],
            $filters['fecha_fin']
        ]);
    }

    // 📊 ORDENAMIENTO
    $sortBy = $filters['sort_by'] ?? 'fecha';
    $sortDirection = $filters['sort_direction'] ?? 'desc';

    $query->orderBy($sortBy, $sortDirection);

    // 📄 PAGINACIÓN
    $perPage = $filters['per_page'] ?? 10;

    return $query->paginate($perPage);
}
    public function find($id)
    {
        return Residuo::findOrFail($id);
    }

    public function update($id, array $data)
    {
        $residuo = $this->find($id);
        $residuo->update($data);
        return $residuo;
    }

    public function delete($id)
    {
        $residuo = $this->find($id);
        $residuo->delete();
        return true;
    } 

      public function timeline(array $filters)
    {
               $sedeId = $filters['sede_id'] ?? null;
        $anio = $filters['anio'] ?? date('Y');

        $query = Residuo::query();

        if ($sedeId) {
            $query->where('sede_id', $sedeId);
        }

        // consulta agrupada por mes
        $residuos = $query
            ->selectRaw('
                MONTH(fecha) as mes_num,
                MONTHNAME(fecha) as mes,
                SUM(cantidad) as total
            ')
            ->whereYear('fecha', $anio)
            ->groupBy('mes_num', 'mes')
            ->orderBy('mes_num')
            ->get();

        // contar usuarios de la sede
        $usuarios = 0;

        if ($sedeId) {
            $usuarios = User::where('sede_id', $sedeId)->count();
        }

        // construir timeline
        $timeline = $residuos->map(function ($item) use ($usuarios) {

            $perCapita = null;

            if ($usuarios > 0) {
                $perCapita = round($item->total / $usuarios, 2);
            }

            return [
                'mes_num' => $item->mes_num,
                'mes' => $item->mes,
                'total_residuos' => $item->total,
                'usuarios' => $usuarios,
                'residuo_per_capita' => $perCapita
            ];
        });

        return [
            'anio' => $anio,
            'sede_id' => $sedeId,
            'timeline' => $timeline
        ];

    }


    public function residuosTimeline(array $filters)
{
    $year = $filters['anio'] ?? date('Y');

    $query = Residuo::query();

    // 🏢 filtro sede
    if (!empty($filters['sede_id'])) {
        $query->where('sede_id', $filters['sede_id']);
    }

    // 🔎 search por tipo residuo
    if (!empty($filters['search'])) {
        $query->whereHas('tipoResiduo', function ($q) use ($filters) {
            $q->where('nombre', 'like', "%{$filters['search']}%");
        });
    }

    // ⚙️ filtro por tipo residuo
    if (!empty($filters['tipo_residuo_id'])) {
        $query->whereIn('tipo_residuo_id', (array) $filters['tipo_residuo_id']);
    }

    // 📅 filtro fechas
    if (!empty($filters['fecha_inicio']) && !empty($filters['fecha_fin'])) {
        $query->whereBetween('fecha', [
            $filters['fecha_inicio'],
            $filters['fecha_fin']
        ]);
    }

    // 📊 agrupación por mes
    $data = $query->selectRaw('
        MONTH(fecha) as mes,
        SUM(cantidad) as total
    ')
    ->whereYear('fecha', $year)
    ->groupBy('mes')
    ->orderBy('mes')
    ->get()
    ->keyBy('mes');

    // 👥 usuarios activos (igual que consumo)
    $usuarios = User::where('estado_id', 3)
        ->when(!empty($filters['sede_id']), function ($q) use ($filters) {
            $q->where('sede_id', $filters['sede_id']);
        })
        ->count();

    // 📅 timeline completo (12 meses)
    $meses = collect(range(1, 12))->map(function ($mes) use ($data, $usuarios) {

        $registro = $data->get($mes);
        $total = $registro ? (float) $registro->total : 0;

        return [
            'mes_num' => $mes,
            'mes' => \Carbon\Carbon::create()->month($mes)->translatedFormat('F'),
            'total_residuos' => $total,
            'usuarios' => $usuarios,
            'residuo_per_capita' => $usuarios > 0
                ? round($total / $usuarios, 2)
                : null
        ];
    });

    return [
        'data' => [
            'anio' => $year,
            'sede_id' => $filters['sede_id'] ?? null,
            'timeline' => $meses
        ]
    ];
}
}