<?php

namespace App\Http\Controllers\Crm;

use App\Exports\GenericExport;
use App\Exports\GestionCarteraExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\StoreGestionCarteraRequest;
use App\Http\Requests\Crm\UpdateGestionCarteraRequest;
use App\Models\Crm\empresa;
use App\Models\Crm\GestionCartera;
use App\RolEnum;
use App\Services\Crm\GestionCarteraService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class GestionCarteraController extends Controller
{
    /**
     * Display a listing of the resource.
     */

   protected $gestionCarteraService;

   public function __construct(GestionCarteraService $gestionCarteraService)
   {
         $this->gestionCarteraService = $gestionCarteraService;
   }

    public function index(Request $request)
    {
        $filtros = $request->only(['buscar', 'buscar_por', 'fecha_inicio', 'fecha_fin', 'cliente_id', 'user_comercial_id', 'estado', 'per_page']);
        $data = $this->gestionCarteraService->listarGestionCartera($filtros);
        return response()->json([
            'message' => 'Gestión de cartera obtenida exitosamente',
            'data' => $data['paginator'],
            'total' => $data['total_cartera'],
            'total_vencido' => $data['total_vencido']
            
            
        ]);
    }

    /**
     * GET /gestion-cartera/exportar
     * Exporta a Excel la cartera que cumpla los filtros aplicados (mismo alcance que index()).
     */
    public function exportar(Request $request)
    {
        $filtros = $request->only(['buscar', 'buscar_por', 'fecha_inicio', 'fecha_fin', 'cliente_id', 'user_comercial_id', 'estado']);
        $registros = $this->gestionCarteraService->exportarGestionCartera($filtros);

        if ($registros->isEmpty()) {
            return response()->json([
                'message' => 'No hay registros de cartera para exportar con los filtros seleccionados.',
            ], 422);
        }

        $filas = $registros->map(fn (GestionCartera $g) => [
            'Cliente' => $g->cliente?->nombre,
            'Empresa' => $g->empresa?->nombre,
            'Comercial' => $g->comercial?->name,
            'N° Factura' => $g->numero_factura,
            'Fecha factura' => optional($g->fecha_factura)->format('Y-m-d'),
            'Días crédito' => $g->dias_credito,
            'Fecha vencimiento' => optional($g->fecha_vencimiento)->format('Y-m-d'),
            'Base' => $g->base,
            'IVA' => $g->iva,
            'RteFte' => $g->rete_renta,
            'RteICA' => $g->rete_ica,
            'Valor total' => $g->valor_total,
            'Saldo pendiente' => $g->saldo_pendiente,
            'Estado' => $g->estado,
            'Observaciones' => $g->observaciones,
        ]);

        $headings = ['Cliente', 'Empresa', 'Comercial', 'N° Factura', 'Fecha factura', 'Días crédito', 'Fecha vencimiento', 'Base', 'IVA', 'RteFte', 'RteICA', 'Valor total', 'Saldo pendiente', 'Estado', 'Observaciones'];

        // Solo se muestra logo/nombre de empresa si, con los filtros aplicados,
        // todos los registros pertenecen a la misma empresa (si hay varias mezcladas
        // no hay una sola marca que mostrar en el encabezado).
        $empresaIds = $registros->pluck('empresa_id')->filter()->unique();
        $empresaUnica = $empresaIds->count() === 1 ? empresa::find($empresaIds->first()) : null;

        $logoPath = null;
        if ($empresaUnica && $empresaUnica->logo) {
            $posiblePath = public_path('storage/' . $empresaUnica->logo);
            if (file_exists($posiblePath) && !is_dir($posiblePath)) {
                $logoPath = $posiblePath;
            }
        }

        $nombreArchivo = 'cartera';
        if ($empresaUnica) {
            $nombreArchivo .= '_' . Str::slug($empresaUnica->nombre, '_');
        }
        $nombreArchivo .= '_' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(
            new GestionCarteraExport($filas, $headings, $empresaUnica?->nombre, $logoPath),
            $nombreArchivo
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreGestionCarteraRequest $request)
    {
        $gestionCartera = $this->gestionCarteraService->crearGestionCartera($request->validated());
        return response()->json([
            'message' => 'Factura registrada exitosamente',
            'data' => $gestionCartera
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $gestionCartera = $this->gestionCarteraService->find($id);
        return response()->json([
            'message' => 'Factura obtenida exitosamente',
            'data' => $gestionCartera
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateGestionCarteraRequest $request, string $id)
    {
        $updatedGestionCartera = $this->gestionCarteraService->update($id, $request->validated());
        return response()->json([
            'message' => 'Factura actualizada exitosamente',
            'data' => $updatedGestionCartera
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
 public function destroy($id)
{
    $user = auth()->user();

    // validar si es responsable
    $departamento = $user->departamento;

    $esResponsable = $departamento && $departamento->responsable_id == $user->id;

    if (!$esResponsable) {
        return response()->json([
            'error' => 'No tienes permiso para cancelar deudas'
        ], 403);
    }

    $cartera = $this->gestionCarteraService->cancelarDeuda($id);

    return response()->json([
        'message' => 'Deuda cancelada correctamente',
        'data' => $cartera
    ]);
}

public function estadisticasCartera(Request $request)
{
    $year = $request->query('year', now()->year);
    $lineaTiempo = $this->gestionCarteraService->lineaTiempoAnual($year);

    return response()->json([
        'message' => 'Línea de tiempo anual obtenida exitosamente',
        'data' => $lineaTiempo
    ]);
}
 public function recaudoSemanal(Request $request)
 {
    $year = $request->query('year', now()->year);
    $recaudoSemanal = $this->gestionCarteraService->recaudoSemanal($year);

    return response()->json([
        'message' => 'Recaudo semanal obtenido exitosamente',
        'data' => $recaudoSemanal
    ]);
 }

 // Nueva fUNCION ELIMINAR FACTURA DE CARTERA (ANULAR)
 public function anularFactura($id)
 {
    $user = auth()->user();

    // Mismo alcance que el botón "Eliminar" en el frontend: solo
    // Administrador/HSEQ/Administrativo pueden borrar una factura.
    $rolesPermitidos = [RolEnum::ADMINISTRADOR->value, RolEnum::HSEQ->value, RolEnum::ADMINISTRATIVO->value];

    if (!in_array($user->role_id, $rolesPermitidos)) {
        return response()->json([
            'message' => 'No tienes permiso para anular facturas'
        ], 403);
    }

    $cartera = $this->gestionCarteraService->anularFactura($id);

    return response()->json([
        'message' => 'Factura anulada correctamente',
        'data' => $cartera
    ]);
 }

}