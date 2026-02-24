<?php

namespace App\Http\Controllers\Tic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tic\StoreAsignacionEquipoRequest;
use App\Models\Crm\empresa;
use App\Models\Crm\product;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AsignacionesController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    protected $asignacionesService;

    public function __construct(\App\Services\Tic\AsignacionesService $asignacionesService)
    {
        $this->asignacionesService = $asignacionesService;
    }
public function index(Request $request)
{
$filters = $request->only([
    'usuario_id',
    'empresa_id',
    'activo',
    'search',
    'per_page'
]);

    $data = $this->asignacionesService->getAllAsignaciones($filters);

    return response()->json($data);
}
    /**
     * Store a newly created resource in storage.
     */
  public function store(StoreAsignacionEquipoRequest $request)
{
    $asignacion = $this->asignacionesService->asignarProducto($request->all());
    $usuarioRecibe = User::find($request->usuario_asignacion_id);

    $empresa = empresa::find($request->empresa_id);
    $producto = product::find($request->producto_id);
    $usuario = auth()->user();

   $logoPath = null;

if ($empresa->logo) {
    $possiblePath = public_path('storage/' . $empresa->logo);

    if (file_exists($possiblePath) && !is_dir($possiblePath)) {
        $logoPath = $possiblePath;
    }
}
    $pdf = Pdf::loadView('pdf.asignacion_equipo', [
        'asignacion' => $asignacion,
        'empresa' => $empresa,
        'producto' => $producto,
        'usuario' => $usuario,
        'usuarioRecibe' => $usuarioRecibe,
        'logoPath' => $logoPath

    ]);

    $fileName = 'acta_asignacion_'.$asignacion->id.'.pdf';

 // Crear carpeta si no existe
Storage::disk('public')->makeDirectory('asignaciones');

$fileName = 'acta_asignacion_'.$asignacion->id.'.pdf';

// Guardar PDF
Storage::disk('public')->put(
    'asignaciones/'.$fileName,
    $pdf->output()
);

    return response()->json([
        'message' => 'Producto asignado exitosamente',
        'pdf_url' => asset('storage/asignaciones/'.$fileName),
        'data' => $asignacion
    ]);
}

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id, Request $request)
    {
        $observaciones = $request->input('observaciones');
        $asignacion = $this->asignacionesService->desactivarAsignacion($id, $observaciones);

    $empresa = $asignacion->empresa;
    $producto = $asignacion->producto;
    $usuario = auth()->user();
    $usuarioRecibe = $asignacion->usuarioRecibe;

    $logoPath = null;

    if ($empresa->logo) {
        $possiblePath = public_path('storage/' . $empresa->logo);

        if (file_exists($possiblePath) && !is_dir($possiblePath)) {
            $logoPath = $possiblePath;
        }
    }

    $pdf = Pdf::loadView('pdf.acta_devolucion_equipo', [
        'asignacion' => $asignacion,
        'empresa' => $empresa,
        'producto' => $producto,
        'usuario' => $usuario,
        'usuarioRecibe' => $usuarioRecibe,
        'logoPath' => $logoPath
    ]);

    $fileName = 'acta_devolucion_'.$asignacion->id.'.pdf';

    $pdf->save(storage_path('app/public/asignaciones/'.$fileName));

    return response()->json([
        'message' => 'Asignación desactivada correctamente',
        'pdf_url' => asset('storage/asignaciones/'.$fileName),
        'data' => $asignacion
    ]);
}               
    }

