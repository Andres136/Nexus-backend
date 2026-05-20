<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\StoreUsersFacePhotoRequest;
use App\Http\Requests\Nomina\UpdateUsersFacePhotoRequest;
use App\Services\Nomina\UsersFacePhotoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UsersFacePhotoController extends Controller
{
    public function __construct(
        private readonly UsersFacePhotoService $usersFacePhotoService
    ) {}

    // GET /users-face-photos
    public function index(): JsonResponse
    {
        try {
            $data = $this->usersFacePhotoService->getAll();

            return response()->json([
                'success' => true,
                'data'    => $data,
            ], 200);

        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    // GET /users-face-photos/{uuid}
    public function show(string $uuid): JsonResponse               
    {
        try {
            $data = $this->usersFacePhotoService->getByUuid($uuid);  

            return response()->json([
                'success' => true,
                'data'    => $data,
            ], 200);

        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    // POST /users-face-photos
    public function store(StoreUsersFacePhotoRequest $request): JsonResponse
    {
        try {
            $data = $this->usersFacePhotoService->store($request);

            return response()->json([
                'success' => true,
                'message' => 'Foto facial registrada correctamente.',
                'data'    => $data,
            ], 201);

        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    // PUT /users-face-photos/{uuid}
    public function update(UpdateUsersFacePhotoRequest $request, string $uuid): JsonResponse  
    {
        try {
            $data = $this->usersFacePhotoService->update($request, $uuid); 

            return response()->json([
                'success' => true,
                'message' => 'Foto facial actualizada correctamente.',
                'data'    => $data,
            ], 200);

        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    // DELETE /users-face-photos/{uuid}
    public function destroy(string $uuid): JsonResponse            
    {
        try {
            $this->usersFacePhotoService->delete($uuid);           

            return response()->json([
                'success' => true,
                'message' => 'Foto facial eliminada correctamente.',
            ], 200);

        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    // GET /users-face-photos/{uuid}/image — sirve la imagen con CORS vía API
    public function image(string $uuid): Response
    {
        try {
            $facePhoto = \App\Models\Nomina\UsersFacePhoto::where('uuid', $uuid)->firstOrFail();

            if (!$facePhoto->photo || !Storage::disk('public')->exists($facePhoto->photo)) {
                abort(404, 'Imagen no encontrada.');
            }

            $path     = Storage::disk('public')->path($facePhoto->photo);
            $mime     = mime_content_type($path) ?: 'image/jpeg';
            $contents = Storage::disk('public')->get($facePhoto->photo);

            return response($contents, 200, [
                'Content-Type'  => $mime,
                'Cache-Control' => 'public, max-age=3600',
            ]);
        } catch (\Exception $e) {
            abort(404, 'Imagen no encontrada.');
        }
    }

    private function errorResponse(\Exception $e): JsonResponse
    {
        Log::error('Error en UsersFacePhotoController', [
            'message' => $e->getMessage(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Ocurrió un error inesperado.',
        ], 500);
    }
}