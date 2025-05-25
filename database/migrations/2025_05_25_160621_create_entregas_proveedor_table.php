<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('entregas_proveedor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('detalle_id')->constrained('orden_compra_proveedor_detalles')->onDelete('cascade');
            $table->decimal('cantidad_entregada', 10, 2);
            $table->timestamp('fecha_entrega')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->string('observaciones')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the foreign key constraints first
        Schema::table('entregas_proveedor', function (Blueprint $table) {
            $table->dropForeign(['detalle_id']);
        });
        Schema::dropIfExists('entregas_proveedor');
    }
};
