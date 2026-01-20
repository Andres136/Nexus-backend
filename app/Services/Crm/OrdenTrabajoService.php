<?php
// filepath: app/Services/Crm/OrdenTrabajoService.php

namespace App\Services\Crm;

use App\Models\Crm\Orden_Compra;
use App\Models\Crm\OrdenDeTrabajo;
use App\Models\Crm\OrdenTrabajoEntrega;
use App\Models\Crm\product;
use App\Models\Estados;
use App\Models\User;
use App\Models\Departamentos;
use App\Notifications\OrdenTrabajoCreada;
use App\Notifications\OrdenTrabajoGeneradaParaCreador;
use App\Notifications\OrdenTrabajoListaParcial;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class OrdenTrabajoService
{
    public function generarOrdenTrabajo(Orden_Compra $ordenCompra, array $data, $userId)
    {
        return DB::transaction(function () use ($ordenCompra, $data, $userId) {
            
           
            
            // 1. Crear u obtener la orden de trabajo
            $ordenTrabajo = OrdenDeTrabajo::firstOrCreate(
                ['orden_compra_id' => $ordenCompra->id],
                [
                    'cliente_id'    => $ordenCompra->cliente_id,
                    'user_id'       => auth()->user()->id,
                    'fecha_entrega' => Carbon::parse($ordenCompra->fecha_entrega, 'America/Bogota'),
                    'observaciones' => $data['observaciones'] ?? '',
                    'valor_total'   => $ordenCompra->valor_total,
                    'estado_id'     => 1,
                    'fecha_despacho' => null,
                ]
            );

            $fueCreada = $ordenTrabajo->wasRecentlyCreated;

            // 2. Procesar detalles
            $resultado = $this->procesarDetalles($ordenCompra, $ordenTrabajo, $data['detalles'] ?? [], $userId);
            
            // 3. Actualizar orden de trabajo con totales
            $ordenTrabajo->update([
                'faltantes' => $resultado['totalFaltantes'],
                'observaciones' => $data['observaciones'] ?? ''
            ]);

            // 4. Actualizar estados
            $this->actualizarEstados($ordenCompra, $ordenTrabajo, $resultado, $data);

            // 5. Enviar notificaciones
            $this->enviarNotificaciones($ordenCompra, $ordenTrabajo, $resultado['totalFaltantes']);

            // 6. Generar PDF
            $pdfUrl = $this->generarPDF($ordenTrabajo, $ordenCompra);

            return [
                'ordenTrabajo' => $ordenTrabajo,
                'totalFaltantes' => $resultado['totalFaltantes'],
                'ordenCompleta' => $resultado['ordenCompleta'],
                'pdf_url' => $pdfUrl,
                'fueCreada' => $fueCreada
            ];
        });
    }

    private function procesarDetalles($ordenCompra, $ordenTrabajo, $detalles, $userId)
    {
        $ordenCompleta = true;
        $totalFaltantes = 0;

        foreach ($detalles as $detalleData) {
            if (!empty($detalleData['id'])) {
                // Actualizar detalle existente
                $resultado = $this->actualizarDetalle($ordenCompra, $ordenTrabajo, $detalleData, $userId);
            } else {
                // Crear nuevo detalle
                $resultado = $this->crearDetalle($ordenCompra, $ordenTrabajo, $detalleData, $userId);
            }

            if ($resultado['faltantes'] > 0) {
                $ordenCompleta = false;
            }
            $totalFaltantes += $resultado['faltantes'];
        }

        return [
            'ordenCompleta' => $ordenCompleta,
            'totalFaltantes' => $totalFaltantes
        ];
    }

    private function actualizarDetalle($ordenCompra, $ordenTrabajo, $detalleData, $userId)
    {
        $detalle = $ordenCompra->detalles()->find($detalleData['id']);
        if (!$detalle) return ['faltantes' => 0];

        // Calcular cantidades - LÓGICA EXACTA DEL CONTROLLER
        $nuevaCantidadEnviada = (int) ($detalleData['cantidad_enviada'] ?? 0);
        $cantidadAnteriorEnviada = (int) $detalle->cantidad_enviada;
        $cantidadRequerida = (int) ($detalleData['cantidad'] ?? $detalle->cantidad);

        $totalCantidadEnviada = min($cantidadAnteriorEnviada + $nuevaCantidadEnviada, $cantidadRequerida);
        $faltantes = max(0, $cantidadRequerida - $totalCantidadEnviada);

        // PREPARAR CAMPOS EXACTOS DEL CONTROLLER
        $updatedFields = [
            'product_id' => $detalleData['product_id'] ?? $detalle->product_id,
            'largo_cm' => $detalleData['largo_cm'] ?? $detalle->largo_cm,
            'ancho_cm' => $detalleData['ancho_cm'] ?? $detalle->ancho_cm,
            'calibre' => $detalleData['calibre'] ?? $detalle->calibre,
            'cliente_clb' => $detalleData['cliente_clb'] ?? $detalle->cliente_clb,
            'peso_bolsa' => $detalleData['peso_bolsa'] ?? $detalle->peso_bolsa,
            'numero_bolsas' => $detalleData['numero_bolsas'] ?? $detalle->numero_bolsas,
            'cantidad_requerida_kg' => $detalleData['cantidad_requerida_kg'] ?? $detalle->cantidad_requerida_kg,
            'descripcion' => $detalleData['descripcion'] ?? $detalle->descripcion,
            'cantidad' => $cantidadRequerida,
            'observaciones' => $detalleData['observaciones'] ?? $detalle->observaciones,
            'valor_unitario' => $detalleData['valor_unitario'] ?? $detalle->valor_unitario,
            'valor_total' => $detalleData['valor_total'] ?? $detalle->valor_total,
            'cantidad_enviada' => $totalCantidadEnviada,
            'faltantes' => $faltantes,
        ];

        // Actualizar detalle
        $detalle->update($updatedFields);

        // Registrar entrega si hay cantidad nueva
        if ($nuevaCantidadEnviada > 0) {
            $this->registrarEntrega($ordenTrabajo, $detalle, $nuevaCantidadEnviada, $faltantes, $userId, $detalleData);
        }

        return ['faltantes' => $faltantes];
    }

    private function crearDetalle($ordenCompra, $ordenTrabajo, $detalleData, $userId)
    {
        // CREAR DETALLE CON CAMPOS EXACTOS DEL CONTROLLER
        $detalle = $ordenCompra->detalles()->create([
            'product_id' => $detalleData['product_id'] ?? null,
            'largo_cm' => $detalleData['largo_cm'] ?? 0,
            'ancho_cm' => $detalleData['ancho_cm'] ?? 0,
            'calibre' => $detalleData['calibre'] ?? 0,
            'cliente_clb' => $detalleData['cliente_clb'] ?? 0,
            'peso_bolsa' => $detalleData['peso_bolsa'] ?? 0,
            'numero_bolsas' => $detalleData['numero_bolsas'] ?? 0,
            'descripcion' => $detalleData['descripcion'] ?? '',
            'cantidad' => $detalleData['cantidad'] ?? 0,
            'cantidad_requerida_kg' => $detalleData['cantidad_requerida_kg'] ?? 0,
            'valor_unitario' => $detalleData['valor_unitario'] ?? 0,
            'valor_total' => $detalleData['valor_total'] ?? 0,
        ]);

        // Manejo de cantidad_enviada y faltantes - LÓGICA EXACTA
        $nuevaCantidadEnviada = (int) ($detalleData['cantidad_enviada'] ?? 0);
        $cantidadRequerida = (int) ($detalleData['cantidad'] ?? 0);

        $totalCantidadEnviada = min($nuevaCantidadEnviada, $cantidadRequerida);
        $faltantes = max(0, $cantidadRequerida - $totalCantidadEnviada);

        // Actualizar con los valores calculados
        $detalle->update([
            'cantidad_enviada' => $totalCantidadEnviada,
            'faltantes' => $faltantes,
        ]);

        // Registrar entrega si hay cantidad
        if ($nuevaCantidadEnviada > 0) {
            $this->registrarEntrega($ordenTrabajo, $detalle, $nuevaCantidadEnviada, $faltantes, $userId, $detalleData);
        }

        return ['faltantes' => $faltantes];
    }

    private function registrarEntrega($ordenTrabajo, $detalle, $cantidad, $faltantes, $userId, $detalleData)
    {
        OrdenTrabajoEntrega::create([
            'orden_trabajo_id' => $ordenTrabajo->id,
            'detalle_id' => $detalle->id,
            'cantidad' => $cantidad,
            'faltante' => $faltantes,
            'fecha_entrega' => Carbon::now('America/Bogota'),
            'usuario_id' => $userId,
            'observaciones' => $detalleData['observaciones'] ?? '',
        ]);
    }

    private function actualizarEstados($ordenCompra, $ordenTrabajo, $resultado, $data)
    {
        // LÓGICA EXACTA DEL CONTROLLER
        $estadoPendiente = Estados::where('nombre', 'Pendiente')->first()->id;
        $estadoParcial = Estados::where('nombre', 'Entrega Parcial')->first()->id;
        $estadoCompleto = Estados::where('nombre', 'Completado')->first()->id;

        $totalSolicitado = $ordenCompra->detalles()->sum('cantidad');
        $totalEntregado = $ordenCompra->detalles()->sum('cantidad_enviada');
        $forzarParcial = $data['forzar_entrega_parcial'] ?? false;

        if ($resultado['ordenCompleta']) {
            $nuevoEstado = $estadoCompleto;
            if (!$ordenCompra->fecha_despacho) {
                $ordenCompra->fecha_despacho = Carbon::now('America/Bogota');
                $ordenCompra->save();
            }
        } elseif ($forzarParcial || ($totalEntregado > 0 && $totalEntregado < $totalSolicitado)) {
            $nuevoEstado = $estadoParcial;
        } else {
            $nuevoEstado = $estadoPendiente;
        }

        $ordenTrabajo->update(['estado_id' => $nuevoEstado]);
        $ordenCompra->update(['estado_id' => $nuevoEstado]);
    }

    private function enviarNotificaciones($ordenCompra, $ordenTrabajo, $totalFaltantes)
    {
        // SISTEMA EXACTO DEL CONTROLLER
        $operacionesId = Departamentos::where('nombre', 'Operaciones')->value('id');

        // 1. Notificar al usuario que creó la orden de compra
        if ($ordenCompra->user) {
            $ordenCompra->user->notify(new OrdenTrabajoGeneradaParaCreador($ordenTrabajo));
        }

        // 2. Notificar a usuarios de Inventario de la sede específica
        $usuariosInventarioSede = User::where('sede_id', $ordenCompra->sede_id)
            ->where('departamento_id', $operacionesId)
            ->where('role_id', 6)
            ->whereNotNull('email')
            ->get();

        if ($usuariosInventarioSede->count() > 0) {
            Notification::send($usuariosInventarioSede, new OrdenTrabajoCreada($ordenTrabajo));
        }

        // Log para seguimiento
        Log::info('Notificaciones de Orden de Trabajo enviadas', [
            'orden_trabajo_id' => $ordenTrabajo->id,
            'orden_compra_id' => $ordenCompra->id,
            'usuario_creador' => $ordenCompra->user->name ?? 'N/A',
            'inventario_notificados' => $usuariosInventarioSede->count(),
            'sede' => $ordenCompra->sede->nombre ?? 'N/A'
        ]);

        // 3. Notificar si hay productos alistados
        $user = $ordenCompra->user;
        if ($user && $totalFaltantes < $ordenCompra->detalles->sum('cantidad')) {
            $faltantes = $totalFaltantes == 0 ? 0 : $totalFaltantes;
            $user->notify(new OrdenTrabajoListaParcial($ordenTrabajo, $faltantes));
        }
    }

    private function generarPDF($ordenTrabajo, $ordenCompra)
    {
        // GENERACIÓN EXACTA DEL CONTROLLER
        $pdf = Pdf::loadView('pdf.orden_trabajo', [
            'orden' => $ordenTrabajo->load([
                'ordenCompra.detalles.product',
                'ordenCompra.estado',
                'cliente',
                'user',
                'entregas.usuario'
            ]),
            'detalles' => $ordenCompra->detalles,
            'totalKg' => $ordenCompra->detalles->sum('cantidad_requerida_kg'),
            'valorTotal' => $ordenCompra->valor_total,
            'observaciones' => $ordenTrabajo->observaciones ?? 'Sin observaciones',
            'observaciones_oc' => $ordenCompra->observaciones ?? 'Sin observaciones',
        ]);

        $fileName = "ordenes_trabajo/orden_trabajo_{$ordenTrabajo->id}.pdf";
        Storage::disk('public')->put($fileName, $pdf->output());

        $ordenTrabajo->update(['pdf_path' => $fileName]);

        return asset("storage/{$fileName}");
    }


      // ✅ NUEVA FUNCIÓN DE VALIDACIÓN
    private function validarProductos(Orden_Compra $ordenCompra, array $detalles)
    {
        // Verificar si ya existe una OT para esta OC
        $existeOT = OrdenDeTrabajo::where('orden_compra_id', $ordenCompra->id)->exists();
        
        foreach ($detalles as $index => $detalleData) {
            
            // 🔹 Si es actualización de detalle existente, validar solo si viene product_id
            if (!empty($detalleData['id'])) {
                $detalleExistente = $ordenCompra->detalles()->find($detalleData['id']);
                
                if ($detalleExistente) {
                    // Solo validar si viene un nuevo product_id en la actualización
                    if (isset($detalleData['product_id'])) {
                        $this->validarProductoIndividual($detalleData['product_id'], $index, 'actualización');
                    }
                    continue; // Saltar al siguiente detalle
                }
            }
            
            // 🔹 Para detalles nuevos: validar según si existe OT
            if ($existeOT) {
                // Si ya existe OT, product_id es opcional para detalles nuevos
                if (isset($detalleData['product_id'])) {
                    $this->validarProductoIndividual($detalleData['product_id'], $index, 'nuevo con OT existente');
                }
            } else {
                // Si NO existe OT, product_id es OBLIGATORIO para detalles nuevos
                if (empty($detalleData['product_id'])) {
                    throw ValidationException::withMessages([
                        "detalles.{$index}.product_id" => 'El producto es obligatorio para crear la orden de trabajo.'
                    ]);
                }
                
                $this->validarProductoIndividual($detalleData['product_id'], $index, 'nuevo sin OT');
            }
        }
    }

    // ✅ VALIDAR PRODUCTO INDIVIDUAL
    private function validarProductoIndividual($productId, $index, $contexto = '')
    {
        if (empty($productId)) {
            return; // Ya validado en el contexto superior
        }

        // Verificar que el producto existe
        $producto = product::find($productId);
        
        if (!$producto) {
            throw ValidationException::withMessages([
                "detalles.{$index}.product_id" => 'El producto seleccionado no existe.'
            ]);
        }

        // Validar que el producto esté activo (si tienes campo de estado)
        if (isset($producto->activo) && !$producto->activo) {
            throw ValidationException::withMessages([
                "detalles.{$index}.product_id" => 'El producto seleccionado está inactivo.'
            ]);
        }

        // Validar que el producto esté disponible (si tienes campo estado)
        if (isset($producto->estado) && $producto->estado !== 'activo') {
            throw ValidationException::withMessages([
                "detalles.{$index}.product_id" => 'El producto seleccionado no está disponible.'
            ]);
        }
    }
}