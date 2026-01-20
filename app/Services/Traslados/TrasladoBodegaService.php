<?php
namespace App\Services\Traslados;

use App\Events\Traslados\TrasladoAprobadorPorBodega;
use App\Events\Traslados\TrasladoCreado;
use App\Models\Crm\Inventario;
use App\Models\Crm\MovimientoStock;
use App\Models\Traslados\Responsabilidad;
use App\Models\Traslados\Traslado_Bodega;
use Exception;
use Illuminate\Support\Facades\DB;
use Symfony\Contracts\EventDispatcher\Event;

class TrasladoBodegaService
{

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
                'empresa_id' => $traslado->empresa_id,
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

    /**
     * Despachar traslado (impacta inventario)
     */
  public function despachar(int $trasladoId): Traslado_Bodega
{
    return DB::transaction(function () use ($trasladoId) {

        $traslado = Traslado_Bodega::with('detalles.producto')
            ->lockForUpdate()
            ->findOrFail($trasladoId);

        // 🔴 VALIDACIÓN CLAVE
        if ($traslado->estado !== 'APROBADO_INVENTARIO') {
            throw new Exception('El traslado debe estar aprobado por inventario antes de despachar');
        }

        // 🔒 Validar stock en el último momento
        $this->validarStock($traslado);

        foreach ($traslado->detalles as $item) {

            MovimientoStock::create([
                'empresa_id' => $traslado->empresa_id,
                'producto_id' => $item->producto_id,
                'bodega_id' => $traslado->bodega_origen_id,
                'tipo' => 'SALIDA',
                'cantidad' => $item->cantidad,
                'origen_tipo' => 'TRASLADO',
                'origen_id' => $traslado->id,
                'usuario_id' => auth()->id(),
            ]);

            MovimientoStock::create([
                'empresa_id' => $traslado->empresa_id,
                'producto_id' => $item->producto_id,
                'bodega_id' => $traslado->bodega_destino_id,
                'tipo' => 'ENTRADA',
                'cantidad' => $item->cantidad,
                'origen_tipo' => 'TRASLADO',
                'origen_id' => $traslado->id,
                'usuario_id' => auth()->id(),
            ]);
        }

        $traslado->update([
            'estado' => 'DESPACHADO',
            'fecha_despacho' => now(),
        ]);

        return $traslado->fresh();
    });
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

        if ($traslado->estado !== 'PENDIENTE_INVENTARIO') {
            throw new Exception('El traslado no está pendiente de aprobación');
        }

        // 🔐 Validar que el usuario sea responsable de inventario
        $this->validarResponsableInventario($traslado);

        $traslado->update([
            'estado' => 'APROBADO_INVENTARIO',
            'usuario_aprobador_inventario_id' => auth()->id(),
            'fecha_aprobacion' => now(),
        ]);

        return $traslado->fresh();
    });
}


private function validarResponsableInventario(Traslado_Bodega $traslado): void
{
    $esResponsable = auth()->user()
        ->responsabilidades()
        ->where('id', Responsabilidad::INVENTARIO)
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

        if ($traslado->estado !== 'PENDIENTE_BODEGA') {
            throw new Exception('El traslado no está pendiente de aprobación por bodega');
        }

        if ($aprueba) {
            $traslado->update([
                'estado' => 'PENDIENTE_INVENTARIO',
                'usuario_aprobador_bodega_id' => auth()->id(),
            ]);

            event(new TrasladoAprobadorPorBodega($traslado));

        } else {
            $traslado->update([
                'estado' => 'RECHAZADO_BODEGA',
                'observaciones' => $motivo,
                'usuario_aprobador_bodega_id' => auth()->id(),
            ]);
        }

        return $traslado->fresh();
    });
}

}
