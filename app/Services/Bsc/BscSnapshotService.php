<?php

namespace App\Services\Bsc;

use App\Models\Indicadores;
use App\Models\RegistroIndicador;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Congela el valor de cada indicador automático del BSC en registro_indicadores
 * (mismo patrón que KpiService::guardarSnapshotCarteraMensual).
 *
 * Se programa a diario: mientras el mes está en curso, cada corrida refresca
 * su propia fila; al pasar de mes, el valor queda fijo con el de la última
 * corrida. `fecha` guarda la fecha real de la corrida (fecha de registro),
 * `periodo` el mes al que corresponde el valor.
 *
 * Rendimiento: el cálculo se hace una vez por `calculo_key` (aunque lo compartan
 * varios indicadores) y la escritura es un único `upsert` en bloque.
 */
class BscSnapshotService
{
    public function __construct(private BscCalculatorRegistry $registry)
    {
    }

    /**
     * @param  string|null  $periodo  'YYYY-MM'; por defecto el mes en curso.
     * @return array<int, array{indicador: string, key: string, valor: float|string}>
     */
    public function ejecutar(?string $periodo = null): array
    {
        $periodo ??= now()->format('Y-m');
        $ahora = Carbon::now();

        $resultados = [];
        $filas = [];
        $cacheCalculo = [];

        $indicadores = Indicadores::whereNotNull('calculo_key')
            ->get(['id', 'nombre', 'calculo_key']);

        foreach ($indicadores as $indicador) {
            $key = $indicador->calculo_key;

            if (!\array_key_exists($key, $cacheCalculo)) {
                $cacheCalculo[$key] = $this->resolver($key, $periodo, $indicador->id);
            }

            $r = $cacheCalculo[$key];

            if ($r === null) {
                continue;
            }

            if ($r === 'ERROR') {
                $resultados[] = ['indicador' => $indicador->nombre, 'key' => $key, 'valor' => 'ERROR'];
                continue;
            }

            $filas[] = [
                'indicador_id'  => $indicador->id,
                'periodo'       => $periodo,
                'origen'        => 'automatico',
                'fecha'         => $ahora->toDateString(),
                'valor'         => $r['valor'],
                'numerador'     => $r['numerador'] ?? null,
                'denominador'   => $r['denominador'] ?? null,
                'observaciones' => $r['detalle'] ?? null,
                'user_id'       => null,
                'created_at'    => $ahora,
                'updated_at'    => $ahora,
            ];

            $resultados[] = [
                'indicador' => $indicador->nombre,
                'key'       => $key,
                'valor'     => $r['valor'],
            ];
        }

        if ($filas !== []) {
            // 1 sola query: inserta o actualiza contra el índice único
            // (indicador_id, periodo, origen).
            RegistroIndicador::upsert(
                $filas,
                ['indicador_id', 'periodo', 'origen'],
                ['fecha', 'valor', 'numerador', 'denominador', 'observaciones', 'updated_at'],
            );
        }

        return $resultados;
    }

    /** @return array|string|null  datos del cálculo, 'ERROR', o null si no hay calculator */
    private function resolver(string $key, string $periodo, int $indicadorId): array|string|null
    {
        $calc = $this->registry->get($key);

        if (!$calc) {
            Log::warning("BSC snapshot: calculo_key sin calculator '{$key}' (indicador {$indicadorId})");

            return null;
        }

        try {
            return $calc->calcular($periodo);
        } catch (\Throwable $e) {
            Log::error("BSC snapshot: fallo calculando '{$key}': {$e->getMessage()}");

            return 'ERROR';
        }
    }
}
