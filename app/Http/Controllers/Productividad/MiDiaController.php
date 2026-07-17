<?php

namespace App\Http\Controllers\Productividad;

use App\Http\Controllers\Controller;
use App\Http\Requests\Productividad\BloquearActividadRequest;
use App\Http\Requests\Productividad\CompletarActividadRequest;
use App\Http\Requests\Productividad\IniciarActividadRequest;
use App\Models\Productividad\CategoriaActividad;
use App\Services\Productividad\MiDiaService;
use Illuminate\Http\Request;

class MiDiaController extends Controller
{
    public function __construct(private readonly MiDiaService $service)
    {
    }

    public function index(Request $request)
    {
        $fecha = $request->query('fecha');

        return response()->json([
            'message' => 'Estado de Mi Día obtenido exitosamente',
            'data' => $this->service->estadoDelDia($request->user()->id, $fecha),
        ]);
    }

    public function lineaTiempo(Request $request)
    {
        return response()->json([
            'message' => 'Línea de tiempo obtenida exitosamente',
            'data' => $this->service->lineaTiempo($request->user()->id, $request->query('fecha')),
        ]);
    }

    public function sugerenciasTarea(Request $request)
    {
        return response()->json([
            'message' => 'Sugerencias obtenidas exitosamente',
            'data' => $this->service->sugerenciasTarea($request->user()->id, $request->query('q')),
        ]);
    }

    public function categorias()
    {
        return response()->json([
            'message' => 'Categorías de actividad obtenidas exitosamente',
            'data' => CategoriaActividad::where('activo', true)->orderBy('nombre')->get(),
        ]);
    }

    public function iniciar(IniciarActividadRequest $request)
    {
        $actividad = $this->service->iniciarActividad($request->user()->id, $request->validated());

        return response()->json([
            'message' => 'Actividad iniciada exitosamente',
            'data' => $actividad,
        ], 201);
    }

    public function disponible(Request $request)
    {
        $actividad = $this->service->marcarDisponible($request->user()->id);

        return response()->json([
            'message' => 'Marcado como disponible',
            'data' => $actividad,
        ]);
    }

    public function completar(CompletarActividadRequest $request, string $uuid)
    {
        $actividad = $this->service->completarActividad($request->user()->id, $uuid, $request->validated());

        return response()->json([
            'message' => 'Actividad completada exitosamente',
            'data' => $actividad,
        ]);
    }

    public function bloquear(BloquearActividadRequest $request, string $uuid)
    {
        $actividad = $this->service->bloquearActividad($request->user()->id, $uuid, $request->validated());

        return response()->json([
            'message' => 'Actividad bloqueada',
            'data' => $actividad,
        ]);
    }

    public function cancelar(Request $request, string $uuid)
    {
        $actividad = $this->service->cancelarActividad($request->user()->id, $uuid);

        return response()->json([
            'message' => 'Actividad cancelada',
            'data' => $actividad,
        ]);
    }

    public function reanudar(Request $request, string $uuid)
    {
        return response()->json([
            'message' => 'Actividad reanudada exitosamente',
            'data' => $this->service->reanudarActividad($request->user()->id, $uuid),
        ]);
    }
}
