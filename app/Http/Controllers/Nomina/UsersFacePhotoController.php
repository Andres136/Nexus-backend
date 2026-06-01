<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\StoreUsersFacePhotoRequest;
use App\Http\Requests\Nomina\UpdateUsersFacePhotoRequest;
use App\Services\Nomina\KioskoDeviceService;
use App\Services\Nomina\UsersFacePhotoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UsersFacePhotoController extends Controller
{
    public function __construct(
        private readonly UsersFacePhotoService $usersFacePhotoService,
        private readonly KioskoDeviceService $kioskoDeviceService
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

    public function empleadosConContrato(Request $request): JsonResponse
    {
        try {
            $data = $this->usersFacePhotoService->getEmpleadosConContrato([
                'search' => $request->query('search'),
                'foto' => $request->query('foto', 'todos'),
                'per_page' => $request->query('per_page', 10),
            ]);

            return response()->json([
                'success' => true,
                'data' => $data['items'],
                'stats' => $data['stats'],
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
            return $this->serveImage($uuid);
        } catch (\Exception $e) {
            abort(404, 'Imagen no encontrada.');
        }
    }

    public function kioskImage(Request $request, string $uuid): Response
    {
        try {
            $this->kioskoDeviceService->validateDeviceSession([
                'uuid' => (string) $request->header('X-Kiosko-Device'),
                'session_token' => (string) $request->header('X-Kiosko-Session'),
                'fingerprint' => (string) $request->header('X-Kiosko-Fingerprint'),
            ], $request->ip());

            return $this->serveImage($uuid);
        } catch (\Exception $e) {
            abort(404, 'Imagen no encontrada.');
        }
    }

    private function serveImage(string $uuid): Response
    {
        $facePhoto = \App\Models\Nomina\UsersFacePhoto::where('uuid', $uuid)->firstOrFail();

        if (!$facePhoto->photo || !Storage::disk('public')->exists($facePhoto->photo)) {
            abort(404, 'Imagen no encontrada.');
        }

        $path = Storage::disk('public')->path($facePhoto->photo);
        $mime = mime_content_type($path) ?: 'image/jpeg';
        $contents = Storage::disk('public')->get($facePhoto->photo);

        return response($contents, 200, [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=3600',
        ]);
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
