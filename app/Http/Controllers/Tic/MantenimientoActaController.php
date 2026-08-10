<?php

namespace App\Http\Controllers\Tic;

use App\Http\Controllers\Controller;
use App\Services\Tic\MantenimientoActaService;
use Illuminate\Http\Request;

class MantenimientoActaController extends Controller
{
    public function __construct(private readonly MantenimientoActaService $service) {}

    public function generar(Request $request, int $mantenimientoId)
    {
        $data = $request->validate([
            'user_id' => 'nullable|integer|exists:users,id',
            'observaciones' => 'nullable|string|max:2000',
        ]);

        $acta = $this->service->generar(
            $mantenimientoId,
            $data['user_id'] ?? null,
            $data['observaciones'] ?? null,
            $request->user()
        );

        return response()->json([
            'acta' => $acta,
            'link' => rtrim((string) config('app.frontend_url'), '/') . '/mantenimiento-acta/' . $acta->token,
        ]);
    }

    public function show(int $mantenimientoId)
    {
        $acta = $this->service->show($mantenimientoId);

        return response()->json([
            'acta' => $acta,
            'link' => $acta ? rtrim((string) config('app.frontend_url'), '/') . '/mantenimiento-acta/' . $acta->token : null,
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
            'message' => 'Acta firmada correctamente.',
            'acta' => $this->service->firmar($token, $data, $request->ip(), $request->userAgent()),
        ]);
    }
}
