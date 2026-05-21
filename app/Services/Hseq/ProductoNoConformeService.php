<?php

namespace App\Services\Hseq;

use App\Models\Hseq\ProductoNoConforme;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ProductoNoConformeService
{
    private array $with = [
        'cliente:id,nombre',
        'comercial:id,name',
        'producto:id,nombre',
        'ordenCompra:id,numero_orden',
        'estado:id,nombre,color',
        'analisis',
    ];

    public function listar(array $filtros)
    {
        $query = ProductoNoConforme::with($this->with);

        if (!empty($filtros['search'])) {
            $search = $filtros['search'];
            $query->where(function ($q) use ($search) {
                $q->where('descripcion_inicial', 'like', "%{$search}%")
                  ->orWhere('tipo_falla', 'like', "%{$search}%");
            });
        }

        if (!empty($filtros['cliente_id'])) {
            $query->where('cliente_id', $filtros['cliente_id']);
        }

        if (!empty($filtros['estado_id'])) {
            $query->where('estado_id', $filtros['estado_id']);
        }

        if (!empty($filtros['tipo_falla'])) {
            $query->where('tipo_falla', $filtros['tipo_falla']);
        }

        if (!empty($filtros['fecha_desde'])) {
            $query->whereDate('fecha_reporte', '>=', $filtros['fecha_desde']);
        }

        if (!empty($filtros['fecha_hasta'])) {
            $query->whereDate('fecha_reporte', '<=', $filtros['fecha_hasta']);
        }

        $perPage = $filtros['per_page'] ?? 15;

        return $query->latest()->paginate($perPage);
    }

    public function crear(array $data): ProductoNoConforme
    {
        return ProductoNoConforme::create($data);
    }

    public function show(int $id): ProductoNoConforme
    {
        return ProductoNoConforme::with($this->with)->findOrFail($id);
    }

    public function actualizar(int $id, array $data): ProductoNoConforme
    {
        $producto = ProductoNoConforme::findOrFail($id);
        $producto->update($data);

        return $producto->load($this->with);
    }

    public function cambiarEstado(int $id, int $estadoId): ProductoNoConforme
    {
        $producto = ProductoNoConforme::findOrFail($id);
        $producto->update(['estado_id' => $estadoId]);

        return $producto->load($this->with);
    }

    public function estadisticas(array $filtros): array
    {
        $query = ProductoNoConforme::query();

        if (!empty($filtros['anio'])) {
            $query->whereYear('fecha_reporte', $filtros['anio']);
        }

        if (!empty($filtros['cliente_id'])) {
            $query->where('cliente_id', $filtros['cliente_id']);
        }

        if (!empty($filtros['fecha_desde'])) {
            $query->whereDate('fecha_reporte', '>=', $filtros['fecha_desde']);
        }

        if (!empty($filtros['fecha_hasta'])) {
            $query->whereDate('fecha_reporte', '<=', $filtros['fecha_hasta']);
        }

        $ids = (clone $query)->pluck('id');

        // 🔹 Por mes
        $porMes = (clone $query)
            ->select(
                DB::raw('MONTH(fecha_reporte) as mes'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(cantidad_afectada) as cantidad_afectada')
            )
            ->groupBy(DB::raw('MONTH(fecha_reporte)'))
            ->orderBy('mes')
            ->get()
            ->map(fn($r) => [
                'mes'               => $r->mes,
                'nombre_mes'        => Carbon::create()->month($r->mes)->translatedFormat('F'),
                'total'             => $r->total,
                'cantidad_afectada' => $r->cantidad_afectada,
            ]);

        // 🔹 Top productos con más no conformidades
        $topProductos = (clone $query)
            ->select(
                'producto_id',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(cantidad_afectada) as cantidad_afectada')
            )
            ->whereNotNull('producto_id')
            ->with('producto:id,nombre')
            ->groupBy('producto_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn($r) => [
                'producto'          => $r->producto?->nombre,
                'total'             => $r->total,
                'cantidad_afectada' => $r->cantidad_afectada,
            ]);

        // 🔹 Top clientes con más no conformidades
        $topClientes = (clone $query)
            ->select(
                'cliente_id',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(cantidad_afectada) as cantidad_afectada')
            )
            ->with('cliente:id,nombre')
            ->groupBy('cliente_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn($r) => [
                'cliente'           => $r->cliente?->nombre,
                'total'             => $r->total,
                'cantidad_afectada' => $r->cantidad_afectada,
            ]);

        // 🔹 Distribución por tipo de falla
        $porTipoFalla = (clone $query)
            ->select('tipo_falla', DB::raw('COUNT(*) as total'))
            ->whereNotNull('tipo_falla')
            ->groupBy('tipo_falla')
            ->orderByDesc('total')
            ->get();

        // 🔹 Distribución por estado
        $porEstado = (clone $query)
            ->select('estado_id', DB::raw('COUNT(*) as total'))
            ->with('estado:id,nombre,color')
            ->groupBy('estado_id')
            ->get()
            ->map(fn($r) => [
                'estado' => $r->estado?->nombre,
                'color'  => $r->estado?->color,
                'total'  => $r->total,
            ]);

        // 🔹 Con análisis vs sin análisis
        $conAnalisis    = DB::table('analisis_productos_no_conformes')->whereIn('producto_no_conforme_id', $ids)->count();
        $total          = $ids->count();
        $sinAnalisis    = $total - $conAnalisis;

        return [
            'resumen' => [
                'total'             => $total,
                'cantidad_afectada' => (clone $query)->sum('cantidad_afectada'),
                'con_analisis'      => $conAnalisis,
                'sin_analisis'      => $sinAnalisis,
            ],
            'por_mes'        => $porMes,
            'top_productos'  => $topProductos,
            'top_clientes'   => $topClientes,
            'por_tipo_falla' => $porTipoFalla,
            'por_estado'     => $porEstado,
        ];
    }
}
