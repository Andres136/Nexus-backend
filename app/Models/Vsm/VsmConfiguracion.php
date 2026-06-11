<?php

namespace App\Models\Vsm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class VsmConfiguracion extends Model
{
    protected $table = 'vsm_configuracion';

    protected $fillable = [
        'meta_unidades_hora',
        'horas_semanales',
        'descripcion',
        'activo',
        'creado_por',
    ];

    protected $casts = [
        'meta_unidades_hora' => 'float',
        'horas_semanales'    => 'float',
        'activo'             => 'boolean',
    ];

    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    /**
     * Devuelve el registro activo vigente.
     * Lanza ModelNotFoundException si no existe ninguno.
     */
    public static function vigente(): self
    {
        return static::where('activo', true)->latest()->firstOrFail();
    }

    /**
     * Devuelve la meta vigente o un fallback si aún no hay configuración.
     */
    public static function metaVigente(float $fallback = 705.88): float
    {
        $config = static::where('activo', true)->latest()->first();

        return $config?->meta_unidades_hora ?? $fallback;
    }

    /**
     * Retorna el objeto de configuración con las metas calculadas para cada período.
     */
    public static function metas(): array
    {
        $config = static::where('activo', true)->latest()->first();

        $metaHora       = $config?->meta_unidades_hora ?? 705.88;
        $horasSemanales = $config?->horas_semanales    ?? 44;
        $horasDiarias   = $horasSemanales / 5;

        return [
            'meta_hora'       => round($metaHora, 2),
            'horas_diarias'   => round($horasDiarias, 2),
            'horas_semanales' => $horasSemanales,
            'meta_diaria'     => round($metaHora * $horasDiarias, 2),
            'meta_semanal'    => round($metaHora * $horasSemanales, 2),
            'meta_mensual'    => round($metaHora * $horasSemanales * (52 / 12), 2),
        ];
    }
}
