<?php

namespace App\Http\Controllers;

use App\Http\Requests\CapacitacionEncuestaEnvioRequest;
use App\Http\Requests\CapacitacionEncuestaRequest;
use App\Http\Requests\CapacitacionEncuestaRespuestaRequest;
use App\Services\CapacitacionEncuestaService;
use Illuminate\Http\Request;

class CapacitacionEncuestaController extends Controller
{
    public function __construct(protected CapacitacionEncuestaService $service) {}

    public function index(Request $request)
    {
        return response()->json(
            $this->service->index($request->only(['search', 'capacitacion_uuid', 'estado']))
        );
    }

    public function store(CapacitacionEncuestaRequest $request)
    {
        $encuesta = $this->service->store($request->validated(), $request->user());

        return response()->json([
            'message' => 'Encuesta creada correctamente.',
            'encuesta' => $encuesta,
        ], 201);
    }

    public function show(string $uuid)
    {
        return response()->json($this->service->show($uuid));
    }

    public function update(CapacitacionEncuestaRequest $request, string $uuid)
    {
        $encuesta = $this->service->update($uuid, $request->validated(), $request->user());

        return response()->json([
            'message' => 'Encuesta actualizada correctamente.',
            'encuesta' => $encuesta,
        ]);
    }

    public function destroy(Request $request, string $uuid)
    {
        $this->service->destroy($uuid, $request->user());

        return response()->json(['message' => 'Encuesta eliminada correctamente.']);
    }

    public function usuarios(Request $request)
    {
        return response()->json(
            $this->service->usuarios($request->only(['search', 'sede_id']))
        );
    }

    public function enviar(CapacitacionEncuestaEnvioRequest $request, string $uuid)
    {
        $resultado = $this->service->enviar(
            $uuid,
            $request->validated()['user_ids'],
            $request->user()
        );

        return response()->json([
            'message' => 'Encuesta enviada correctamente.',
            'links' => $resultado['links'],
            'excluidos' => $resultado['excluidos'],
        ]);
    }

    public function resultados(string $uuid)
    {
        return response()->json($this->service->resultados($uuid));
    }

    public function showPublica(string $token)
    {
        return response()->json($this->service->showPublica($token));
    }

    public function responderPublica(CapacitacionEncuestaRespuestaRequest $request, string $token)
    {
        $this->service->responderPublica($token, $request->validated()['respuestas']);

        return response()->json(['message' => 'Gracias por responder la encuesta.']);
    }
}
