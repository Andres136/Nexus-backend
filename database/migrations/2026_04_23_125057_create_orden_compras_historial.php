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
        Schema::create('orden_compras_historial', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_compra_id')->constrained('orden__compras')->onDelete('cascade');
            $table->date('fecha_anterior');
            $table->date('fecha_nueva');
            $table->text('observacion')->nullable();
            $table->foreignId('usuario_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orden_compras_historial');
    }
};
