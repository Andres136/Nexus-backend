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
    ->where('tipo', 'no')
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
            DB::raw("SUM(CASE WHEN tipo = 'no' THEN 1 ELSE 0 END) as total_registros"),
            DB::raw("AVG(CASE WHEN tipo='no' THEN respuesta END) as promedio_respuesta"),
            DB::raw("SUM(CASE WHEN tipo='no' THEN respuesta ELSE 0 END) as total_respuesta")
        )
        ->whereYear('fecha', $anio)
        ->groupBy('id','departamento_id', DB::raw('MONTH(fecha)'))
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
    $departamentos = Departamentos::select('id','nombre')->get();

    $resultado = [];

    foreach ($departamentos as $departamento) {

        $meses = [];

        for ($mes = 1; $mes <= 12; $mes++) {

            $registrosMes = $registros->filter(fn($r) =>
                $r->departamento_id === $departamento->id &&
                $r->mes === $mes
            );

            $total = $registrosMes->sum('total_registros');

            $si = 0;
            $no = 0;

            foreach ($registrosMes as $r) {
                if(isset($verificaciones[$r->id])){
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

    // 🔹 Registros diarios
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

    // 🔹 Novedades
$novedades = DB::table('novedad_diaria')
    ->join(
        'registro_diario',
        'registro_diario.id',
        '=',
        'novedad_diaria.registro_diario_id'
    )
    ->select(
        'registro_diario.departamento_id',
        DB::raw('MONTH(registro_diario.fecha) as mes'),
        DB::raw('COUNT(novedad_diaria.id) as total_novedades')
    )
    ->whereYear('registro_diario.fecha', $anio)
    ->groupBy(
        'registro_diario.departamento_id',
        DB::raw('MONTH(registro_diario.fecha)')
    )
    ->get()
    ->groupBy(['departamento_id', 'mes']);

    $resultado = [];

    foreach ($departamentos as $dep) {
        $meses = [];

        for ($mes = 1; $mes <= 12; $mes++) {
            $r = $registros[$dep->id][$mes][0] ?? null;
            $n = $novedades[$dep->id][$mes][0] ?? null;

            $total = $r->total_registros ?? 0;
            $si = $r->si ?? 0;
            $no = $r->no ?? 0;

            $meses[] = [
                'mes' => $mes,
                'nombre_mes' => Carbon::create()->month($mes)->translatedFormat('F'),
                'total_registros' => $total,
                'si' => $si,
                'no' => $no,
                'cumplimiento' => $total > 0
                    ? round(($si / $total) * 100, 2)
                    : 0,
                'respuestas' => [
                    'total' => (int) ($r->total_respuesta ?? 0),
                    'promedio' => round($r->promedio_respuesta ?? 0, 2),
                ],
                'novedades' => $n->total_novedades ?? 0,
                'estabilidad' => $total > 0
                    ? round((1 - (($n->total_novedades ?? 0) / $total)) * 100, 2)
                    : 100,
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