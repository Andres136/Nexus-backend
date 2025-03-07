<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\seguimentoClienteRequest;
use App\Http\Requests\Crm\SeguimientoClienteRequest;
use App\Models\Crm\Cliente;
use App\Models\Crm\SeguimientoCliente;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SeguimientoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
     

         // Obtener la fecha de hace 10 días
    $fechaLimite = Carbon::now()->subDays(30);

    $gestionClientesPorUsuario = User::leftJoin('clientes', 'users.id', '=', 'clientes.user_id')
        ->select('users.id as user_id', 'users.name as nombre', DB::raw('COUNT(clientes.id) as total'))
        ->where('clientes.created_at', '>=', $fechaLimite) // Filtrar clientes registrados en los últimos 10 días
        ->groupBy('users.id', 'users.name')
        ->get();

    return response()->json($gestionClientesPorUsuario);

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SeguimientoClienteRequest $request,$cliente_id)
    {
        $seguimiento = SeguimientoCliente::create([
            'cliente_id' => $cliente_id,
            'user_id' => $request->user_id,
            'tipo_contacto' => $request->tipo_contacto,
            'estado' => $request->estado,
            'comentario' => $request->comentario,
        ]);
        return response()->json([
            'message' => 'Seguimiento creado con exito',
            'seguimiento' => $seguimiento
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Cliente $cliente, SeguimientoCliente $seguimiento)
    {
        if ($seguimiento->cliente_id !== $cliente->id) {
            return response()->json([
                'success' => false,
                'message' => 'El seguimiento no pertenece a este cliente'
            ], 403);
        }

        $seguimiento->load('user', 'cliente');

        return response()->json([
            'success' => true,
            'data' => $seguimiento,
            'user_name' => $seguimiento->user->name,
            'cliente_name' => $seguimiento->cliente->name
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
