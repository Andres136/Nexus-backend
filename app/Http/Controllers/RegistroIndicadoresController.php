<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegistroIndicadoresRequest;
use App\Http\Requests\UpdateRejistroIndicadoresRequest;
use App\Models\Indicadores;

use App\Models\RegistroIndicador;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon as SupportCarbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class RegistroIndicadoresController extends Controller
{
   
public function index(Request $request)
{
    $user = auth()->user();
    $departamento = $user->departamento;

    $mes = $request->input('mes', date('m'));
    $anio = $request->input('anio', date('Y'));
    $departamento_id = $request->input('departamento_id');

    // 🔹 Filtro por rol y departamento
    if ($user->role_id == 1) {
        $indicadoresIds = $departamento_id
            ? Indicadores::where('departamento_id', $departamento_id)->pluck('id')
            : Indicadores::pluck('id');
    } else {
        if (!$departamento || $departamento->responsable_id != $user->id) {
            return response()->json([
                'message' => 'No autorizado para ver registros en este departamento.'
            ], 403);
        }
        $indicadoresIds = Indicadores::where('departamento_id', $departamento->id)->pluck('id');
    }

    // 🔹 Consulta de registros
    $registros = RegistroIndicador::with(['indicador.departamento', 'user'])
        ->whereIn('indicador_id', $indicadoresIds)
        ->whereMonth('fecha', $mes)
        ->whereYear('fecha', $anio)
        ->get();

    // 🔹 Procesamiento
$registros = $registros->map(function ($registro) {
    $meta  = (float) ($registro->indicador->meta ?? 0);
    $valor = (float) $registro->valor;
    $formula = strtolower($registro->indicador->formula ?? '');
    $nombre  = strtolower($registro->indicador->nombre ?? '');
    $tipoMeta = strtolower(trim($registro->indicador->tipo_meta ?? 'mayor'));

    $esDias = str_contains($formula, 'dia') || str_contains($nombre, 'dia');
    $resultado = '';
    $estado = 'sin datos';

    if ($esDias) {
        // 🔹 Indicador medido en días
        $fechaRegistro = Carbon::parse($registro->fecha);
        $fechaInicioMes = Carbon::createFromDate(Carbon::now()->year, Carbon::now()->month, 1);
    $dias = floor($fechaInicioMes->diffInDays($fechaRegistro, false));
$resultado = "{$dias} días";

     

        if ($meta > 0) {
            if ($dias <= $meta) $estado = 'ok';
            elseif ($dias <= $meta + 3) $estado = 'medio';
            else $estado = 'critico';
        }
    } else {
        // 🔹 Indicadores numéricos o porcentuales
        $porcentaje = ($meta && $meta != 0) ? round(($valor / $meta) * 100, 2) : null;

        switch ($tipoMeta) {
            case 'mayor':
                $estado = ($valor >= $meta) ? 'ok'
                    : (($valor >= ($meta * 0.8)) ? 'medio' : 'critico');
                break;

            case 'menor':
                $estado = ($valor <= $meta) ? 'ok'
                    : (($valor <= ($meta * 1.2)) ? 'medio' : 'critico');
                break;

            default:
                $estado = ($valor >= $meta) ? 'ok' : 'critico';
                break;
        }

        if ($porcentaje !== null && $meta >= 10) {
            $resultado = "{$porcentaje}%";
        } else {
            $resultado = rtrim(rtrim(number_format($valor, 2, '.', ''), '0'), '.');
        }
    }

    // ✅ Asegurar resultado y valor numérico visibles
    if (empty($resultado) || $resultado === '0') {
        $resultado = rtrim(rtrim(number_format($valor, 2, '.', ''), '0'), '.');
    }

    $registro->valor_real = $valor;
    $registro->resultado = $resultado;
    $registro->estado = $estado;
    return $registro;
});



    return response()->json(['data' => $registros], 200);
}


     


    /**
     * Store a newly created resource in storage.
     */
    public function store(RegistroIndicadoresRequest $request)
    {
           Log::debug('Entrando al método store de RegistroIndicadoresController');
         $user = auth()->user();
         //Buscar indicador con su departamento relacionado
            $indicador = Indicadores::with('departamento')->find($request->indicador_id);
            if(!$indicador){
                return response()->json(['message' => 'Indicador no encontrado.'], 404);
            }
            $departamento = $indicador->departamento;
            if(!$departamento || $departamento->responsable_id != $user->id){
                return response()->json(['message' => 'No autorizado para crear registros en este departamento.'], 403);
            }

            //Manejar archivo si existe 
            $path = null;
            if ($request->hasFile('documento')) {
                $archivo = $request->file('documento')->getClientOriginalName();
                $uniqueName= time().$archivo;
                $path = $request->file('documento')->storeAs('documentos_indicadores',$uniqueName, 'public');
            }
            try {
                $registro = RegistroIndicador::create([
                    'indicador_id' => $request->indicador_id,
                    'fecha' => $request->fecha,
                    'valor' => $request->valor,
                    'observaciones' => $request->observaciones,
                    'documento' => $path,
                    'user_id' => $user->id,
                ]);
                return response()->json(['message' => 'Registro creado con éxito', 'data' => $registro], 201);
            } catch (\Exception $e) {
                Log::error('Error al crear el registro: ' . $e->getMessage());
                return response()->json(['message' => 'Error al crear el registro', 'error' => $e->getMessage()], 500);
            }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $query = RegistroIndicador::with('indicador', 'user')->find($id);
        if (!$query) {
            return response()->json(['message' => 'Registro no encontrado'], 404);
        }
        return response()->json(['data' => $query], 200);
    }

    /**
     * Update the specified resource in storage.
     */
 public function update(UpdateRejistroIndicadoresRequest $request, string $id)
{
      $user = auth()->user();

    // 1) Cargar el registro actual con su indicador y departamento
    $registro = RegistroIndicador::with('indicador.departamento')->find($id);
    if (!$registro) {
        return response()->json(['message' => 'Registro no encontrado'], 404);
    }
    // 2) Resolver el indicador “de referencia” para autorización
    //    - Si NO cambia indicador, usamos el del registro
    //    - Si cambia, validamos contra el nuevo indicador también
    $indicadorActual = $registro->indicador; // relación belongsTo('Indicadores', 'indicador_id')
    $indicadorRef = $indicadorActual;

    if ($request->filled('indicador_id') && (int)$request->indicador_id !== (int)$registro->indicador_id) {
        $nuevoIndicador = Indicadores::with('departamento')->find($request->indicador_id);
        if (!$nuevoIndicador) {
            return response()->json(['message' => 'Indicador destino no encontrado'], 404);
        }
        $indicadorRef = $nuevoIndicador;
    }

    // 3) Autorización: responsable del departamento del indicador “de referencia”
    $departamento = $indicadorRef->departamento;
    if (!$departamento || $departamento->responsable_id !== $user->id) {
        return response()->json(['message' => 'No autorizado para actualizar registros en este departamento.'], 403);
    }

    // 4) Preparar datos a actualizar (solo los que llegaron)
    $data = $request->only(['indicador_id', 'fecha', 'valor', 'observaciones']);

    // (opcional) Si quieres que el "propietario del cambio" sea el autenticado:
    // $data['user_id'] = $user->id;

    // 5) Reemplazo de archivo si llegó uno nuevo
    if ($request->hasFile('documento')) {
        // borrar el anterior si existía (y si era del disco public)
        if ($registro->documento) {
            Storage::disk('public')->delete($registro->documento);
        }
        $original = $request->file('documento')->getClientOriginalName();
        $unique   = time().'_'.$original;
        $path     = $request->file('documento')->storeAs('documentos_indicadores', $unique, 'public');
        $data['documento'] = $path;
    }

    // 6) Actualizar
    $registro->fill($data);
    $registro->save();

    // Si cambiaste indicador_id, puedes refrescar relaciones
    $registro->load('indicador.departamento', 'user');

    return response()->json([
        'message' => 'Registro actualizado con éxito',
        'data'    => $registro
    ], 200);

}


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = auth()->user();
        if (!in_array($user->role_id, [1, 2])) {
            return response()->json(['message' => 'No autorizado para eliminar registros.'], 403);
    }

    $registro = RegistroIndicador::find($id);
    if (!$registro) {
        return response()->json(['message' => 'Registro no encontrado'], 404);
    }

    // Elimina el archivo si existe
    if ($registro->documento) {
        Storage::disk('public')->delete($registro->documento);
    }

    $registro->delete();

    return response()->json(['message' => 'Registro eliminado con éxito'], 200);
}

// Función para descargar el documento asociado a un registro
    public function descargarDocumento($id)
    {
        $registro = RegistroIndicador::find($id);
        if (!$registro || !$registro->documento) {
            return response()->json(['message' => 'Documento no encontrado'], 404);
        }

        $filePath = storage_path('app/public/' . $registro->documento);
        if (!file_exists($filePath)) {
            return response()->json(['message' => 'Archivo no existe en el servidor'], 404);
        }

        return response()->download($filePath);
    }



public function indexByCompany(Request $request)
{
    $user = auth()->user();

    if (!in_array($user->role_id, [1, 2])) {
        return response()->json(['message' => 'No autorizado.'], 403);
    }

    $mes = $request->input('mes', date('m'));
    $anio = $request->input('anio', date('Y'));

    $indicadores = Indicadores::with([
        'departamento',
        'registros' => function ($query) use ($mes, $anio) {
            $query->whereMonth('fecha', $mes)
                  ->whereYear('fecha', $anio);
        }
    ])->get();

    $result = $indicadores->map(function ($indicador) {
        return [
            'id' => $indicador->id,
            'nombre' => $indicador->nombre,
            'meta' => $indicador->meta,
            'frecuencia' => $indicador->frecuencia,
            'tipo_meta' => $indicador->tipo_meta,
            'departamento' => [
                'id' => $indicador->departamento->id,
                'nombre' => $indicador->departamento->nombre,
            ],
            'registro' => $indicador->registros->first()
                ? $this->procesarRegistro($indicador->registros->first())
                : null
        ];
    });

    return response()->json(['data' => $result], 200);
}
public function procesarRegistro($registro)
{
    $indicador = $registro->indicador;
    $meta      = (float) ($indicador->meta ?? 0);
    $valor     = (float) $registro->valor;
    $tipoMeta  = strtolower(trim($indicador->tipo_meta ?? 'mayor')); // puede ser ≥, ≤, etc.
    $formula   = strtolower($indicador->formula ?? '');
    $nombre    = strtolower($indicador->nombre ?? '');

    $esDias = str_contains($formula, 'dia') || str_contains($nombre, 'dia');
    $resultado = null;
    $estado = 'Sin datos';
    $porcentaje = null;

    // 🔹 Indicadores tipo “en días”
    if ($esDias) {
        $fechaRegistro = \Carbon\Carbon::parse($registro->fecha);
        $fechaInicioMes = \Carbon\Carbon::createFromDate(
            \Carbon\Carbon::now()->year,
            \Carbon\Carbon::now()->month,
            1
        );
        $dias = $fechaInicioMes->diffInDays($fechaRegistro, false);
        $resultado = $dias . ' días';

        if ($meta > 0) {
            if ($dias <= $meta) {
                $estado = 'OK';
            } elseif ($dias <= $meta + 3) {
                $estado = 'Medio';
            } else {
                $estado = 'Crítico';
            }
        }
    } else {
        // 🔹 Indicadores por porcentaje o valor absoluto
        if ($meta > 0) {
            $porcentaje = round(($valor / $meta) * 100, 2);
            $resultado = "{$porcentaje}%";
        } else {
            $resultado = "{$valor}";
        }

        switch (true) {
            // ✅ Si la meta es "≥"
            case str_contains($tipoMeta, '≥') || str_contains($tipoMeta, 'mayor'):
                if ($valor >= $meta) {
                    $estado = 'OK';
                } elseif ($valor >= ($meta * 0.8)) {
                    $estado = 'Medio';
                } else {
                    $estado = 'Crítico';
                }
                break;

            // ✅ Si la meta es "≤"
            case str_contains($tipoMeta, '≤') || str_contains($tipoMeta, 'menor'):
                if ($valor <= $meta) {
                    $estado = 'OK';
                } elseif ($valor <= ($meta * 1.2)) {
                    $estado = 'Medio';
                } else {
                    $estado = 'Crítico';
                }
                break;

            // ✅ Si la meta es "=", igualdad exacta
            case str_contains($tipoMeta, '=') || str_contains($tipoMeta, 'igual'):
                $estado = ($valor == $meta) ? 'OK' : 'Crítico';
                break;

            default:
                $estado = 'Desconocido';
                break;
        }
    }

    // 🔹 Respuesta limpia (no altera el modelo original)
    return [
        'id' => $registro->id,
        'fecha' => $registro->fecha,
        'valor' => $valor,
        'meta' => $meta,
        'tipo_meta' => $tipoMeta,
        'resultado' => $resultado,
        'estado' => $estado,
        'porcentaje_meta' => $porcentaje,
    ];
}



}
