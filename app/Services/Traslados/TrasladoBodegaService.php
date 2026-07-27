<?php

namespace App\Services\Traslados;

use App\Events\Traslados\TrasladoActualizado;
use App\Events\Traslados\TrasladoAprobadorPorBodega;
use App\Events\Traslados\TrasladoCreado;
use App\Exceptions\Traslados\EstadoTrasladoInvalidoException;
use App\Exceptions\Traslados\MovimientoInventarioInvalidoException;
use App\Models\Crm\Inventario;
use App\Models\Crm\MovimientoStock;
use App\Models\Traslados\Responsabilidad;
use App\Models\Traslados\Traslado_Bodega;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Contracts\EventDispatcher\Event;

class TrasladoBodegaService
{


    /**
     * Listar traslados de bodega CON FILTROS DE BUSQUEDA Y PAGINACION
     */

public function listar(array $filters = [])
    {
        $query = Traslado_Bodega::with([
            'bodegaOrigen',
            'bodegaDestino',
            'creador',
            'aprobadorBodega',
            'aprobadorInventario',
            'detalles.producto',
        ]);

        // Filtros de búsqueda
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('codigo', 'like', "%{$search}%")
                  ->orWhere('estado', 'like', "%{$search}%");
            });
        }

        // Ordenamiento
        $orderBy = $filters['order_by'] ?? 'created_at';
        $order = $filters['order'] ?? 'desc';
        $query->orderBy($orderBy, $order);

        // Paginación
        $perPage = $filters['per_page'] ?? 10;
        return $query->paginate($perPage);
    }
    /**
     * Crear traslado (BORRADOR)
     */
    public function crear(array $data): Traslado_Bodega
    {
        return DB::transaction(function () use ($data) {

            $traslado = Traslado_Bodega::create([
                'codigo' => $this->generarCodigo(),
                'bodega_origen_id' => $data['bodega_origen_id'],
                'bodega_destino_id' => $data['bodega_destino_id'],
                'usuario_creador_id' => auth()->id(),
                //  'usuario_aprobador_id' => $data['usuario_aprobador_id'],
                'estado' => 'PENDIENTE_BODEGA',
                'observaciones' => $data['observaciones'] ?? null,
            ]);

            foreach ($data['detalles'] as $item) {
                $traslado->detalles()->create([
                    'producto_id' => $item['producto_id'],
                    'cantidad' => $item['cantidad'],

                ]);
            }

            event(new TrasladoCreado($traslado));
            return $traslado->load('detalles');
        });
    }

    /**
     * Validar stock antes del despacho
     */
    public function validarStock(Traslado_Bodega $traslado): void
    {
        foreach ($traslado->detalles as $item) {

            $inventario = Inventario::where([
              //  'empresa_id' => $traslado->empresa_id,
                'bodega_id' => $traslado->bodega_origen_id,
                'producto_id' => $item->producto_id,
            ])->lockForUpdate()->first();

            if (!$inventario || $inventario->stock < $item->cantidad) {
                throw new Exception(
                    "Stock insuficiente para el producto {$item->producto->name}"
                );
            }
        }
    }



    private function generarCodigo(): string
    {
        $year = now()->year;

        $ultimo = Traslado_Bodega::whereYear('created_at', $year)
            ->latest('id')
            ->first();

        $seq = $ultimo
            ? intval(substr($ultimo->codigo, -4)) + 1
            : 1;

        return 'TR-' . $year . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }


    public function aprobar(int $trasladoId): Traslado_Bodega
    {
        return DB::transaction(function () use ($trasladoId) {

            $traslado = Traslado_Bodega::lockForUpdate()->findOrFail($trasladoId);

            $this->asegurarMovimientoInventarioPermitido($traslado, 'PENDIENTE_INVENTARIO');

            // 🔐 Validar que el usuario sea responsable de inventario
            $this->validarResponsableInventario($traslado);
            logger()->info('ENTRÓ aprobarPorBodega', [
                'traslado_id' => $trasladoId,
                'user' => auth()->id()
            ]);

            $traslado->update([
                'estado' => 'APROBADO',
                'usuario_aprobador_inventario_id' => auth()->id(),
                'fecha_recepcion' => now(),
            ]);
      
            $this->generarMovimientoStockTraslado($traslado);
            return $traslado->fresh();


        });
    }


    private function validarResponsableInventario(Traslado_Bodega $traslado): void
    {
        $idInventario = Responsabilidad::where('codigo', 'inventario')->value('id');
        $esResponsable = auth()->user()
            ->responsabilidades()
            ->where('responsabilidad_id', $idInventario)
            ->wherePivot('activo', true)
            ->exists();

        if (!$esResponsable) {
            throw new Exception('No tiene autorización para aprobar este traslado');
        }
    }



    public function aprobarPorBodega(int $trasladoId, bool $aprueba, ?string $motivo = null)
    {
        return DB::transaction(function () use ($trasladoId, $aprueba, $motivo) {

            $traslado = Traslado_Bodega::lockForUpdate()->findOrFail($trasladoId);

            $this->asegurarEstado($traslado, 'PENDIENTE_BODEGA');

            if ($aprueba) {
                $traslado->update([
                    'estado' => 'PENDIENTE_INVENTARIO',
                    'usuario_aprobador_bodega_id' => auth()->id(),
                    'fecha_despacho' => now(),

                ]);

                event(new TrasladoAprobadorPorBodega($traslado));
            } else {
                $traslado->update([
                    'estado' => 'RECHAZADO_BODEGA',
                    'observaciones' => $motivo,
                    'usuario_aprobador_bodega_id' => auth()->id(),
                    'fecha_despacho' => now(),


                ]);
            }


            return $traslado->fresh();
        });
    }


    //RECHAZAR POR BODEGA
    public function rechazarPorBodega(int $trasladoId, string $motivo): Traslado_Bodega
    {
        return DB::transaction(function () use ($trasladoId, $motivo) {

            $traslado = Traslado_Bodega::lockForUpdate()->findOrFail($trasladoId);

            $this->asegurarEstado($traslado, 'PENDIENTE_BODEGA');

            $traslado->update([
                'estado' => 'RECHAZADO_BODEGA',
                'observaciones' => $motivo,
                'usuario_aprobador_bodega_id' => auth()->id(),
                'fecha_despacho' => now(),
            ]);

            return $traslado->fresh();
        });
    }

 private function generarMovimientoStockTraslado(Traslado_Bodega $traslado): void
{
    $traslado = $traslado->fresh([
        'bodegaOrigen',
        'bodegaDestino',
        'detalles.producto',
    ]);

    $pdfPath = $this->generarPDFTraslado(
        $traslado,
        $traslado->detalles->map(function ($item) {
            return [
                'producto' => $item->producto->name ?? '',
                'cantidad' => $item->cantidad,
                'producto_id' => $item->producto_id,
            ];
        })->toArray()
    );

    $totalTraslado = 0;
    $empresaId = null;

    foreach ($traslado->detalles as $item) {

        $inventariosOrigen = Inventario::where([
                'producto_id' => $item->producto_id,
                'bodega_id'   => $traslado->bodega_origen_id,
            ])
            ->where('stock', '>', 0)
            ->lockForUpdate()
            ->orderBy('stock', 'desc')
            ->get();

        if ($inventariosOrigen->sum('stock') < $item->cantidad) {
            throw new Exception(
                'Stock insuficiente para el producto ' . $item->producto->name
            );
        }

        $empresaId = $inventariosOrigen->first()->empresa_id;
        $totalTraslado += $item->cantidad;

        $cantidadPendiente = $item->cantidad;

        // 🔻 DESCONTAR ORIGEN
        foreach ($inventariosOrigen as $inventarioOrigen) {
            if ($cantidadPendiente <= 0) break;

            $cantidadADescontar = min($cantidadPendiente, $inventarioOrigen->stock);
            $inventarioOrigen->decrement('stock', $cantidadADescontar);
            $cantidadPendiente -= $cantidadADescontar;
        }

        // 🔵 SUMAR DESTINO
        $inventarioDestino = Inventario::where([
                'empresa_id'  => $empresaId,
                'producto_id' => $item->producto_id,
                'bodega_id'   => $traslado->bodega_destino_id,
            ])
            ->lockForUpdate()
            ->first();

        if (!$inventarioDestino) {
            $inventarioDestino = Inventario::create([
                'empresa_id'  => $empresaId,
                'producto_id' => $item->producto_id,
                'bodega_id'   => $traslado->bodega_destino_id,
                'sede_id'     => $traslado->bodegaDestino->sede_id, // 🔥 IMPORTANTE
                'user_id'     => auth()->id(),
                'stock'       => 0,
                'min_stock'   => 0,
                'max_stock'   => 0,
            ]);
        }

        $inventarioDestino->increment('stock', $item->cantidad);
    }

    // 🔻 Movimiento único salida
    MovimientoStock::create([
        'empresa_id'   => $empresaId,
        'bodega_id'    => $traslado->bodega_origen_id,
        'tipo'         => 'SALIDA_BODEGA',
        'cantidad'     => $totalTraslado,
        'usuario_id'   => auth()->id(),
        'pdf_path'     => $pdfPath,
        'observaciones'=> "Salida por traslado {$traslado->codigo}",
    ]);

    // 🔵 Movimiento único entrada
    MovimientoStock::create([
        'empresa_id'   => $empresaId,
        'bodega_id'    => $traslado->bodega_destino_id,
        'tipo'         => 'ENTRADA_BODEGA',
        'cantidad'     => $totalTraslado,
        'usuario_id'   => auth()->id(),
        'pdf_path'     => $pdfPath,
        'observaciones'=> "Entrada por traslado {$traslado->codigo}",
    ]);

    $traslado->update([
        'estado' => 'DESPACHADO',
        'pdf_path' => $pdfPath,
        'fecha_despacho' => now(),
    ]);
}

    private function generarPDFTraslado(Traslado_Bodega $traslado, array $detalle): string
    {
        $data = [
            'traslado' => $traslado,
            'detalle'  => $detalle,
            'fecha'    => now()->format('d/m/Y H:i'),
            'usuario'  => auth()->user(),
      
        ];

        $pdf = Pdf::loadView('pdf.traslado', $data)
            ->setPaper('A4');

        $filename = 'TR_' . $traslado->codigo . '_' . now()->format('Ymd_His') . '.pdf';
        $path = 'movimientos/' . $filename;

        Storage::disk('public')->put($path, $pdf->output());

        return $path;
    }

    private function asegurarEstado(
        Traslado_Bodega $traslado,
        string $estadoEsperado
    ): void {
        if ($traslado->estado !== $estadoEsperado) {
            throw new EstadoTrasladoInvalidoException(
                "Acción no permitida. Estado actual: {$traslado->estado}"
            );
        }
    }
    private function asegurarMovimientoInventarioPermitido(Traslado_Bodega $traslado, string $estadoEsperado): void
    {
        if ($traslado->estado !== $estadoEsperado) {
            throw new MovimientoInventarioInvalidoException(
                "No se puede generar movimiento de inventario. Estado actual: {$traslado->estado}"
            );
        }
    }

    public function getById(int $id): ?Traslado_Bodega
    {
        return Traslado_Bodega::with([    'bodegaOrigen:id,nombre',
        'bodegaDestino:id,nombre','detalles','detalles.producto'])->find($id);
    }

    public function update(int $id, array $data): ?Traslado_Bodega
    {
        return DB::transaction(function () use ($id, $data) {

            $traslado = Traslado_Bodega::with('detalles')->find($id);
            if (!$traslado) {
                return null;
            }

            // Actualizar campos principales
            $traslado->update([
                'bodega_origen_id' => $data['bodega_origen_id'],
                'bodega_destino_id' => $data['bodega_destino_id'],
                'observaciones' => $data['observaciones'] ?? null,
            ]);

            // Actualizar detalles
            $traslado->detalles()->delete();
            foreach ($data['detalles'] as $item) {
                $traslado->detalles()->create([
                    'producto_id' => $item['producto_id'],
                    'cantidad' => $item['cantidad'],
                ]);
            }
          event(new TrasladoActualizado(
    $traslado,
    auth()->id()
));
            return $traslado->fresh('detalles');
        });
  
    }
}
