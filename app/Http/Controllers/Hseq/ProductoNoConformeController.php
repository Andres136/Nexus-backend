<?php

namespace App\Http\Controllers\Hseq;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hseq\StoreProductoNoConformeRequest;
use App\Models\User;
use App\RolEnum;
use App\Services\Hseq\ProductoNoConformeService;
use Illuminate\Http\Request;

class ProductoNoConformeController extends Controller
{
    protected ProductoNoConformeService $service;

    private const ROLES_GESTION = [RolEnum::ADMINISTRADOR, RolEnum::HSEQ];

    public function __construct(ProductoNoConformeService $service)
    {
        $this->service = $service;
    }

    private function puedeGestionarTodas(User $user): bool
    {
        return in_array(RolEnum::tryFrom((int) $user->role_id), self::ROLES_GESTION, true);
    }

    public function index(Request $request)
    {
        $usuario = $request->user();
        $filtros = $request->query();

        if (!$this->puedeGestionarTodas($usuario)) {
            $filtros['comercial_id'] = $usuario->id;
        }

        $data = $this->service->listar($filtros);

        return response()->json([
            'message' => 'Productos no conformes listados exitosamente',
            'data' => $data,
        ]);
    }

    public function store(StoreProductoNoConformeRequest $request)
    {
        $usuario = $request->user();

        $data = array_merge($request->validated(), [
            'comercial_id' => $usuario->id,
            'proceso_id' => $usuario->procesos()->oldest('id')->value('id'),
        ]);

        $producto = $this->service->crear($data);

        return response()->json([
            'message' => 'Producto no conforme creado exitosamente',
            'data' => $producto,
        ], 201);
    }

    public function show(int $id)
    {
        $producto = $this->service->show($id);

        return response()->json([
            'message' => 'Producto no conforme encontrado',
            'data' => $producto,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $producto = $this->service->actualizar($id, $request->all());

        return response()->json([
            'message' => 'Producto no conforme actualizado exitosamente',
            'data' => $producto,
        ]);
    }

    public function estadisticas(Request $request)
    {
        $data = $this->service->estadisticas($request->query());

        return response()->json([
            'message' => 'Estadísticas de productos no conformes',
            'data' => $data,
        ]);
    }

    public function cambiarEstado(Request $request, int $id)
    {
        $request->validate([
            'estado_id' => 'required|exists:estados,id',
        ]);

        $producto = $this->service->cambiarEstado($id, $request->estado_id);

        return response()->json([
            'message' => 'Estado actualizado exitosamente',
            'data' => $producto,
        ]);
    }
}
