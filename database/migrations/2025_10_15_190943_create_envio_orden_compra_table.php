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
        Schema::create('envio_orden_compra', function (Blueprint $table) {
            $table->id();
           $table->foreignId('envio_interno_id')
                ->constrained('envios_internos')
                ->cascadeOnDelete();

            $table->foreignId('orden_compra_id')
                ->constrained('orden_compra_proveedores')
                ->cascadeOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('envio_orden_compra');
    }
};
