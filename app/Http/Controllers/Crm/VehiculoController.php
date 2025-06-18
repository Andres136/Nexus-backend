<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\VehiculoRequest;
use App\Http\Requests\Crm\VehiculoUpdateRequest;
use App\Models\Crm\DocumentoVehiculo;
use App\Models\Crm\Inspeccion;
use App\Models\Crm\Mantenimiento;
use App\Models\Crm\Vehiculo;
use Carbon\Carbon;
use GuzzleHttp\Promise\Create;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VehiculoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
   public function index(Request $request)
{
    $search = $request->query('search');

   $vehiculos = Vehiculo::with(['fotos', 'documentos', 'mantenimientos', 'inspecciones'])
                     ->where('placa', 'like', "%$search%")
                     ->orWhere('marca', 'like', "%$search%")
                     ->orWhere('modelo', 'like', "%$search%")
                     ->get();


    return response()->json([
        'vehiculos' => $vehiculos
    ], 200);
}


    /**
     * Store a newly created resource in storage.
        */
        public function store(VehiculoRequest $request)
        {
            //Obtener el nombre original del archivo
            $nombre = $request->file('foto')->getClientOriginalName();
            $uniqueName = time() . $nombre;
            //Subir la imagen y almacenar su ruta
            $rutaFoto = $request->file('foto')->storeAs('vehiculos', $uniqueName, 'public');
       
        
        
            // 2. Crear el vehículo con la ruta de la foto incluida
            $vehiculo = Vehiculo::create([
                'placa' => $request->placa,
                'marca' => $request->marca,
                'modelo' => $request->modelo,
                'tipo' => $request->tipo,
                'anio' => $request->anio,
                'kilometraje_actual' => $request->kilometraje_actual ?? 0,
                'estado' => $request->estado,
                'observaciones' => $request->observaciones,
                'foto' => $rutaFoto,
                'licencia_transito' => $request->licencia_transito,
                'conductor' => $request->conductor,
            ]);
        
            return response()->json([
                'message' => 'Vehículo creado exitosamente',
                'vehiculo' => $vehiculo,
                'id' => $vehiculo->id
            ], 201);
        }
        


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // 1. Obtener el vehículo por ID
        $vehiculo = Vehiculo::find($id);
        // 2. Retornar la vista con el vehículo
        return response()->json([
            'vehiculo' => $vehiculo
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(VehiculoUpdateRequest $request, Vehiculo $vehiculo)
    {
        // Laravel ya hace el find o lanza 404 automáticamente si no lo encuentra
    
        $vehiculo->placa = $request->placa;
        $vehiculo->marca = $request->marca;
        $vehiculo->modelo = $request->modelo;
        $vehiculo->tipo = $request->tipo;
        $vehiculo->anio = $request->anio;
        $vehiculo->kilometraje_actual = $request->kilometraje_actual;
        $vehiculo->estado = $request->estado;
        $vehiculo->observaciones = $request->observaciones;
        $vehiculo->licencia_transito = $request->licencia_transito;
        $vehiculo->conductor = $request->conductor;
    
        if ($request->hasFile('foto')) {
            $nombre = $request->file('foto')->getClientOriginalName();
            $uniqueName = time() . '_' . $nombre;
            $rutaFoto = $request->file('foto')->storeAs('vehiculos', $uniqueName, 'public');
            $vehiculo->foto = $rutaFoto;
        }
    
        $vehiculo->save();
    
        return response()->json([
            'message' => 'Vehículo actualizado con éxito',
            'vehiculo' => $vehiculo
        ], 200);
    }
    

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $vehiculo = Vehiculo::findOrFail($id);
        $vehiculo->delete();
    
        return response()->json([
            'message' => 'Vehículo eliminado con éxito',
        ]);
    }
    


   // Traer estadisticas de vehiculos mantenimientos soat a vencer gastos 
 
   
   public function getDashboardVehiculos()
   {
       
    $now = Carbon::now();
    $mesActual = $now->month;
    $mesAnterior = $now->copy()->subMonth()->month;

        // Lista de meses abreviados
        $meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
            // Generar histórico de gastos por mes del año actual
    $historico_gastos = [];
    for ($i = 1; $i <= 12; $i++) {
        $total = Mantenimiento::whereYear('created_at', $now->year)
            ->whereMonth('created_at', $i)
            ->sum('costo');

        $historico_gastos[] = [
            'mes' => $meses[$i - 1],
            'total' => $total,
        ];
    }

    return response()->json([
        'total_vehiculos' => Vehiculo::count(),

        'mantenimientos_pendientes' => Mantenimiento::whereNull('fecha_realizado')->count(),

        'mantenimientos_realizados' => Mantenimiento::whereNotNull('fecha_realizado')->count(),

        'mantenimientos_proximos' => Mantenimiento::whereNull('fecha_realizado')
            ->whereBetween('fecha_programada', [$now, $now->copy()->addDays(15)])
            ->count(),

        'soat' => [
            'vencidos' => DocumentoVehiculo::where('tipo_documento', 'SOAT')
                ->where('fecha_vencimiento', '<', $now)
                ->count(),

            'por_vencer' => DocumentoVehiculo::where('tipo_documento', 'SOAT')
                ->whereBetween('fecha_vencimiento', [$now, $now->copy()->addDays(30)])
                ->count(),
        ],

        'gastos' => [
            'actual' => Mantenimiento::whereMonth('created_at', $mesActual)->sum('costo'),
            'anterior' => Mantenimiento::whereMonth('created_at', $mesAnterior)->sum('costo'),
        ],

        'inspecciones' => [
            'actual' => Inspeccion::whereMonth('created_at', $mesActual)->count(),
            'anterior' => Inspeccion::whereMonth('created_at', $mesAnterior)->count(),
        ],
        'historico_gastos' => $historico_gastos,
        'documentos_estado' => [
    'vencidos' => DocumentoVehiculo::where('fecha_vencimiento', '<', $now)->count(),
    'por_vencer' => DocumentoVehiculo::whereBetween('fecha_vencimiento', [$now, $now->copy()->addDays(30)])->count(),
    'vigentes' => DocumentoVehiculo::where('fecha_vencimiento', '>', $now->copy()->addDays(30))->count(),
],
'ultimos_mantenimientos' => Mantenimiento::whereNotNull('fecha_realizado')
    ->latest('fecha_realizado')
    ->take(3)
    ->with('vehiculo') // Asegúrate de tener la relación en el modelo
    ->get(['id', 'vehiculo_id', 'fecha_realizado']),
    'tipos_mantenimiento' => Mantenimiento::select('tipo_mantenimiento', DB::raw('count(*) as total'))
    ->groupBy('tipo_mantenimiento')
    ->get(),

'tipos_mantenimiento' => Mantenimiento::select('tipo_mantenimiento', DB::raw('count(*) as total'))
    ->groupBy('tipo_mantenimiento')
    ->get(),

    ]);

   }
   

   //Traer vehiculos CON TODOS sus mantenimientos, documentos Y INspecciones
   public function vehiculosAll(Request $request)
{
    $query = Vehiculo::with(['mantenimientos', 'documentos', 'inspecciones']);

    // Si hay un término de búsqueda, aplicamos filtro
    if ($request->has('search') && $request->search != '') {
        $search = $request->search;
        $query->where(function($q) use ($search) {
            $q->where('placa', 'LIKE', "%$search%")
              ->orWhere('modelo', 'LIKE', "%$search%")
              ->orWhere('marca', 'LIKE', "%$search%");
        });
    }

    // Paginamos la respuesta
    $vehiculos = $query->paginate(10); // 10 por página, puedes ajustar

    return response()->json([
        'vehiculos' => $vehiculos
    ]);
}

}