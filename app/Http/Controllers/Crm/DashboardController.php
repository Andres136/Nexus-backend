<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\Crm\Cliente;
use App\Models\Crm\Orden_Compra;
use App\Models\Crm\OrdenDeTrabajo;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function getDashboardData()
    {
        $ordenes = Orden_Compra::with('ordenTrabajo', 'detalles', 'cliente', 'creador')->get();
        $hoy = now()->format('Y-m-d');
    
        // Paso 1: Mapear cada orden a un estado único
        $ordenesConEstado = $ordenes->map(function ($orden) {
            $tieneFaltantes = $orden->detalles->sum('faltantes') > 0;
            $tieneEnviados  = $orden->detalles->sum('cantidad_enviada') > 0;
            $fechaVencida   = now()->gt($orden->fecha_entrega);
            $tieneOT        = $orden->ordenTrabajo !== null;
    
            if ($fechaVencida) {
                $estado = 'Vencida';
            } elseif ($tieneFaltantes && $tieneEnviados) {
                $estado = 'Con faltantes';
            } elseif ($tieneEnviados && !$tieneFaltantes) {
                $estado = 'Lista';
            } elseif ($tieneOT) {
                $estado = 'En orden trabajo';
            } else {
                $estado = 'Registrada';
            }
    
            return [
                'id'             => $orden->id,
                'estado'         => $estado,
                'cliente'        => optional($orden->cliente)->nombre,
                'usuario'        => optional($orden->creador)->name,
                'fecha_entrega'  => $orden->fecha_entrega,
            ];
        });
    
        // Paso 2: Agrupar por estado
        $agrupadoPorEstado = $ordenesConEstado->groupBy('estado');
        $porCliente = $ordenesConEstado->groupBy('cliente')->map->count();
        $porUsuario = $ordenesConEstado->groupBy('usuario')->map->count();
    
        // Paso 3: Calcular órdenes listas recientes (últimos 5 días)
        $listasRecientes = $agrupadoPorEstado->get('Lista', collect())
            ->filter(function ($orden) use ($ordenes) {
                $original = $ordenes->firstWhere('id', $orden['id']);
                return $original?->ordenTrabajo?->updated_at > now()->subDays(5);
            });
    
        // Paso 4: Retornar datos para el dashboard
        return response()->json([
            'total'              => $ordenes->count(),
            'registradas'        => $agrupadoPorEstado->get('Registrada', collect())->count(),
            'en_orden_trabajo'   => $agrupadoPorEstado->get('En orden trabajo', collect())->count(),
            'listas'             => $listasRecientes->count(), // Solo listas recientes
            'faltantes'          => $agrupadoPorEstado->get('Con faltantes', collect())->count(),
            'vencidas'           => $agrupadoPorEstado->get('Vencida', collect())->count(),
            'hoy'                => $ordenesConEstado->filter(fn($o) => $o['fecha_entrega'] == $hoy)->count(),
    
            // Para tooltips o vistas por estado
            'en_orden_trabajo_detalle' => $agrupadoPorEstado->get('En orden trabajo', collect())->pluck('cliente')->values(),
            'listas_detalle'           => $listasRecientes->pluck('cliente')->values(), // Solo recientes
            'faltantes_detalle'        => $agrupadoPorEstado->get('Con faltantes', collect())->pluck('cliente')->values(),
            'vencidas_detalle'         => $agrupadoPorEstado->get('Vencida', collect())->pluck('cliente')->values(),
    
            'por_cliente' => $porCliente,
            'por_usuario' => $porUsuario,
        ]);
    }
    




}
