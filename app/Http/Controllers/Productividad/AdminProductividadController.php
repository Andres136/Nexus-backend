<?php

namespace App\Http\Controllers\Productividad;

use App\Exports\GenericExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Productividad\CorregirActividadRequest;
use App\Http\Requests\Productividad\DetalleProductividadRequest;
use App\Services\Productividad\AdminProductividadService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class AdminProductividadController extends Controller
{
    public function __construct(private readonly AdminProductividadService $service)
    {
    }

    private function filtrosDesde(Request $request): array
    {
        return [
            'fecha' => $request->query('fecha'),
            'estado' => $request->query('estado'),
            'search' => $request->query('search'),
            'per_page' => $request->query('per_page', 15),
        ];
    }

    public function equipo(Request $request)
    {
        return response()->json([
            'message' => 'Equipo del día obtenido exitosamente',
            'data' => $this->service->equipoDelDia($this->filtrosDesde($request)),
        ]);
    }

    public function usuario(DetalleProductividadRequest $request, int $id)
    {
        return response()->json([
            'message' => 'Detalle de productividad obtenido exitosamente',
            'data' => $this->service->detalleUsuario(
                $id,
                $request->validated('fecha_inicio'),
                $request->validated('fecha_fin')
            ),
        ]);
    }

    public function exportar(Request $request)
    {
        $filas = $this->service->filasParaExportar($this->filtrosDesde($request));

        if ($filas->isEmpty()) {
            return response()->json([
                'message' => 'No hay registros de productividad para exportar con los filtros seleccionados.',
            ], 422);
        }

        $exportable = $filas->map(fn (array $fila) => [
            'Empleado' => $fila['usuario']?->name,
            'Email' => $fila['usuario']?->email,
            'Estado' => $fila['estado'],
            'Hora entrada' => optional($fila['work_session']['hora_entrada'])->format('Y-m-d H:i'),
            'Hora salida' => optional($fila['work_session']['hora_salida'])->format('Y-m-d H:i'),
            'Actividad actual' => $fila['actividad_actual']['titulo'] ?? null,
            'Tareas pendientes' => $fila['tareas']['pendientes'],
            'Tareas completadas' => $fila['tareas']['completadas'],
            'Tareas bloqueadas' => $fila['tareas']['bloqueadas'],
            'Minutos clasificable' => $fila['resumen']['minutos_clasificable'] ?? null,
            'Minutos tarea' => $fila['resumen']['minutos_tarea'] ?? null,
            'Minutos otra actividad' => $fila['resumen']['minutos_otra_actividad'] ?? null,
            'Minutos disponible' => $fila['resumen']['minutos_disponible'] ?? null,
            'Minutos sin clasificar' => $fila['resumen']['minutos_sin_clasificar'] ?? null,
        ]);

        $headings = array_keys($exportable->first());
        $filename = 'productividad_equipo_' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(new GenericExport($exportable, $headings), $filename);
    }

    public function corregir(CorregirActividadRequest $request, string $uuid)
    {
        $resultado = $this->service->corregirActividad(
            $request->user()->id,
            $uuid,
            $request->validated()
        );

        return response()->json([
            'message' => 'Actividad corregida exitosamente',
            'data' => $resultado,
        ]);
    }
}
