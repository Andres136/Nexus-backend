<?php

namespace App\Http\Controllers\Tic;

use App\Http\Controllers\Controller;
use App\Services\Tic\AsignacionActaService;
use Illuminate\Http\Request;

class AsignacionActaController extends Controller
{
    public function __construct(private readonly AsignacionActaService $service) {}

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
