<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\EncuestaEnvioRequest;
use App\Http\Requests\Crm\EncuestaRequest;
use App\Http\Requests\Crm\EncuestaRespuestaRequest;
use App\Services\Crm\EncuestaService;
use Illuminate\Http\Request;

class EncuestaController extends Controller
{
    public function __construct(protected EncuestaService $encuestaService) {}

    public function index(Request $request)
    {
        return response()->json(
            $this->encuestaService->index(auth()->user())
        );
    }

    public function store(EncuestaRequest $request)
    {
        $encuesta = $this->encuestaService->store($request->validated(), auth()->user());

        return response()->json([
            'message'  => 'Encuesta creada con éxito',
            'encuesta' => $encuesta,
        ], 201);
    }

    public function show(string $id)
    {
        return response()->json(
            $this->encuestaService->show((int) $id, auth()->user())
        );
    }

    public function update(EncuestaRequest $request, string $id)
    {
        $encuesta = $this->encuestaService->update((int) $id, $request->validated(), auth()->user());

        return response()->json([
            'message'  => 'Encuesta actualizada correctamente',
            'encuesta' => $encuesta,
        ]);
    }

    public function destroy(string $id)
    {
        $this->encuestaService->destroy((int) $id, auth()->user());

        return response()->json(['message' => 'Encuesta eliminada correctamente']);
    }

    public function enviar(EncuestaEnvioRequest $request, string $id)
    {
        $links = $this->encuestaService->enviar(
            (int) $id,
            $request->validated()['cliente_ids'],
            auth()->user()
        );

        return response()->json([
            'message' => 'Encuesta enviada correctamente',
            'links'   => $links,
        ]);
    }

    public function resultados(string $id)
    {
        return response()->json(
            $this->encuestaService->resultados((int) $id, auth()->user())
        );
    }

    // ─── Rutas públicas (sin auth) ────────────────────────────────────────────

    public function showPublico(string $token)
    {
        return response()->json(
            $this->encuestaService->showPublico($token)
        );
    }

    public function responder(EncuestaRespuestaRequest $request, string $token)
    {
        $this->encuestaService->responderPublico($token, $request->validated()['respuestas']);

        return response()->json(['message' => '¡Gracias por responder la encuesta!']);
    }
}
