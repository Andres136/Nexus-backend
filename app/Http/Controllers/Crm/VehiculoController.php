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

class VehiculoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
public function index(Request $request)
{
    $search = $request->query('search');

    $vehiculos = Vehiculo::with(['fotos', 'documentos', 'mantenimientos', 'inspecciones',''])
        ->when($search, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->where('placa', 'like', "%$search%")
                  ->orWhere('marca', 'like', "%$search%")
                  ->orWhere('modelo', 'like', "%$search%");
            });
        })
        ->paginate(10); // ✅ añade paginación si es listado principal

    return response()->json(
        $vehiculos, 200);
}

public function options()
{
    $vehiculos = Vehiculo::all();
    return response()->json($vehiculos, 200);
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
                'nombre' => $request->nombre,
                'tipo_servicio' => $request->tipo_servicio,
                'color' => $request->color,
                'tipo_carroceria' => $request->tipo_carroceria,
                'tipo_combustible' => $request->tipo_combustible,
                'numero_motor' => $request->numero_motor,
                'numero_chasis' => $request->numero_chasis,
                'propietario' => $request->propietario,
                'identificacion' => $request->identificacion,
                'organismo_transito' => $request->organismo_transito,
                'fecha_matricula' => $request->fecha_matricula,
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
            'vehiculo' => $vehiculo,

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
        $vehiculo->nombre = $request->nombre;
        $vehiculo->tipo_servicio = $request->tipo_servicio;
        $vehiculo->color = $request->color;
        $vehiculo->tipo_carroceria = $request->tipo_carroceria;
        $vehiculo->tipo_combustible = $request->tipo_combustible;
        $vehiculo->numero_motor = $request->numero_motor;
        $vehiculo->numero_chasis = $request->numero_chasis;
        $vehiculo->propietario = $request->propietario;
        $vehiculo->identificacion = $request->identificacion;
        $vehiculo->organismo_transito = $request->organismo_transito;
        $vehiculo->fecha_matricula = $request->fecha_matricula;
    
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
    $hoy = $now->copy()->startOfDay();
    $inicioMesActual = $now->copy()->startOfMonth();
    $inicioMesAnterior = $now->copy()->subMonthNoOverflow()->startOfMonth();
    $finMesAnterior = $inicioMesActual->copy()->subDay()->endOfDay();
    $en30dias = $hoy->copy()->addDays(30);
    $en15dias = $hoy->copy()->addDays(15);

    $meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

    // Histórico de gastos del año en curso (por mes de ejecución del mantenimiento).
    $gastosPorMes = Mantenimiento::query()
        ->whereNotNull('fecha_realizado')
        ->whereYear('fecha_realizado', $now->year)
        ->selectRaw('MONTH(fecha_realizado) as mes, SUM(costo) as total')
        ->groupBy('mes')
        ->pluck('total', 'mes');

    $historico_gastos = [];
    for ($i = 1; $i <= 12; $i++) {
        $historico_gastos[] = [
            'mes' => $meses[$i - 1],
            'total' => (float) ($gastosPorMes[$i] ?? 0),
        ];
    }

    // Gasto real ejecutado en un rango de fechas (fecha_realizado).
    $gastoEnRango = fn ($desde, $hasta) => (float) Mantenimiento::whereNotNull('fecha_realizado')
        ->whereBetween('fecha_realizado', [$desde, $hasta])
        ->sum('costo');

    // Inspecciones realizadas en un rango (fecha_realizado).
    $inspeccionesEnRango = fn ($desde, $hasta) => Inspeccion::whereNotNull('fecha_realizado')
        ->whereBetween('fecha_realizado', [$desde, $hasta])
        ->count();

    // Mantenimientos ejecutados en un rango (fecha_realizado).
    $mttoRealizadosEnRango = fn ($desde, $hasta) => Mantenimiento::whereNotNull('fecha_realizado')
        ->whereBetween('fecha_realizado', [$desde, $hasta])
        ->count();

    return response()->json([
        'total_vehiculos' => Vehiculo::count(),

        'vehiculos_por_estado' => Vehiculo::selectRaw('estado, COUNT(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado'),

        // Backlog acumulado (no depende del periodo).
        'mantenimientos_pendientes' => Mantenimiento::whereNull('fecha_realizado')->count(),
        'mantenimientos_realizados' => Mantenimiento::whereNotNull('fecha_realizado')->count(),

        'mantenimientos_vencidos' => Mantenimiento::whereNull('fecha_realizado')
            ->whereNotNull('fecha_programada')
            ->whereDate('fecha_programada', '<', $hoy)
            ->count(),

        'mantenimientos_proximos' => Mantenimiento::whereNull('fecha_realizado')
            ->whereBetween('fecha_programada', [$hoy, $en15dias])
            ->count(),

        'soat' => [
            'vencidos' => DocumentoVehiculo::where('tipo_documento', 'SOAT')
                ->whereDate('fecha_vencimiento', '<', $hoy)
                ->count(),
            'por_vencer' => DocumentoVehiculo::where('tipo_documento', 'SOAT')
                ->whereDate('fecha_vencimiento', '>=', $hoy)
                ->whereDate('fecha_vencimiento', '<=', $en30dias)
                ->count(),
        ],

        'documentos_estado' => [
            'vencidos' => DocumentoVehiculo::whereDate('fecha_vencimiento', '<', $hoy)->count(),
            'por_vencer' => DocumentoVehiculo::whereDate('fecha_vencimiento', '>=', $hoy)
                ->whereDate('fecha_vencimiento', '<=', $en30dias)
                ->count(),
            'vigentes' => DocumentoVehiculo::whereDate('fecha_vencimiento', '>', $en30dias)->count(),
        ],

        // Comparativa mes actual vs mes anterior (todo por fecha de ejecución real).
        'gastos' => [
            'actual' => $gastoEnRango($inicioMesActual, $now),
            'anterior' => $gastoEnRango($inicioMesAnterior, $finMesAnterior),
        ],
        'inspecciones' => [
            'actual' => $inspeccionesEnRango($inicioMesActual, $now),
            'anterior' => $inspeccionesEnRango($inicioMesAnterior, $finMesAnterior),
        ],
        'mantenimientos_mes' => [
            'actual' => $mttoRealizadosEnRango($inicioMesActual, $now),
            'anterior' => $mttoRealizadosEnRango($inicioMesAnterior, $finMesAnterior),
        ],

        'historico_gastos' => $historico_gastos,

        'ultimos_mantenimientos' => Mantenimiento::whereNotNull('fecha_realizado')
            ->latest('fecha_realizado')
            ->take(5)
            ->with('vehiculo:id,placa,marca,modelo')
            ->get(['id', 'vehiculo_id', 'fecha_realizado', 'tipo_mantenimiento', 'costo']),

        'tipos_mantenimiento' => Mantenimiento::selectRaw('tipo_mantenimiento, COUNT(*) as total')
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

//Trear usuarios  que tengan rol 8 de conductor

}