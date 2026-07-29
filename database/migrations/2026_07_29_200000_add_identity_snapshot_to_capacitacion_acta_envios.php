<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('capacitacion_acta_envios', function (Blueprint $table) {
            $table->string('usuario_apellidos')->nullable()->after('empresa_nombre');
            $table->string('numero_documento', 80)->nullable()->after('usuario_apellidos');
        });
    }

    public function down(): void
    {
        Schema::table('capacitacion_acta_envios', function (Blueprint $table) {
            $table->dropColumn(['usuario_apellidos', 'numero_documento']);
        });
    }
};
