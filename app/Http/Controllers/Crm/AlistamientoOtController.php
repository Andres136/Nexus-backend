<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\StoreAlistamientoOtRequest;
use App\Services\Crm\AlistamientoOtService;
use Illuminate\Http\Request;

class AlistamientoOtController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    protected $alistamientoOtService;

    public function __construct(AlistamientoOtService $alistamientoOtService)
    {
        $this->alistamientoOtService = $alistamientoOtService;
    }


    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
  public function store(StoreAlistamientoOtRequest $request)
{
    try {

        $alistamientos = $this->alistamientoOtService
            ->crearAlistamientosMasivo($request->validated()['items']);

        return response()->json([
            'success' => true,
            'message' => 'Alistamientos de OT creados exitosamente',
            'data' => $alistamientos
        ], 201);

    } catch (\Exception $e) {

        // 🔥 intentar decodificar errores por item
        $errores = json_decode($e->getMessage(), true);

        return response()->json([
            'success' => false,
            'errores' => $errores ?? [$e->getMessage()]
        ], 400);
    }
}
    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $data = $this->alistamientoOtService->getByOrdenTrabajo($id);
        return response()->json([
            'message' => 'Alistamientos de OT obtenidos exitosamente',
            'data' => $data
        ]);
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
    public function destroy(string $id)
    {
        //
    }
}
