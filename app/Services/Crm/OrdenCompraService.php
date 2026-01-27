<?php

namespace App\Services\Crm;

use App\Models\Crm\Orden_Compra;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class OrdenCompraService
{
  public function obtenerDocumentoPreview(Orden_Compra $orden)
{
       $rutaArchivo = $orden->cliente_documento;
    //dd($rutaArchivo);
        if (!$rutaArchivo) {
            abort(404, 'Documento no disponible');
        }

        $disk = Storage::disk('public');

        if (!$disk->exists($rutaArchivo)) {
            abort(404, 'Archivo no encontrado');
        }

        $contenido = $disk->get($rutaArchivo);
        $mimeType = $disk->mimeType($rutaArchivo);

        return response($contenido, 200)
            ->header('Content-Type', $mimeType)
            ->header('Content-Disposition', 'inline')
            ->header('X-Content-Type-Options', 'nosniff');
}

public function procesar(LengthAwarePaginator $ordenes, User $user): LengthAwarePaginator
    {
        $ordenes->getCollection()->transform(function ($orden) use ($user) {

            $detalles = $orden->detalles->map(function ($detalle) use ($user) {

                // 🔹 Cantidad entregada según vista del usuario
                if (!in_array($user->role_id, [1, 2, 4])) {
                    $cantidadEntregadaVista = $detalle->entregas->sum('cantidad_entregada');
                } else {
                    if ($user->sede_id) {
                        $cantidadEntregadaVista = $detalle->entregas
                            ->where('sede_id', $user->sede_id)
                            ->sum('cantidad_entregada');

                        // Fallback órdenes viejas
                        if (
                            $cantidadEntregadaVista == 0 &&
                            $detalle->entregas->whereNull('sede_id')->count() > 0
                        ) {
                            $cantidadEntregadaVista = $detalle->cantidad_entregada;
                        }
                    } else {
                        $cantidadEntregadaVista = $detalle->cantidad_entregada;
                    }
                }

                // 🔹 Estado del producto
                $estado = 'Pendiente';

                if ($cantidadEntregadaVista >= $detalle->cantidad_solicitada) {
                    $estado = $cantidadEntregadaVista > $detalle->cantidad_solicitada
                        ? 'Con entrega extra'
                        : 'Completo';
                }

                return [
                    'estado_producto' => $estado,
                    'cantidad_entregada_vista' => $cantidadEntregadaVista,
                    'cantidad_solicitada' => $detalle->cantidad_solicitada,
                ];
            });

            $total = $detalles->count();
            $completados = $detalles
                ->whereIn('estado_producto', ['Completo', 'Con entrega extra'])
                ->count();

            // 🔹 Estado de la orden
            $orden->estado_calculado = match (true) {
                $completados === 0 => 'Pendiente',
                $completados < $total => 'Parcialmente Entregada',
                default => 'Completada',
            };

            $orden->sede_nombre = $orden->sede->nombre ?? 'Sin sede';

            $orden->estadisticas_detalle = [
                'total_items' => $total,
                'items_completos' => $completados,
                'items_pendientes' => $total - $completados,
                'porcentaje_completado' => $total > 0
                    ? round(($completados / $total) * 100, 2)
                    : 0,
            ];

            $orden->vista_filtrada_por_sede =
                !in_array($user->role_id, [1, 2, 4]) ||
                (in_array($user->role_id, [1, 2, 4]) && $user->sede_id);

            return $orden;
        });

        return $ordenes;
    }


}
