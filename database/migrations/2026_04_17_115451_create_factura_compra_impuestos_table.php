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
        Schema::create('factura_compra_impuestos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factura_compras_id')->constrained('factura_compras')->onDelete('cascade');
            $table->foreignId('impuestos_id')->constrained('impuestos')->onDelete('cascade');
            $table->decimal('monto', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('factura_compra_impuestos');
    }
};
