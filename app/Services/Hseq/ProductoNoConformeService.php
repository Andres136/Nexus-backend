<?php

namespace App\Services\Hseq;

use App\Models\Hseq\ProductoNoConforme;
use App\Models\Hseq\ProductoNoConformeItem;
use App\Models\Traslados\Responsabilidad;
use App\Models\User;
use App\Notifications\Hseq\ProductoNoConformeRegistradoNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class ProductoNoConformeService
{
    private array $with = [
        'cliente:id,nombre',
        'proveedor:id,nombre',
        'comercial:id,name',
        'proceso:id,nombre',
        'items.producto:id,name',
        'ordenCompra:id,code',
        'ordenCompraProveedor:id,numero_orden',
        'estado:id,nombre',
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

        if (!empty($filtros['proveedor_id'])) {
            $query->where('proveedor_id', $filtros['proveedor_id']);
        }

        if (!empty($filtros['comercial_id'])) {
            $query->where('comercial_id', $filtros['comercial_id']);
        }

        if (!empty($filtros['origen'])) {
            $query->where('origen', $filtros['origen']);
        }

        if (!empty($filtros['proceso_id'])) {
            $query->where('proceso_id', $filtros['proceso_id']);
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
        $productos = $data['productos'] ?? [];
        unset($data['productos']);

        $reporte = ProductoNoConforme::create($data);

        foreach ($productos as $item) {
            $reporte->items()->create([
                'producto_id' => $item['producto_id'],
                'cantidad_afectada' => $item['cantidad_afectada'],
            ]);
        }

        $this->notificarResponsablesCalidad($reporte);

        return $reporte->load($this->with);
    }

    private function notificarResponsablesCalidad(ProductoNoConforme $reporte): void
    {
        $idCalidad = Responsabilidad::where('codigo', 'calidad')->value('id');

        if (! $idCalidad) {
            return;
        }

        $responsables = User::whereHas('responsabilidades', function ($q) use ($idCalidad) {
            $q->where('responsabilidades.id', $idCalidad)
              ->where('responsabilidades_user.activo', true);
        })->get();

        if ($responsables->isNotEmpty()) {
            Notification::send($responsables, new ProductoNoConformeRegistradoNotification($reporte));
        }
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

        if (!empty($filtros['comercial_id'])) {
            $query->where('comercial_id', $filtros['comercial_id']);
        }

        if (!empty($filtros['fecha_desde'])) {
            $query->whereDate('fecha_reporte', '>=', $filtros['fecha_desde']);
        }

        if (!empty($filtros['fecha_hasta'])) {
            $query->whereDate('fecha_reporte', '<=', $filtros['fecha_hasta']);
        }

        $ids = (clone $query)->pluck('id');

        // Un reporte ahora puede tener varios productos (producto_no_conforme_items),
        // así que cantidad_afectada ya no vive en productos_no_conformes: hay que
        // unir con la tabla de items para sumarla, o consultarla directamente.
        $conItems = fn () => (clone $query)
            ->leftJoin('producto_no_conforme_items', 'producto_no_conforme_items.producto_no_conforme_id', '=', 'productos_no_conformes.id');

        // 🔹 Por mes
        $porMes = $conItems()
            ->select(
                DB::raw('MONTH(productos_no_conformes.fecha_reporte) as mes'),
                DB::raw('COUNT(DISTINCT productos_no_conformes.id) as total'),
                DB::raw('COALESCE(SUM(producto_no_conforme_items.cantidad_afectada), 0) as cantidad_afectada')
            )
            ->groupBy(DB::raw('MONTH(productos_no_conformes.fecha_reporte)'))
            ->orderBy('mes')
            ->get()
            ->map(fn($r) => [
                'mes'               => $r->mes,
                'nombre_mes'        => Carbon::create()->month($r->mes)->translatedFormat('F'),
                'total'             => $r->total,
                'cantidad_afectada' => $r->cantidad_afectada,
            ]);

        // 🔹 Top productos con más no conformidades
        $topProductos = ProductoNoConformeItem::query()
            ->select(
                'producto_id',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(cantidad_afectada) as cantidad_afectada')
            )
            ->whereIn('producto_no_conforme_id', $ids)
            ->with('producto:id,name')
            ->groupBy('producto_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn($r) => [
                'producto'          => $r->producto?->name,
                'total'             => $r->total,
                'cantidad_afectada' => $r->cantidad_afectada,
            ]);

        // 🔹 Top clientes con más no conformidades
        $topClientes = $conItems()
            ->select(
                'productos_no_conformes.cliente_id',
                DB::raw('COUNT(DISTINCT productos_no_conformes.id) as total'),
                DB::raw('COALESCE(SUM(producto_no_conforme_items.cantidad_afectada), 0) as cantidad_afectada')
            )
            ->whereNotNull('productos_no_conformes.cliente_id')
            ->with('cliente:id,nombre')
            ->groupBy('productos_no_conformes.cliente_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn($r) => [
                'cliente'           => $r->cliente?->nombre,
                'total'             => $r->total,
                'cantidad_afectada' => $r->cantidad_afectada,
            ]);

        // 🔹 Top proveedores con más no conformidades
        $topProveedores = $conItems()
            ->select(
                'productos_no_conformes.proveedor_id',
                DB::raw('COUNT(DISTINCT productos_no_conformes.id) as total'),
                DB::raw('COALESCE(SUM(producto_no_conforme_items.cantidad_afectada), 0) as cantidad_afectada')
            )
            ->whereNotNull('productos_no_conformes.proveedor_id')
            ->with('proveedor:id,nombre')
            ->groupBy('productos_no_conformes.proveedor_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn($r) => [
                'proveedor'         => $r->proveedor?->nombre,
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
            ->with('estado:id,nombre')
            ->groupBy('estado_id')
            ->get()
            ->map(fn($r) => [
                'estado' => $r->estado?->nombre,
                'total'  => $r->total,
            ]);

        // 🔹 Distribución por origen (cliente / proveedor / interno)
        $porOrigen = $conItems()
            ->select(
                'productos_no_conformes.origen',
                DB::raw('COUNT(DISTINCT productos_no_conformes.id) as total'),
                DB::raw('COALESCE(SUM(producto_no_conforme_items.cantidad_afectada), 0) as cantidad_afectada')
            )
            ->groupBy('productos_no_conformes.origen')
            ->orderByDesc('total')
            ->get();

        // 🔹 Distribución por proceso (a través del usuario que reporta)
        $porProceso = $conItems()
            ->select(
                'productos_no_conformes.proceso_id',
                DB::raw('COUNT(DISTINCT productos_no_conformes.id) as total'),
                DB::raw('COALESCE(SUM(producto_no_conforme_items.cantidad_afectada), 0) as cantidad_afectada')
            )
            ->whereNotNull('productos_no_conformes.proceso_id')
            ->with('proceso:id,nombre')
            ->groupBy('productos_no_conformes.proceso_id')
            ->orderByDesc('total')
            ->get()
            ->map(fn($r) => [
                'proceso'           => $r->proceso?->nombre,
                'total'             => $r->total,
                'cantidad_afectada' => $r->cantidad_afectada,
            ]);

        // 🔹 Con análisis vs sin análisis
        $conAnalisis    = DB::table('analisis_productos_no_conformes')->whereIn('producto_no_conforme_id', $ids)->count();
        $total          = $ids->count();
        $sinAnalisis    = $total - $conAnalisis;

        return [
            'resumen' => [
                'total'             => $total,
                'cantidad_afectada' => ProductoNoConformeItem::whereIn('producto_no_conforme_id', $ids)->sum('cantidad_afectada'),
                'con_analisis'      => $conAnalisis,
                'sin_analisis'      => $sinAnalisis,
            ],
            'por_mes'        => $porMes,
            'top_productos'  => $topProductos,
            'top_clientes'   => $topClientes,
            'top_proveedores' => $topProveedores,
            'por_tipo_falla' => $porTipoFalla,
            'por_estado'     => $porEstado,
            'por_origen'     => $porOrigen,
            'por_proceso'    => $porProceso,
        ];
    }
}
