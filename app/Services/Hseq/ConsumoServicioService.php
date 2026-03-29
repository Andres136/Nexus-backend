<?php

namespace App\Services\Hseq;

use App\Models\Hseq\ConsumoServicio;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ConsumoServicioService
{
 public function create(array $data)
    {
        // contar personas en la sede
        $personas = User::where('sede_id', $data['sede_id'])->count();

        // calcular consumo per capita
        $consumoPercapita = null;

        if ($personas > 0) {
            $consumoPercapita = $data['consumo'] / $personas;
        }

        // crear registro
        return ConsumoServicio::create([
            'sede_id' => $data['sede_id'],
            'tipo_servicio_id' => $data['tipo_servicio_id'],
            'consumo' => $data['consumo'],
            'fecha_consumo' => $data['fecha_consumo'],
            'fecha_pago' => $data['fecha_pago'] ?? null,
            'valor_factura' => $data['valor_factura'] ?? null,
            'estado' => $data['estado'] ?? 'pendiente',
            'consumo_percapita' => $consumoPercapita
        ]);   
    }


    //Resto de métodos para find, update, delete, etc.



public function find($id)
    {
        return ConsumoServicio::findOrFail($id);
    }

    public function update($id, array $data)
    {
        $consumoServicio = $this->find($id);
        $consumoServicio->update($data);
        return $consumoServicio;
    }

    public function delete($id)
    {
        $consumoServicio = $this->find($id);
        return $consumoServicio->delete();
    }

    public function estadisticasAnuales($year, $tipoServicioId = null)
{
    $query = ConsumoServicio::select(
        DB::raw('MONTH(fecha_consumo) as mes'),
        DB::raw('SUM(consumo) as total_consumo')
    )
    ->whereYear('fecha_consumo', $year);

    if ($tipoServicioId) {
        $query->where('tipo_servicio_id', $tipoServicioId);
    }

    $data = $query
        ->groupBy(DB::raw('MONTH(fecha_consumo)'))
        ->orderBy('mes')
        ->get();

    $totalAnual = $data->sum('total_consumo');

    return $data->map(function ($item) use ($totalAnual) {
        $item->porcentaje = $totalAnual > 0
            ? round(($item->total_consumo / $totalAnual) * 100, 2)
            : 0;

        return $item;
    });
}

public function getAll(array $filters)
{
    $query = ConsumoServicio::with(['sede', 'tipoServicio']);

    //  SEARCH (puedes ajustar campos)
    if (!empty($filters['search'])) {
        $search = $filters['search'];

        $query->where(function ($q) use ($search) {
            $q->where('estado', 'like', "%{$search}%")
              ->orWhere('consumo', 'like', "%{$search}%")
              ->orWhereHas('tipoServicio', function ($q2) use ($search) {
                  $q2->where('nombre', 'like', "%{$search}%");
              })
              ->orWhereHas('sede', function ($q2) use ($search) {
                  $q2->where('nombre', 'like', "%{$search}%");
              });
        });
    }

    //  FILTRO POR SEDE
    if (!empty($filters['sede_id'])) {
        $query->where('sede_id', $filters['sede_id']);
    }

    //  FILTRO POR TIPO SERVICIO
    if (!empty($filters['tipo_servicio_id'])) {
        $query->where('tipo_servicio_id', $filters['tipo_servicio_id']);
    }

    //  FILTRO POR RANGO DE FECHA CONSUMO
    if (!empty($filters['fecha_inicio']) && !empty($filters['fecha_fin'])) {
        $query->whereBetween('fecha_consumo', [
            $filters['fecha_inicio'],
            $filters['fecha_fin']
        ]);
    }

    //  FILTRO POR ESTADO
    if (!empty($filters['estado'])) {
        $query->where('estado', $filters['estado']);
    }

    //  ORDENAMIENTO
    $sortBy = $filters['sort_by'] ?? 'fecha_consumo';
    $sortDirection = $filters['sort_direction'] ?? 'desc';

    $query->orderBy($sortBy, $sortDirection);

    //  PAGINACIÓN
    $perPage = $filters['per_page'] ?? 10;

    return $query->paginate($perPage);
}

public function consumoTimeline(array $filters)
{
    $year = $filters['anio'] ?? date('Y');

    $query = ConsumoServicio::query()
        ->whereYear('fecha_consumo', $year);

    if (!empty($filters['sede_id'])) {
        $query->where('sede_id', $filters['sede_id']);
    }

    if (!empty($filters['search'])) {
        $query->whereHas('tipoServicio', function ($q) use ($filters) {
            $q->where('nombre', 'like', "%{$filters['search']}%");
        });
    }

    if (!empty($filters['tipo_servicio_id'])) {
        $query->whereIn('tipo_servicio_id', (array) $filters['tipo_servicio_id']);
    }

    $data = $query->selectRaw('
        MONTH(fecha_consumo) as mes,
        SUM(consumo) as total
    ')
    ->groupBy('mes')
    ->orderBy('mes')
    ->get()
    ->keyBy('mes');

    $usuarios = User::where('estado_id', 3)
        ->when(!empty($filters['sede_id']), fn($q) => $q->where('sede_id', $filters['sede_id']))
        ->count();

    $meses = collect(range(1, 12))->map(function ($mes) use ($data, $usuarios) {

        $registro = $data->get($mes);
        $total = $registro ? (float) $registro->total : 0;

        return [
            'mes_num' => $mes,
            'mes' => \Carbon\Carbon::create()->month($mes)->translatedFormat('F'),
            'total_consumo' => $total,
            'usuarios' => $usuarios,
            'consumo_percapita' => $usuarios > 0
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