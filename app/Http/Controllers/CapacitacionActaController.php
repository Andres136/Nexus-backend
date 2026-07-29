<?php

namespace App\Http\Controllers;

use App\Models\Capacitacion;
use App\Services\CapacitacionActaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class CapacitacionActaController extends Controller
{
    public function __construct(private readonly CapacitacionActaService $service) {}

    public function index(Request $request)
    {
        return response()->json(
            $this->service->listar($request->only(['search', 'estado', 'empresa_id', 'page', 'per_page']))
        );
    }

    public function empresas()
    {
        return response()->json($this->service->empresas());
    }

    public function pdf(Request $request, string $capacitacionUuid)
    {
        $validated = $request->validate(['empresa_id' => 'nullable|integer|exists:empresas,id']);
        $data = $this->service->datosPdf(
            $capacitacionUuid,
            isset($validated['empresa_id']) ? (int) $validated['empresa_id'] : null
        );

        $nombre = "acta_{$data['acta']->numero}_" . str($data['empresa']->nombre)->slug('_') . '.pdf';

        return Pdf::loadView('pdf.capacitacion_acta', $data)
            ->setPaper('letter', 'portrait')
            ->download($nombre);
    }

    public function show(Request $request, string $capacitacionUuid)
    {
        return response()->json($this->service->show($capacitacionUuid, $request->user()));
    }

    public function guardar(Request $request, string $capacitacionUuid)
    {
        Capacitacion::where('uuid', $capacitacionUuid)->firstOrFail();
        $data = $request->validate([
            'titulo' => 'required|string|max:255',
            'objetivo' => 'nullable|string|max:3000',
            'desarrollo' => 'required|string|max:30000',
            'compromisos' => 'nullable|array',
            'compromisos.*.descripcion' => 'required|string|max:1000',
            'compromisos.*.responsable' => 'nullable|string|max:255',
            'compromisos.*.fecha' => 'nullable|date',
            'conclusiones' => 'nullable|string|max:5000',
        ]);

        return response()->json([
            'message' => 'Acta guardada correctamente.',
            'acta' => $this->service->guardar($capacitacionUuid, $data, $request->user()),
        ]);
    }

    public function enviar(Request $request, string $capacitacionUuid)
    {
        $data = $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'integer|exists:users,id',
        ]);
        return response()->json($this->service->enviar($capacitacionUuid, $data['user_ids'], $request->user()));
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
            'envio' => $this->service->firmar($token, $data, $request->ip(), $request->userAgent()),
        ]);
    }
}
