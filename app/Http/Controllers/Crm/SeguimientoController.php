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
    public function resumenMensualPorUsuario(Request $request)
{
    $inicio = Carbon::now()->subMonths(6)->startOfMonth();
 $userFiltro = $request->query('user_id');
    $mesFiltro  = $request->query('mes');
    // 1. Obtener gestiones
    $gestiones = DB::table('seguimiento_clientes')
        ->join('users', 'seguimiento_clientes.user_id', '=', 'users.id')
        ->select(
            'users.id as user_id',
            'users.name as usuario',
            DB::raw('DATE_FORMAT(seguimiento_clientes.created_at, "%Y-%m") as mes'),
            DB::raw('COUNT(seguimiento_clientes.id) as total_gestiones')
        )
        ->where('seguimiento_clientes.created_at', '>=', $inicio)
        ->groupBy('users.id', 'users.name', 'mes')
        ->get();

    // 2. Cotizaciones
    $cotizaciones = DB::table('cotizaciones')
        ->join('users', 'cotizaciones.user_id', '=', 'users.id')
        ->select(
            'users.id as user_id',
            'users.name as usuario',
            DB::raw('DATE_FORMAT(cotizaciones.created_at, "%Y-%m") as mes'),
            DB::raw('COUNT(cotizaciones.id) as total_cotizaciones')
        )
        ->where('cotizaciones.created_at', '>=', $inicio)
        ->groupBy('users.id', 'users.name', 'mes')
        ->get();

    // 3. Ordenes de compra
    $ordenes = DB::table('orden__compras')
        ->join('users', 'orden__compras.user_id', '=', 'users.id')
        ->select(
            'users.id as user_id',
            'users.name as usuario',
            DB::raw('DATE_FORMAT(orden__compras.created_at, "%Y-%m") as mes'),
            DB::raw('COUNT(orden__compras.id) as total_ordenes'),
            DB::raw('SUM(orden__compras.valor_total) as total_valor_ordenes')
        )
        ->where('orden__compras.created_at', '>=', $inicio)
        ->groupBy('users.id', 'users.name', 'mes')
        ->get();

    // 4. Nuevos clientes
    $clientes = DB::table('clientes')
        ->join('users', 'clientes.user_id', '=', 'users.id')
        ->select(
            'users.id as user_id',
            'users.name as usuario',
            DB::raw('DATE_FORMAT(clientes.created_at, "%Y-%m") as mes'),
            DB::raw('COUNT(clientes.id) as total_clientes')
        )
        ->where('clientes.created_at', '>=', $inicio)
        ->groupBy('users.id', 'users.name', 'mes')
        ->get();

    // 5. Metas por mes
$metas = DB::table('meta_mensuals')
    ->select(
        DB::raw("CONCAT(anio, '-', LPAD(mes,2,'0')) as periodo"),
        'valor_meta'
    )
    ->get()
    ->keyBy('periodo');


    // 6. Consolidar resultados
    $resultado = [];

    foreach ([$gestiones, $cotizaciones, $ordenes, $clientes] as $collection) {
        foreach ($collection as $registro) {
            $key = $registro->user_id . '_' . $registro->mes;

            if (!isset($resultado[$key])) {
                $resultado[$key] = [
                      'user_id'            => $registro->user_id,
                    'usuario'             => $registro->usuario,
                    'mes'                 => $registro->mes,
                    'total_gestiones'     => 0,
                    'total_cotizaciones'  => 0,
                    'total_ordenes'       => 0,
                    'total_valor_ordenes' => 0,
                    'total_clientes'      => 0,
                    'meta'                => 0,
                    'meta_individual'     => 0,
                    'cumplimiento'        => 0
                ];
            }

            // Asignación segura
            if (isset($registro->total_gestiones)) {
                $resultado[$key]['total_gestiones'] = (int) $registro->total_gestiones;
            }
            if (isset($registro->total_cotizaciones)) {
                $resultado[$key]['total_cotizaciones'] = (int) $registro->total_cotizaciones;
            }
            if (isset($registro->total_ordenes)) {
                $resultado[$key]['total_ordenes'] = (int) $registro->total_ordenes;
            }
            if (isset($registro->total_valor_ordenes)) {
                $resultado[$key]['total_valor_ordenes'] = (float) $registro->total_valor_ordenes;
            }
            if (isset($registro->total_clientes)) {
                $resultado[$key]['total_clientes'] = (int) $registro->total_clientes;
            }
        }
    }

    // 7. Agrupar por mes para contar usuarios únicos
    $usuariosPorMes = [];
    foreach ($resultado as $key => $registro) {
        $usuariosPorMes[$registro['mes']][] = $registro['usuario'];
    }

    // 8. Cálculo de metas individuales y cumplimiento
    foreach ($resultado as $key => &$registro) {
        $mes = $registro['mes'];
        $metaMes = $metas[$mes]->valor_meta ?? 0;
        $usuariosActivos = count(array_unique($usuariosPorMes[$mes] ?? []));
        $metaIndividual = $usuariosActivos > 0 ? round($metaMes / $usuariosActivos, 2) : 0;
        $cumplimiento = $metaIndividual > 0 ? round(($registro['total_valor_ordenes'] / $metaIndividual) * 100, 2) : 0;

        $registro['meta'] = (float) $metaMes;
        $registro['meta_individual'] = $metaIndividual;
        $registro['cumplimiento'] = $cumplimiento;
    }
 // --- FILTRADO OPCIONAL ---
if ($userFiltro !== null) {
    $resultado = array_filter($resultado, fn($r) => $r['user_id'] == (int)$userFiltro);
}
if ($mesFiltro !== null) {
    $resultado = array_filter($resultado, fn($r) => $r['mes'] == $mesFiltro);
}
// --- FIN FILTRADO ---

    // 9. Retornar respuesta ordenada
    return response()->json(
        collect($resultado)->sortBy(['mes', 'usuario'])->values()
    );
}


    

}
