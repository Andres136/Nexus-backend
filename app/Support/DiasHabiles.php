<?php

namespace App\Support;

use Carbon\Carbon;

class DiasHabiles
{
    /**
     * Suma días hábiles (lunes a viernes) a una fecha. No descuenta festivos.
     */
    public static function sumar(Carbon $fecha, int $dias): Carbon
    {
        $resultado = $fecha->copy()->startOfDay();

        while ($dias > 0) {
            $resultado->addDay();
            if ($resultado->isWeekday()) {
                $dias--;
            }
        }

        return $resultado;
    }
}
