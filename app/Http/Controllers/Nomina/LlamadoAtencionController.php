<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Models\Nomina\Contratacion;
use App\Models\Nomina\LlamadoAtencion;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LlamadoAtencionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = LlamadoAtencion::with([
            'empleado:id,name',
            'contratacion:id,cargo,empresa_id',
            'contratacion.empresa:id,nombre',
        ]);

        if ($request->user_id)      $q->where('user_id', $request->user_id);
        if ($request->tipo)         $q->where('tipo', $request->tipo);
        if ($request->fecha_inicio) $q->whereDate('fecha_hecho', '>=', $request->fecha_inicio);
        if ($request->fecha_fin)    $q->whereDate('fecha_hecho', '<=', $request->fecha_fin);

        return response()->json($q->orderByDesc('fecha_hecho')->paginate($request->per_page ?? 50));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id'    => 'required|integer|exists:users,id',
            'tipo'       => 'required|string|max:50',
            'titulo'     => 'nullable|string|max:150',
            'detalle'    => 'nullable|string',
            'minutos'    => 'nullable|integer|min:0',
            'fecha_hecho'=> 'required|date',
            'severidad'  => 'nullable|in:leve,moderado,grave',
        ]);

        $data['generado_por'] = auth()->id();
        $data['minutos']      = $data['minutos'] ?? 0;
        $data['severidad']    = $data['severidad'] ?? 'leve';

        $contratacion = Contratacion::where('users_id', $data['user_id'])
            ->latest('id')->first();
        if ($contratacion) $data['contratacion_id'] = $contratacion->id;

        $llamado = LlamadoAtencion::create($data);

        return response()->json($llamado->load('empleado:id,name'), 201);
    }

    public function show($uuid): JsonResponse
    {
        $llamado = LlamadoAtencion::with([
            'empleado:id,name',
            'contratacion.empresa',
        ])->whereUuid($uuid)->firstOrFail();

        return response()->json($llamado);
    }

    public function destroy($uuid): JsonResponse
    {
        LlamadoAtencion::whereUuid($uuid)->firstOrFail()->delete();
        return response()->json(['message' => 'Eliminado.']);
    }

    public function pdf($uuid, Request $request)
    {
        $llamado = LlamadoAtencion::with([
            'empleado',
            'contratacion.empresa',
        ])->whereUuid($uuid)->firstOrFail();

        $descripcion = $request->input('descripcion', $llamado->detalle ?? '');

        $pdf = Pdf::loadView('pdf.llamado_atencion', [
            'llamado'     => $llamado,
            'descripcion' => $descripcion,
            'empresa'     => $llamado->contratacion?->empresa,
            'fecha_actual'=> now()->locale('es')->translatedFormat('d \d\e F \d\e Y'),
        ])->setPaper('letter', 'portrait');

        return $pdf->download("llamado_{$llamado->uuid}.pdf");
    }
}
