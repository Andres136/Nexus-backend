<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Models\Nomina\Contratacion;
use App\Models\Nomina\Descargo;
use App\Services\Nomina\ConfiguracionNominaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DescargoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = Descargo::with([
            'empleado:id,name',
            'contratacion:id,cargo,empresa_id',
            'contratacion.empresa:id,nombre',
        ]);

        if ($request->user_id)      $q->where('user_id', $request->user_id);
        if ($request->fecha_inicio) $q->whereDate('fecha_hecho', '>=', $request->fecha_inicio);
        if ($request->fecha_fin)    $q->whereDate('fecha_hecho', '<=', $request->fecha_fin);

        return response()->json($q->orderByDesc('fecha_hecho')->paginate($request->per_page ?? 50));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id'      => 'required|integer|exists:users,id',
            'tipo_descargo'=> 'required|string|max:150',
            'fecha_hecho'  => 'required|date',
            'descripcion'  => 'required|string',
        ]);

        $data['generado_por'] = auth()->id();

        $contratacion = Contratacion::where('users_id', $data['user_id'])
            ->latest('id')->first();
        if ($contratacion) $data['contratacion_id'] = $contratacion->id;

        $descargo = Descargo::create($data);

        return response()->json($descargo->load('empleado:id,name'), 201);
    }

    public function show($uuid): JsonResponse
    {
        $descargo = Descargo::with([
            'empleado',
            'contratacion.empresa',
        ])->whereUuid($uuid)->firstOrFail();

        return response()->json($descargo);
    }

    public function destroy($uuid): JsonResponse
    {
        Descargo::whereUuid($uuid)->firstOrFail()->delete();
        return response()->json(['message' => 'Eliminado.']);
    }

    public function pdf($uuid)
    {
        $descargo = Descargo::with([
            'empleado',
            'contratacion.empresa',
        ])->whereUuid($uuid)->firstOrFail();

        $pdf = Pdf::loadView('pdf.descargo', [
            'descargo'    => $descargo,
            'empresa'     => $descargo->contratacion?->empresa,
            'fecha_actual'=> now()->locale('es')->translatedFormat('d \d\e F \d\e Y'),
            'firmaTalentoHumanoPath' => app(ConfiguracionNominaService::class)->firmaTalentoHumanoPath(),
        ])->setPaper('letter', 'portrait');

        return $pdf->download("descargo_{$descargo->uuid}.pdf");
    }
}
