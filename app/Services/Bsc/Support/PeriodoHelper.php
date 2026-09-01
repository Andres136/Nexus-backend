<?php

namespace App\Services\Bsc\Support;

use Illuminate\Support\Carbon;

trait PeriodoHelper
{
    /** Primer y último instante del mes 'YYYY-MM'. */
    protected function rangoMes(string $periodo): array
    {
        $inicio = Carbon::createFromFormat('Y-m-d', $periodo . '-01')->startOfMonth();

        return [$inicio, $inicio->copy()->endOfMonth()];
    }

    /** Mismo, pero del mes anterior al periodo dado. */
    protected function rangoMesAnterior(string $periodo): array
    {
        $inicio = Carbon::createFromFormat('Y-m-d', $periodo . '-01')->startOfMonth()->subMonth();

        return [$inicio, $inicio->copy()->endOfMonth()];
    }
}
