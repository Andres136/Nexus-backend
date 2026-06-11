<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\EstadoEnum;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('factura_compras', function (Blueprint $table) {
            $table->foreignId('forma_pago_id')
                ->nullable()
                ->after('estado_id')
                ->constrained('formas_pago')
                ->nullOnDelete();
            $table->timestamp('fecha_anulacion')->nullable()->after('fecha_vencimiento');
        });

        collect([
            EstadoEnum::PENDIENTE,
            EstadoEnum::PAGADA,
            EstadoEnum::PAGO_PARCIAL,
            EstadoEnum::ANULADA,
        ])->each(function (EstadoEnum $estado) {
            DB::table('estados')->updateOrInsert(
                ['id' => $estado->value],
                [
                    'nombre' => $estado->nombre(),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        });

        DB::table('factura_compras')
            ->orderBy('id')
            ->each(function ($factura) {
                $pagos = DB::table('factura_pagos')
                    ->where('factura_compras_id', $factura->id);

                $totalPagado = (float) (clone $pagos)->sum('monto');
                $formaPagoId = (clone $pagos)->orderBy('id')->value('forma_pago_id');
                $total = (float) $factura->total;
                $saldoPendiente = max($total - $totalPagado, 0);

                if (!empty($factura->fecha_anulacion)) {
                    $estadoId = EstadoEnum::ANULADA->value;
                    $saldoPendiente = 0;
                } elseif ($total > 0 && $totalPagado >= $total) {
                    $estadoId = EstadoEnum::PAGADA->value;
                } elseif ($totalPagado > 0) {
                    $estadoId = EstadoEnum::PAGO_PARCIAL->value;
                } else {
                    $estadoId = EstadoEnum::PENDIENTE->value;
                }

                DB::table('factura_compras')
                    ->where('id', $factura->id)
                    ->update([
                        'forma_pago_id' => $formaPagoId,
                        'estado_id' => $estadoId,
                        'saldo_pendiente' => $saldoPendiente,
                    ]);
            });

        // Los registros en cero solo se usaban para guardar la forma de pago.
        // Ahora la forma de pago vive directamente en factura_compras.
        DB::table('factura_pagos')->where('monto', '<=', 0)->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('factura_compras', function (Blueprint $table) {
            $table->dropConstrainedForeignId('forma_pago_id');
            $table->dropColumn('fecha_anulacion');
        });
    }
};
