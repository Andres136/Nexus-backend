<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nomina', function (Blueprint $table) {
            $table->text('motivo_reversion')->nullable()->after('fecha_cierre_contable');
            $table->foreignId('reversado_por')->nullable()->after('motivo_reversion')->constrained('users');
            $table->timestamp('fecha_reversion')->nullable()->after('reversado_por');
            $table->string('estado_contable_anterior', 30)->nullable()->after('fecha_reversion');
            $table->json('detalle_reversion')->nullable()->after('estado_contable_anterior');
        });
    }

    public function down(): void
    {
        Schema::table('nomina', function (Blueprint $table) {
            $table->dropForeign(['reversado_por']);
            $table->dropColumn([
                'motivo_reversion',
                'reversado_por',
                'fecha_reversion',
                'estado_contable_anterior',
                'detalle_reversion',
            ]);
        });
    }
};
