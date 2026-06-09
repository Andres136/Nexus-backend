<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comisiones', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users');
            $table->date('periodo_inicio');
            $table->date('periodo_fin');
            $table->string('concepto');
            $table->decimal('valor', 14, 2);
            $table->enum('status', ['pendiente', 'aprobada', 'rechazada', 'aplicada'])->default('pendiente');
            $table->text('observacion')->nullable();
            $table->foreignId('registrado_por')->nullable()->constrained('users');
            $table->foreignId('autorizado_por')->nullable()->constrained('users');
            $table->timestamp('fecha_gestion')->nullable();
            $table->string('observacion_gestion')->nullable();
            $table->foreignId('nomina_id')->nullable()->constrained('nomina');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status', 'periodo_inicio', 'periodo_fin']);
        });

        Schema::table('nomina', function (Blueprint $table) {
            $table->decimal('total_comisiones', 14, 2)->default(0)->after('auxilio_transporte');
        });
    }

    public function down(): void
    {
        Schema::table('nomina', function (Blueprint $table) {
            $table->dropColumn('total_comisiones');
        });

        Schema::dropIfExists('comisiones');
    }
};
