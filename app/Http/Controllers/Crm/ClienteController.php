<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\ClientesRequest;
use App\Http\Requests\Crm\ImportarClientesExelRequest;
use App\Services\Crm\GestionCarteraService;
use App\Services\crm\ClienteService;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    protected $clienteService;

    public function __construct(ClienteService $clienteService)
    {
        $this->clienteService = $clienteService;
    }

    public function clientesTodos(Request $request)
    {
        return response()->json(
            $this->clienteService->clientesTodos($request)
        );
    }

    public function index(Request $request)
    {
        return response()->json(
            $this->clienteService->index($request)
        );
    }

    public function store(ClientesRequest $request)
    {
        $cliente = $this->clienteService->store($request->validated());

        return response()->json([
            'message' => 'Cliente creado con exito',
            'cliente' => $cliente,
        ], 201);
    }

    public function show(string $id)
    {
        return response()->json(
            $this->clienteService->show($id)
        );
    }

    public function edit(string $id)
    {
        //
    }

    public function carteraResumen(string $id)
    {
        return response()->json([
            'cartera' => app(GestionCarteraService::class)->resumenCarteraCliente((int) $id),
        ]);
    }

    public function update(Request $request, string $id)
    {
        $cliente = $this->clienteService->update($request, $id);

        return response()->json([
            'message' => 'Cliente actualizado correctamente',
            'cliente' => $cliente,
        ]);
    }

    public function destroy(string $id)
    {
        $this->clienteService->destroy($id);

        return response()->json([
            'message' => 'Cliente eliminado correctamente',
        ]);
    }

    public function cambiarEstado(string $id)
    {
        $result = $this->clienteService->cambiarEstado($id);

        if (!$result['autorizado']) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        if (!$result['puede_cambiar_estado']) {
            $faltantes = $result['gestiones_requeridas'] - $result['total_gestiones'];

            return response()->json([
                'message' => "Debes registrar al menos 3 gestiones propias antes de desactivar este cliente. Te faltan {$faltantes}.",
                'total_gestiones' => $result['total_gestiones'],
                'gestiones_requeridas' => $result['gestiones_requeridas'],
            ], 422);
        }

        return response()->json([
            'message'   => 'Estado del cliente actualizado correctamente',
            'estado_id' => $result['estado_id'],
            'cliente'   => $result['cliente'],
        ]);
    }

    public function clientesUsuario(Request $request)
    {
        return response()->json(
            $this->clienteService->clientesUsuario($request)
        );
    }

    public function usuariosComerciales()
    {
        return response()->json(
            $this->clienteService->usuariosComerciales()
        );
    }

    public function importExcel(ImportarClientesExelRequest $request)
    {
        $insertados = $this->clienteService->importExcel(
            $request->validated()['clientes']
        );

        return response()->json([
            'message' => "Se importaron/actualizaron $insertados clientes",
        ]);
    }
}
