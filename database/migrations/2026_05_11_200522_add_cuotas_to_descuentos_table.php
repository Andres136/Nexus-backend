<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('descuentos', function (Blueprint $table) {
              $table->integer('numero_cuotas')
                ->default(1)
                ->after('monto');
                  $table->decimal('valor_cuota', 10, 2)
                ->default(0)
                ->after('numero_cuotas');
                    $table->enum('frecuencia_pago', [
                'quincenal',
                'mensual'
            ])
                ->default('mensual')
                ->after('valor_cuota');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('descuentos', function (Blueprint $table) {
            $table->dropColumn(['numero_cuotas', 'valor_cuota', 'frecuencia_pago']);
        });
    }
};
