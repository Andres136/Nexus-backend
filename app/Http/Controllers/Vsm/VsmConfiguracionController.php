<?php

namespace App\Http\Controllers\Vsm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vsm\StoreVsmConfiguracionRequest;
use App\Http\Requests\Vsm\UpdateVsmConfiguracionRequest;
use App\Models\Vsm\VsmConfiguracion;
use App\Services\Vsm\VsmConfiguracionService;

class VsmConfiguracionController extends Controller
{
    protected VsmConfiguracionService $service;

    public function __construct()
    {
        $this->service = new VsmConfiguracionService();
    }

    /**
     * GET /api/vsm/configuracion
     */
    public function vigente()
    {
        $config = $this->service->vigente();

        if (!$config) {
            return response()->json([
                'message' => 'No hay una meta configurada aún.',
                'data'    => null,
            ], 200);
        }

        return response()->json(['data' => $this->formatear($config)]);
    }

    /**
     * GET /api/vsm/configuracion/historial
     */
    public function historial()
    {
        $registros = $this->service->historial();

        return response()->json(
            $registros->map(fn (VsmConfiguracion $r) => $this->formatear($r))
        );
    }

    /**
     * POST /api/vsm/configuracion
     * Crea nueva meta (desactiva la anterior).
     */
    public function store(StoreVsmConfiguracionRequest $request)
    {
        try {
            $usuarioId = $request->user()->id;

            $config = $this->service->crear($request->validated(), $usuarioId);

            return response()->json(
                $this->formatear($config->load('creadoPor:id,name')),
                201
            );
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * PUT /api/vsm/configuracion/{id}
     * Edita un registro existente (meta y descripción).
     */
    public function update(UpdateVsmConfiguracionRequest $request, int $id)
    {
        try {
            $config = $this->service->actualizar($id, $request->validated());

            return response()->json($this->formatear($config));
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * DELETE /api/vsm/configuracion/{id}
     * Elimina un registro inactivo.
     */
    public function destroy(int $id)
    {
        try {
            $this->service->eliminar($id);

            return response()->json(['message' => 'Registro eliminado.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * POST /api/vsm/configuracion/{id}/restaurar
     * Restaura un registro anterior como meta activa.
     */
    public function restaurar(int $id)
    {
        try {
            $config = $this->service->restaurar($id);

            return response()->json($this->formatear($config));
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    private function formatear(VsmConfiguracion $config): array
    {
        $horasDiarias = $config->horas_semanales / 5;

        return [
            'id'                 => $config->id,
            'meta_unidades_hora' => $config->meta_unidades_hora,
            'horas_semanales'    => $config->horas_semanales,
            'horas_diarias'      => round($horasDiarias, 2),
            'meta_diaria'        => round($config->meta_unidades_hora * $horasDiarias, 2),
            'meta_semanal'       => round($config->meta_unidades_hora * $config->horas_semanales, 2),
            'descripcion'        => $config->descripcion,
            'activo'             => $config->activo,
            'creado_por'         => $config->creadoPor?->name,
            'creado_en'          => $config->created_at?->format('Y-m-d H:i'),
        ];
    }
}
