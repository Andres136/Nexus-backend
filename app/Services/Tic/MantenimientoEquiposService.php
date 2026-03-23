<?php

namespace App\Services\Tic;

use App\Models\Tic\MantenimientoEquipos;
use Illuminate\Support\Facades\DB;

class MantenimientoEquiposService
{
    public function registrarMantenimiento(array $data)
    {
        $query = MantenimientoEquipos::create([
            'sede_id' => $data['sede_id'],
            'producto_id' => $data['producto_id'],
            'empresa_id' => $data['empresa_id'],
            'usuario_id' => auth()->id(),
            'tipo' => $data['tipo'],
            'fecha_programada' => $data['fecha_programada'],
            'fecha_ejecucion' => $data['fecha_ejecucion'] ?? null,
            'observaciones' => $data['observaciones'] ?? null,
            'estado' => $data['estado'] ?? 'pendiente',
            'costo' => $data['costo'] ?? null,
        ]);
        return $query;
    }


    //Traer mantenimientos 
    public function obtenerMantenimientos()
    {
      $mantenimientos = MantenimientoEquipos::with([
    'producto',
    'sede',
    'empresa',
    'usuario',
    'asignacion.usuarioRecibe',
])->get()
     ->map(function ($m) {
    return [
        'id' => $m->id,
        'title' => $m->producto->name . ' - ' . ucfirst($m->tipo),
        'start' => $m->fecha_programada,
        'backgroundColor' => $this->colorEstado($m->estado),

        'extendedProps' => [
            'producto_id' => $m->producto_id,
            'sede_id' => $m->sede_id,
            'empresa_id' => $m->empresa_id,
            'usuario_id' => $m->usuario_id,
            'asignado_a' => $m->asignacion?->usuarioRecibe?->name,

            'producto' => $m->producto?->name,
            'sede' => $m->sede?->nombre,
            'empresa' => $m->empresa?->nombre,
            'usuario' => $m->usuario?->name,

            'estado' => $m->estado,
            'tipo' => $m->tipo,
            'costo' => $m->costo,
            'observaciones' => $m->observaciones,
        ],
    ];
});
        return $mantenimientos;
    }
private function colorEstado($estado)
{
    return match($estado) {
        'pendiente' => '#f59e0b',   // amarillo
        'en_proceso' => '#3b82f6',  // azul
        'completado' => '#10b981',  // verde
        default => '#6b7280',       // gris
    };
}


//Lstar mantenimientos  todos los mantenimientos xon filtros
public function listarMantenimientos(array $filters = [])
{
    $query = MantenimientoEquipos::with(['producto', 'sede', 'empresa', 'usuario', 'asignacion.usuarioRecibe', 'archivos']);

    if (!empty($filters['sede_id'])) {
        $query->where('sede_id', $filters['sede_id']);
    }
    if (!empty($filters['producto_id'])) {
        $query->where('producto_id', $filters['producto_id']);
    }
    if (!empty($filters['empresa_id'])) {
        $query->where('empresa_id', $filters['empresa_id']);
    }
    if (!empty($filters['tipo'])) {
        $query->where('tipo', $filters['tipo']);
    }
    if (!empty($filters['estado'])) {
        $query->where('estado', $filters['estado']);
    }

       $query->orderByRaw("
        FIELD(estado, 'pendiente', 'en_proceso', 'completado')
    ");

    // Orden secundario por fecha programada (opcional pero recomendado)
    $query->orderBy('fecha_programada', 'asc');

    return $query->paginate($filters['per_page'] ?? 10);
}

 //Metodo para actualizar el estado del mantenimiento

public function cambiarEstado($id, array $data, $archivos = null)
{
    $mantenimiento = MantenimientoEquipos::findOrFail($id);

    if ($data['estado'] === 'completado' && empty($archivos)) {
        throw new \Exception('Debe adjuntar al menos un archivo para completar el mantenimiento.');
    }

    if ($data['estado'] === 'completado') {
        $mantenimiento->fecha_ejecucion = now();
    }

    $mantenimiento->estado = $data['estado'];
    $mantenimiento->save();

    // Guardar múltiples archivos
    if ($archivos && is_array($archivos)) {

        foreach ($archivos as $archivo) {

            $ruta = $archivo->store('mantenimientos', 'public');

            $mantenimiento->archivos()->create([
                'archivo' => $ruta,
                'tipo' => $data['tipo_archivo'] ?? 'evidencia',
                'descripcion' => $data['descripcion'] ?? null,
            ]);
        }
    }

    return $mantenimiento->load('archivos');
}

//Actualizar mantenimiento
public function actualizarMantenimiento($id, array $data)
{
    $mantenimiento = MantenimientoEquipos::findOrFail($id);

    $mantenimiento->update([
        'sede_id' => $data['sede_id'] ?? $mantenimiento->sede_id,
        'producto_id' => $data['producto_id'] ?? $mantenimiento->producto_id,
        'empresa_id' => $data['empresa_id'] ?? $mantenimiento->empresa_id,
        'tipo' => $data['tipo'] ?? $mantenimiento->tipo,
        'fecha_programada' => $data['fecha_programada'] ?? $mantenimiento->fecha_programada,
        'observaciones' => $data['observaciones'] ?? $mantenimiento->observaciones,
        'costo' => $data['costo'] ?? $mantenimiento->costo,
    ]);

    return $mantenimiento;
}


public function estadisticasMensuales($year = null)
{
    $year = $year ?? now()->year;

    $result = MantenimientoEquipos::selectRaw("
            MONTH(fecha_programada) as mes,
            COUNT(*) as total,
            SUM(CASE WHEN estado = 'completado' THEN 1 ELSE 0 END) as completados
        ")
        ->whereYear('fecha_programada', $year)
        ->groupBy(DB::raw('MONTH(fecha_programada)'))
        ->orderBy('mes')
        ->get()
        ->map(function ($item) {
            $porcentaje = $item->total > 0 
                ? round(($item->completados / $item->total) * 100, 2)
                : 0;

            return [
                'mes' => $item->mes,
                'total' => $item->total,
                'completados' => $item->completados,
                'porcentaje_cumplimiento' => $porcentaje,
            ];
        });

    return $result;
}
}