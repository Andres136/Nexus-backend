<?php

namespace App\Services\RegistroDiario;

use App\Models\Departamentos;
use App\Models\RegistroDiario\Novedades;
use App\Models\RegistroDiario\Preguntas;
use App\Models\RegistroDiario\RegistroDiarios;
use App\Models\RegistroDiario\Verificaciones;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

use function Symfony\Component\Clock\now;

class RegistroDiarioService
{
    public function crearRegistroDiario($data)
    {

        $registroDiario = RegistroDiarios::create([
            'usuario_id' => auth()->id(),
            'departamento_id' => $data['departamento_id'],
            'pregunta_id' => $data['pregunta_id'],
            'respuesta' => $data['respuesta'],
            'observaciones' => $data['observaciones'] ?? null,
            'fecha' => now(),
            'tipo' => $data['tipo'],
        ]);


        if (!empty($data['novedad'])) {
            Novedades::create([
                'registro_diario_id' => $registroDiario->id,
                'descripcion'        => $data['novedad'],
            ]);
        }


        return $registroDiario;
    }

    //Traer registros diarios por id
    public function getRegistroDiarioById($id)
    {
        return RegistroDiarios::find($id);
    }
    public function getByDepartamento(int $departamentoId)
    {
        return RegistroDiarios::with([
            'departamento:id,nombre',
            'usuario:id,name',
            'verificaciones.pregunta:id,pregunta',
            'pregunta:id,pregunta'
        ])
            ->where('departamento_id', $departamentoId)
            ->whereIn('tipo', ['si', 'no'])
            ->whereBetween('fecha', [
                Carbon::today()->startOfDay(),
                Carbon::today()->endOfDay()
            ])
            ->latest()
            ->get();
    }




    public function estadisticasAnuales(int $anio)
    {
        // 1️⃣ Registros diarios (solo tipo NO)
       $registros = RegistroDiarios::select(
        'id',
        'departamento_id',
        DB::raw('MONTH(fecha) as mes'),
        DB::raw("SUM(CASE WHEN tipo IN ('si', 'no') THEN 1 ELSE 0 END) as total_registros"),
        DB::raw("AVG(CASE WHEN tipo IN ('si', 'no') THEN respuesta END) as promedio_respuesta"),
        DB::raw("SUM(CASE WHEN tipo IN ('si', 'no') THEN respuesta ELSE 0 END) as total_respuesta")
    )
        ->whereYear('fecha', $anio)
        ->whereIn('tipo', ['si', 'no'])
        ->groupBy('id', 'departamento_id', DB::raw('MONTH(fecha)'))
        ->get();

        // 2️⃣ Verificaciones
        $verificaciones = Verificaciones::select(
            'registro_diario_id',
            DB::raw('MONTH(fecha) as mes'),
            DB::raw("SUM(CASE WHEN estado = 'si' THEN 1 ELSE 0 END) as si"),
            DB::raw("SUM(CASE WHEN estado = 'no' THEN 1 ELSE 0 END) as no")
        )
            ->whereYear('fecha', $anio)
            ->groupBy('registro_diario_id', DB::raw('MONTH(fecha)'))
            ->get()
            ->keyBy('registro_diario_id');

        // 3️⃣ Departamentos
        $departamentos = Departamentos::select('id', 'nombre')->get();

        $resultado = [];

        foreach ($departamentos as $departamento) {

            $meses = [];

            for ($mes = 1; $mes <= 12; $mes++) {

                $registrosMes = $registros->filter(
                    fn($r) =>
                    $r->departamento_id === $departamento->id &&
                        $r->mes === $mes
                );

                $total = $registrosMes->sum('total_registros');

                $si = 0;
                $no = 0;

                foreach ($registrosMes as $r) {
                    if (isset($verificaciones[$r->id])) {
                        $si += $verificaciones[$r->id]->si;
                        $no += $verificaciones[$r->id]->no;
                    }
                }

                $verificados = $si + $no;

                $meses[] = [
                    'mes' => $mes,
                    'nombre_mes' => Carbon::create()->month($mes)->translatedFormat('F'),
                    'total_registros' => $total,
                    'verificados' => $verificados,
                    'pendientes' => max($total - $verificados, 0),
                    'cumplimiento' => $total > 0
                        ? round(($verificados / $total) * 100, 2)
                        : 0,
                    'estado' => [
                        'si' => $si,
                        'no' => $no,
                    ],
                    'respuestas' => [
                        'promedio' => round($registrosMes->avg('promedio_respuesta') ?? 0, 2),
                        'total' => $registrosMes->sum('total_respuesta') ?? 0,
                    ],
                ];
            }

            $resultado[] = [
                'departamento_id' => $departamento->id,
                'departamento' => $departamento->nombre,
                'meses' => $meses,
            ];
        }

        return [
            'anio' => $anio,
            'departamentos' => $resultado,
        ];
    }

 public function estadisticasAnualesDepartamentos(int $anio)
{
    $departamentos = Departamentos::all();

    // 🔹 REGISTROS
    $registros = RegistroDiarios::select(
        'departamento_id',
        DB::raw('MONTH(fecha) as mes'),
        DB::raw('COUNT(*) as total_registros'),
        DB::raw("SUM(CASE WHEN tipo = 'si' THEN 1 ELSE 0 END) as si"),
        DB::raw("SUM(CASE WHEN tipo = 'no' THEN 1 ELSE 0 END) as no"),
        DB::raw('SUM(respuesta) as total_respuesta'),
        DB::raw('AVG(respuesta) as promedio_respuesta')
    )
        ->whereYear('fecha', $anio)
        ->groupBy('departamento_id', DB::raw('MONTH(fecha)'))
        ->get()
        ->groupBy(['departamento_id', 'mes']);

    // 🔥 TAREAS (1 SOLA QUERY)
  $tareas = DB::table('tareas')
    ->select(
        'departamento_id',
        DB::raw('MONTH(fecha_fin) as mes'),

        DB::raw("
            COUNT(
                CASE 
                    WHEN estado_id IN (2,5) THEN 1
                END
            ) as completadas_mes
        "),

        DB::raw("
            COUNT(
                CASE 
                    WHEN estado_id IN (2,5)
                    AND fecha_cerrado <= fecha_fin
                    THEN 1 
                END
            ) as a_tiempo
        "),

        DB::raw("
            COUNT(
                CASE 
                    WHEN estado_id IN (2,5)
                    AND fecha_cerrado > fecha_fin
                    THEN 1 
                END
            ) as tarde
        ")
    )
    ->whereYear('fecha_fin', $anio)
    ->groupBy('departamento_id', DB::raw('MONTH(fecha_fin)'))
    ->get()
    ->groupBy(['departamento_id', 'mes']);

    // 🔹 PLANIFICADAS
    $planificadas = DB::table('tareas')
        ->select(
            'departamento_id',
            DB::raw('MONTH(fecha_fin) as mes'),
            DB::raw('COUNT(*) as total_planificadas')
        )
        ->whereYear('fecha_fin', $anio)
        ->groupBy('departamento_id', DB::raw('MONTH(fecha_fin)'))
        ->get()
        ->groupBy(['departamento_id', 'mes']);

    // 🔹 NOVEDADES (1 SOLO LOOP)
    $novedadesPorMes = [];
    $novedadesEstabilidadPorMes = [];

    for ($mes = 1; $mes <= 12; $mes++) {

        $inicioMes = Carbon::create($anio, $mes, 1)->startOfMonth();
        $finMes    = Carbon::create($anio, $mes, 1)->endOfMonth();

        $novedadesPorMes[$mes] = DB::table('novedad_diaria')
    ->join('registro_diario', 'registro_diario.id', '=', 'novedad_diaria.registro_diario_id')
    ->select(
        'registro_diario.departamento_id',
        DB::raw('COUNT(novedad_diaria.id) as total_novedades')
    )
    ->where('novedad_diaria.created_at', '<=', $finMes)
    ->whereIn('novedad_diaria.estado', ['ABIERTA', 'EN_PROCESO'])
    ->groupBy('registro_diario.departamento_id')
    ->get()
    ->keyBy('departamento_id');

$novedadesEstabilidadPorMes[$mes] = DB::table('novedad_diaria')
    ->join('registro_diario', 'registro_diario.id', '=', 'novedad_diaria.registro_diario_id')
    ->select(
        'registro_diario.departamento_id',
        DB::raw('COUNT(DISTINCT novedad_diaria.registro_diario_id) as registros_con_novedad_mes')
    )
    ->where('novedad_diaria.created_at', '<=', $finMes)
    ->whereIn('novedad_diaria.estado', ['ABIERTA', 'EN_PROCESO'])
    ->groupBy('registro_diario.departamento_id')
    ->get()
    ->keyBy('departamento_id');
    }

    // 🔥 RESULTADO FINAL
    $resultado = [];

    foreach ($departamentos as $dep) {

        $meses = [];

        for ($mes = 1; $mes <= 12; $mes++) {

            $r = $registros[$dep->id][$mes][0] ?? null;
            $t = $tareas[$dep->id][$mes][0] ?? null;
            $p = $planificadas[$dep->id][$mes][0] ?? null;
            $n = $novedadesPorMes[$mes][$dep->id] ?? null;
            $nEstabilidad = $novedadesEstabilidadPorMes[$mes][$dep->id] ?? null;

            $totalRegistros = $r->total_registros ?? 0;
            $si = $r->si ?? 0;
            $no = $r->no ?? 0;

            $totalPlanificadas = $p->total_planificadas ?? 0;
            $completadas = $t->completadas_mes ?? 0;
            $aTiempo = $t->a_tiempo ?? 0;
            $tarde = $t->tarde ?? 0;

            $totalNovedades = $n->total_novedades ?? 0;
            $registrosConNovedadMes = $nEstabilidad->registros_con_novedad_mes ?? 0;

            // 🔹 MÉTRICAS
           $rendimiento = $totalPlanificadas > 0
    ? round(($completadas / $totalPlanificadas) * 100, 2)
    : 100;

            $eficienciaTiempo = $completadas > 0
    ? round(($aTiempo / $completadas) * 100, 2)
    : 0;

            $meses[] = [
                'mes' => $mes,
                'nombre_mes' => Carbon::create()->month($mes)->translatedFormat('F'),

                'total_registros' => $totalRegistros,
                'si' => $si,
                'no' => $no,
'cumplimiento' => $totalRegistros > 0
    ? round(($si / $totalRegistros) * 100, 2)
    : 100,

                'respuestas' => [
                    'total' => (int) ($r->total_respuesta ?? 0),
                    'promedio' => round($r->promedio_respuesta ?? 0, 2),
                ],

                'novedades' => $totalNovedades,

                'estabilidad' => $totalRegistros > 0
                    ? round((($totalRegistros - $registrosConNovedadMes) / $totalRegistros) * 100, 2)
                    : 100,

                'rendimiento' => $rendimiento,
                'eficiencia_tiempo' => $eficienciaTiempo,
                'a_tiempo' => $aTiempo,
                'tarde' => $tarde,
            ];
        }

        $resultado[] = [
            'departamento_id' => $dep->id,
            'departamento' => $dep->nombre,
            'meses' => $meses,
        ];
    }

    return [
        'anio' => $anio,
        'departamentos' => $resultado,
    ];
}
}