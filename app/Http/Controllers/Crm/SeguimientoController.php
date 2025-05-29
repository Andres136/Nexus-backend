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
    public function resumenMensualPorUsuario()
    {
        $inicio = Carbon::now()->subMonths(6)->startOfMonth();
    
        // 1) Gestiones
        $gestiones = DB::table('seguimiento_clientes')
            ->join('users', 'seguimiento_clientes.user_id', '=', 'users.id')
            ->select(
                'users.id as user_id',
                'users.name as usuario',
                DB::raw('DATE_FORMAT(seguimiento_clientes.created_at, "%Y-%m") as mes'),
                DB::raw('COUNT(seguimiento_clientes.id) as total_gestiones')
            )
            ->where('seguimiento_clientes.created_at', '>=', $inicio)
            ->groupBy('mes', 'users.id', 'users.name');
    
        // 2) Cotizaciones
        $cotizaciones = DB::table('cotizaciones')
            ->join('users', 'cotizaciones.user_id', '=', 'users.id')
            ->select(
                'users.id as user_id',
                'users.name as usuario',
                DB::raw('DATE_FORMAT(cotizaciones.created_at, "%Y-%m") as mes'),
                DB::raw('COUNT(cotizaciones.id) as total_cotizaciones')
            )
            ->where('cotizaciones.created_at', '>=', $inicio)
            ->groupBy('mes', 'users.id', 'users.name');
    
        // 3) Órdenes de compra  ← aquí el nombre correcto de la tabla
        $ordenes = DB::table('orden__compras')
            ->join('users', 'orden__compras.user_id', '=', 'users.id')
            ->select(
                'users.id as user_id',
                'users.name as usuario',
                DB::raw('DATE_FORMAT(orden__compras.created_at, "%Y-%m") as mes'),
                DB::raw('COUNT(orden__compras.id) as total_ordenes')
            )
            ->where('orden__compras.created_at', '>=', $inicio)
            ->groupBy('mes', 'users.id', 'users.name');
    
        // 4) Clientes nuevos
        $clientes = DB::table('clientes')
            ->join('users', 'clientes.user_id', '=', 'users.id')
            ->select(
                'users.id as user_id',
                'users.name as usuario',
                DB::raw('DATE_FORMAT(clientes.created_at, "%Y-%m") as mes'),
                DB::raw('COUNT(clientes.id) as total_clientes')
            )
            ->where('clientes.created_at', '>=', $inicio)
            ->groupBy('mes', 'users.id', 'users.name');
    
        // 5) Fusionar en un array
        $resultado = [];
    
        foreach ([$gestiones, $cotizaciones, $ordenes, $clientes] as $query) {
            foreach ($query->get() as $r) {
                $key = $r->user_id.'_'.$r->mes;
                if (! isset($resultado[$key])) {
                    $resultado[$key] = [
                        'usuario'            => $r->usuario,
                        'mes'                => $r->mes,
                        'total_gestiones'    => 0,
                        'total_cotizaciones' => 0,
                        'total_ordenes'      => 0,
                        'total_clientes'     => 0,
                    ];
                }
                if (isset($r->total_gestiones)) {
                    $resultado[$key]['total_gestiones'] = $r->total_gestiones;
                }
                if (isset($r->total_cotizaciones)) {
                    $resultado[$key]['total_cotizaciones'] = $r->total_cotizaciones;
                }
                if (isset($r->total_ordenes)) {
                    $resultado[$key]['total_ordenes'] = $r->total_ordenes;
                }
                if (isset($r->total_clientes)) {
                    $resultado[$key]['total_clientes'] = $r->total_clientes;
                }
            }
        }
    
        // 6) Devolver ordenado
        return response()->json(
            collect($resultado)
                ->sortBy(['mes','usuario'])
                ->values()
        );
    }
    

}
