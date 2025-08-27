<?php

namespace App\Services;

use App\Models\Crm\Orden_Compra;
use Carbon\Carbon;

class EmailPersonalizationService
{
    /**
     * Generar saludo personalizado según la hora
     */
    public static function getSaludo(): string
    {
         $hora = \Carbon\Carbon::now('America/Bogota')->hour;

    if ($hora >= 6 && $hora < 12)  return '🌅 Buenos días';
    if ($hora >= 12 && $hora < 18) return '☀️ Buenas tardes';
    return '🌙 Buenas noches';
    }

    /**
     * Determinar el color y tema según la prioridad
     */
    public static function getThemeByPriority(string $prioridad): array
    {
        return match($prioridad) {
            'alta' => [
                'color' => '#e74c3c',
                'icon' => '🚨',
                'class' => 'urgent',
                'mensaje' => 'Requiere atención inmediata'
            ],
            'media' => [
                'color' => '#f39c12',
                'icon' => '⚠️',
                'class' => 'medium',
                'mensaje' => 'Seguimiento requerido'
            ],
            'baja' => [
                'color' => '#27ae60',
                'icon' => '✅',
                'class' => 'low',
                'mensaje' => 'Para revisión'
            ],
            default => [
                'color' => '#3498db',
                'icon' => '📋',
                'class' => 'normal',
                'mensaje' => 'Nueva orden creada'
            ]
        };
    }

    /**
     * Generar estadísticas del mes para incluir en emails
     */
    public static function getEstadisticasMensuales(): array
    {
        $inicioMes = Carbon::now()->startOfMonth();
        $ordenes = Orden_Compra::where('created_at', '>=', $inicioMes)->get();
        
        return [
            'total_ordenes' => $ordenes->count(),
            'valor_total' => $ordenes->sum('valor_total'),
            'ordenes_pendientes' => $ordenes->where('estado', 'pendiente')->count(),
            'ordenes_completadas' => $ordenes->where('estado', 'completada')->count(),
        ];
    }

    /**
     * Generar mensaje contextual según el departamento del usuario
     */
    public static function getMensajePorDepartamento($usuario): string
    {
        $departamento = $usuario->departamento_id ?? null;
        
        return match($departamento) {
            4 => 'Como miembro del equipo de Operaciones, tu atención es crucial para el procesamiento de esta orden.',
            5 => 'Tu experiencia en Producción es clave para coordinar los recursos necesarios.',
            6 => 'Desde Logística, tu coordinación asegurará la entrega oportuna.',
            default => 'Tu participación es importante para el éxito de esta orden.'
        };
    }

    /**
     * Generar recomendaciones inteligentes basadas en datos históricos
     */
    public static function getRecomendaciones(Orden_Compra $orden): array
    {
        $recomendaciones = [];
        
        // Análisis temporal
        $fechaEntrega = Carbon::parse($orden->fecha_entrega);
        $diasRestantes = $fechaEntrega->diffInDays(Carbon::now());
        
        if ($diasRestantes <= 3) {
            $recomendaciones[] = '🕒 Coordinar recursos urgentemente debido a la proximidad de la fecha de entrega';
        }
        
        // Análisis de valor
        if ($orden->valor_total > 5000000) {
            $recomendaciones[] = '💰 Orden de alto valor - Considerar asignación de personal especializado';
        }
        
        // Análisis del cliente (si existe histórico)
        $ordenesAnteriores = Orden_Compra::where('cliente_id', $orden->cliente_id)
                                       ->where('id', '!=', $orden->id)
                                       ->count();
        
        if ($ordenesAnteriores > 5) {
            $recomendaciones[] = '🤝 Cliente frecuente - Mantener estándares de calidad elevados';
        } elseif ($ordenesAnteriores == 0) {
            $recomendaciones[] = '🆕 Cliente nuevo - Asegurar excelente primera impresión';
        }
        
        // Día de la semana
        if (Carbon::now()->isFriday()) {
            $recomendaciones[] = '📅 Coordinar entregas para evitar retrasos de fin de semana';
        }
        
        return $recomendaciones;
    }

    /**
     * Formatear moneda colombiana
     */
    public static function formatearMoneda($valor): string
    {
        return '$' . number_format($valor, 0, ',', '.') . ' COP';
    }

    /**
     * Generar signature personalizada
     */
    public static function getSignature(): string
    {
        return "
        <div style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;'>
            <p style='color: #666; font-size: 12px; line-height: 1.5;'>
                <strong>📧 Sistema Automatizado de Notificaciones</strong><br>
                " . config('app.name') . " - Gestión Integral<br>
                📞 Soporte: +57 (1) XXX-XXXX | 📧 soporte@setasplast.com
            </p>
        </div>";
    }
}
