<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Services\Nomina\DescargoActaService;
use Illuminate\Http\Request;

class DescargoActaController extends Controller
{
    public function __construct(private readonly DescargoActaService $service) {}

    public function generar(Request $request, int $descargoId)
    {
        $data = $request->validate([
            'observaciones' => 'nullable|string|max:2000',
        ]);

        $acta = $this->service->generar(
            $descargoId,
            $data['observaciones'] ?? null,
            $request->user()
        );

        return response()->json([
            'acta' => $acta,
            'link' => rtrim((string) config('app.frontend_url'), '/') . '/descargo-acta/' . $acta->token,
        ]);
    }

    public function show(int $descargoId)
    {
        $acta = $this->service->show($descargoId);

        return response()->json([
            'acta' => $acta,
            'link' => $acta ? rtrim((string) config('app.frontend_url'), '/') . '/descargo-acta/' . $acta->token : null,
        ]);
    }

    public function publica(string $token)
    {
        return response()->json($this->service->publica($token));
    }

    public function firmar(Request $request, string $token)
    {
        $data = $request->validate([
            'firma_nombre' => 'required|string|min:3|max:255',
            'firma_imagen' => ['required', 'string', 'max:500000', 'regex:/^data:image\\/png;base64,/'],
            'acepta' => 'accepted',
        ]);

        return response()->json([
            'message' => 'Descargo firmado correctamente.',
            'acta' => $this->service->firmar($token, $data, $request->ip(), $request->userAgent()),
        ]);
    }
}
