<?php

namespace App\Http\Controllers\comunicaciones;

use App\Http\Controllers\Controller;
use App\Http\Requests\comunicaciones\StorePublicacionMarketingRequest;
use App\Services\comunicaciones\PublicacionMarketingService;
use Illuminate\Http\Request;

class PublicacionMarketingController extends Controller
{
    protected $publicacionService;

    public function __construct(PublicacionMarketingService $publicacionService)
    {
        $this->publicacionService = $publicacionService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $publicaciones = $this->publicacionService->all($request->query('search'));
        return response()->json([
            'data' => $publicaciones,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePublicacionMarketingRequest $request)
    {
        $data = $request->validated();
        $publicacion = $this->publicacionService->create($data);
        return response()->json([
            'message' => 'Publicación registrada exitosamente',
            'data' => $publicacion,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $data = $request->all();
        $publicacion = $this->publicacionService->update($id, $data);
        return response()->json([
            'message' => 'Publicación actualizada exitosamente',
            'data' => $publicacion,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $data = $this->publicacionService->delete($id);
        return response()->json([
            'message' => 'Publicación eliminada exitosamente',
            'data' => $data,
        ]);
    }
}
