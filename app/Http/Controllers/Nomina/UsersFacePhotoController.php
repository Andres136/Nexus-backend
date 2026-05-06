<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\StoreUsersFacePhotoRequest;
use App\Http\Requests\Nomina\UpdateUsersFacePhotoRequest;
use App\Services\Nomina\UsersFacePhotoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class UsersFacePhotoController extends Controller
{
    public function __construct(
        private readonly UsersFacePhotoService $usersFacePhotoService
    ) {}

    public function index(): JsonResponse
    {
        try {
            $data = $this->usersFacePhotoService->getAll();
            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $data = $this->usersFacePhotoService->getById($id);
            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

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

    public function update(UpdateUsersFacePhotoRequest $request, int $id): JsonResponse
    {
        try {
            $data = $this->usersFacePhotoService->update($request, $id);
            return response()->json([
                'success' => true,
                'message' => 'Foto facial actualizada correctamente.',
                'data'    => $data,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->usersFacePhotoService->delete($id);
            return response()->json([
                'success' => true,
                'message' => 'Foto facial eliminada correctamente.',
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    private function errorResponse(\Exception $e): JsonResponse
    {
        Log::error('Error en UsersFacePhotoController', ['message' => $e->getMessage()]);
        return response()->json([
            'success' => false,
            'message' => 'Ocurrió un error inesperado.',
        ], 500);
    }
}
