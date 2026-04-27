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
        Schema::create('detalle_factura_impuestos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('detalle_factura_id')->constrained('detalles_factura_compra')->onDelete('cascade');
            $table->foreignId('impuesto_id')->constrained('impuestos')->onDelete('restrict');
            $table->decimal('monto', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_factura_impuestos');

    }
};
