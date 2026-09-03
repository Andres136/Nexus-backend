<?php

namespace App\Models\Bsc;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class BscEtapa extends Model
{
    protected $table = 'bsc_etapas';

    protected $fillable = [
        'numero',
        'anio',
        'nombre',
        'descripcion',
        'estado',
        'responsable_id',
        'fecha_objetivo',
        'fecha_completada',
        'notas',
    ];

    protected $casts = [
        'fecha_objetivo'   => 'date',
        'fecha_completada' => 'date',
    ];

    /**
     * Definición fija de las 5 etapas de implementación del BSC
     * (hoja "Etapas" del Excel). Se usa al sembrar y al abrir un año nuevo.
     */
    public const PLANTILLA = [
        [1, 'Análisis externo e interno / partes interesadas', 'Diagnóstico del entorno (oportunidades y amenazas) y de la organización (fortalezas y debilidades), e identificación de los grupos de interés.'],
        [2, 'Objetivos base (mapa estratégico)', 'Definición de los objetivos estratégicos por perspectiva y sus relaciones causa-efecto.'],
        [3, 'Tablero de control', 'Definición de los indicadores de cada objetivo: nombre, fórmula, fuente de datos, tendencia, frecuencia y meta.'],
        [4, 'Representación gráfica (dashboard)', 'Construcción del tablero visual con gauges, tendencias y semáforos por perspectiva.'],
        [5, 'Planes de acción y seguimiento', 'Definición de planes de acción para los indicadores en riesgo y seguimiento periódico de su avance.'],
    ];

    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function scopeDelAnio($query, int $anio)
    {
        return $query->where('anio', $anio);
    }
}
