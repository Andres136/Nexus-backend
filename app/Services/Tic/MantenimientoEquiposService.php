<?php

namespace App\Services\Tic;

use App\Models\Tic\MantenimientoEquipos;

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
            'observaciones' => $data['observaciones'],
            'estado' => $data['estado'] ?? 'pendiente',
            'costo' => $data['costo'],
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
        'PENDIENTE' => '#f59e0b',
        'EN_PROCESO' => '#3b82f6',
        'COMPLETADO' => '#10b981',
        default => '#6b7280',
    };
}

}