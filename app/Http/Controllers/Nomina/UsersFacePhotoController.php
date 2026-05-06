<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nomina\StoreUsersFacePhotoRequest;
use App\Http\Requests\Nomina\UpdateUsersFacePhotoRequest;
use App\Services\Nomina\UsersFacePhotoService;
use Illuminate\Http\JsonResponse;

class UsersFacePhotoController extends Controller
{
    public function __construct(
        private UsersFacePhotoService $usersFacePhotoService
    ) {}

    public function index(): JsonResponse
    {
        $data = $this->usersFacePhotoService->getAll();
        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $data = $this->usersFacePhotoService->getById($id);
        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    public function store(StoreUsersFacePhotoRequest $request): JsonResponse
    {
        $data = $this->usersFacePhotoService->create($request->validated());
        return response()->json([
            'success' => true,
            'message' => 'Foto facial registrada correctamente.',
            'data'    => $data,
        ], 201);
    }

    public function update(UpdateUsersFacePhotoRequest $request, int $id): JsonResponse
    {
        $data = $this->usersFacePhotoService->update($id, $request->validated());
        return response()->json([
            'success' => true,
            'message' => 'Foto facial actualizada correctamente.',
            'data'    => $data,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->usersFacePhotoService->delete($id);
        return response()->json([
            'success' => true,
            'message' => 'Foto facial eliminada correctamente.',
        ]);
    }
}
