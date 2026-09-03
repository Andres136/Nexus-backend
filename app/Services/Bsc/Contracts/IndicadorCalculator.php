<?php

namespace App\Services\Bsc\Contracts;

/**
 * Un calculator sabe obtener el valor de UN indicador del BSC para un
 * periodo (mes) dado, leyendo los datos reales de su módulo dueño.
 *
 * Se vincula a una fila de indicadores_procesos por su `calculo_key`.
 */
interface IndicadorCalculator
{
    /** Clave única que se guarda en indicadores_procesos.calculo_key */
    public function key(): string;

    /** Etiqueta legible para la pantalla de clasificación */
    public function label(): string;

    /** Unidad del resultado: '%', '$', 'dias', 'ratio' */
    public function unidad(): string;

    /**
     * @param  string  $periodo  'YYYY-MM'
     * @return array{valor: float, numerador: float|null, denominador: float|null, detalle: string|null}
     */
    public function calcular(string $periodo): array;
}
