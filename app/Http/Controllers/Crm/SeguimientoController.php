<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\seguimentoClienteRequest;
use App\Http\Requests\Crm\SeguimientoClienteRequest;
use App\Models\Crm\Cliente;
use App\Models\Crm\SeguimientoCliente;
use App\Models\User;
use App\RolEnum;
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
         $fechaLimite = Carbon::now()->subDays(30); // Últimos 30 días
     
         $gestiones = DB::table('seguimiento_clientes')
             ->join('users', 'seguimiento_clientes.user_id', '=', 'users.id')
             ->select('users.name as nombre', DB::raw('COUNT(seguimiento_clientes.id) as total'))
             ->where('seguimiento_clientes.created_at', '>=', $fechaLimite)
             ->groupBy('users.name')
             ->get();
     
         return response()->json($gestiones);
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
 public function resumenMensualPorUsuario(Request $request)
{
    $user = auth()->user();

    $inicio = Carbon::now()->subMonths(6)->startOfMonth()->toDateTimeString();

    $rolesRestringidos = [RolEnum::COMERCIAL->value, RolEnum::EJECUTIVO_COMERCIAL->value];
    $userId = in_array($user->role_id, $rolesRestringidos)
        ? $user->id
        : $request->query('user_id');

    // ✅ Consumir el service
    $data = (new \App\Services\Crm\ComercialDashboardService())
        ->getMesAMes($userId, $inicio);

    // Filtros opcionales (igual que antes)
    $userFiltro = $request->query('user_id');
    $mesFiltro  = $request->query('mes');

    if ($mesFiltro !== null) {
        $data = array_filter($data, fn($r) => $r['mes'] == $mesFiltro);
    }

    return response()->json(
        collect($data)->sortBy(['mes', 'usuario'])->values()
    );
}


    public function dashboardComercialMesAMes(Request $request)
{
    $user = auth()->user();

    $inicio = Carbon::now()->subMonths(6)->startOfMonth();

    $rolesRestringidos = [RolEnum::COMERCIAL->value, RolEnum::EJECUTIVO_COMERCIAL->value];
    $userId = in_array($user->role_id, $rolesRestringidos)
        ? $user->id
        : $request->query('user_id');

    // 1️⃣ Gestiones
    $gestiones = DB::table('seguimiento_clientes')
        ->join('users', 'seguimiento_clientes.user_id', '=', 'users.id')
        ->select(
            'users.id as user_id',
            'users.name as usuario',
            DB::raw('DATE_FORMAT(seguimiento_clientes.created_at, "%Y-%m") as mes'),
            DB::raw('COUNT(*) as gestiones')
        )
        ->where('seguimiento_clientes.created_at', '>=', $inicio)
        ->when($userId, fn ($q) => $q->where('users.id', $userId))
        ->groupBy('users.id', 'users.name', 'mes')
        ->get();

    // 2️⃣ Cotizaciones
    $cotizaciones = DB::table('cotizaciones')
        ->join('users', 'cotizaciones.user_id', '=', 'users.id')
        ->select(
            'users.id as user_id',
            DB::raw('DATE_FORMAT(cotizaciones.created_at, "%Y-%m") as mes'),
            DB::raw('COUNT(*) as cotizaciones')
        )
        ->where('cotizaciones.created_at', '>=', $inicio)
        ->when($userId, fn ($q) => $q->where('users.id', $userId))
        ->groupBy('users.id', 'mes')
        ->get();

    // 3️⃣ Órdenes
    $ordenes = DB::table('orden__compras')
        ->join('users', 'orden__compras.user_id', '=', 'users.id')
        ->select(
            'users.id as user_id',
            DB::raw('DATE_FORMAT(orden__compras.created_at, "%Y-%m") as mes'),
            DB::raw('COUNT(*) as ordenes'),
            DB::raw('SUM(orden__compras.valor_total) as valor')
        )
        ->where('orden__compras.created_at', '>=', $inicio)
        ->when($userId, fn ($q) => $q->where('users.id', $userId))
        ->groupBy('users.id', 'mes')
        ->get();

    // 4️⃣ Consolidar
    $resultado = [];

    foreach ([$gestiones, $cotizaciones, $ordenes] as $coleccion) {
        foreach ($coleccion as $r) {
            $key = $r->user_id . '_' . $r->mes;

            if (!isset($resultado[$key])) {
                $resultado[$key] = [
                    'user_id' => $r->user_id,
                    'usuario' => $r->usuario ?? $user->name,
                    'mes' => $r->mes,
                    'gestiones' => 0,
                    'cotizaciones' => 0,
                    'ordenes' => 0,
                    'valor_ventas' => 0,
                ];
            }

            if (isset($r->gestiones)) {
                $resultado[$key]['gestiones'] = (int) $r->gestiones;
            }
            if (isset($r->cotizaciones)) {
                $resultado[$key]['cotizaciones'] = (int) $r->cotizaciones;
            }
            if (isset($r->ordenes)) {
                $resultado[$key]['ordenes'] = (int) $r->ordenes;
                $resultado[$key]['valor_ventas'] = (float) $r->valor;
            }
        }
    }

    return response()->json(
        collect($resultado)->sortBy('mes')->values()
    );
}



}
