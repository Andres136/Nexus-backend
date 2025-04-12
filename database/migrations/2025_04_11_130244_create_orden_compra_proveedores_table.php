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
        Schema::create('orden_compra_proveedores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proveedor_id')->constrained('proveedores')->onDelete('cascade');
            $table->date('fecha');
            $table->string('numero_orden')->unique();
            $table->string('observaciones');
            $table->foreignId('estado_id')->constrained('estados')->onDelete('cascade');
            $table->foreignId('usuario_id')->constrained('users')->onDelete('cascade');
     
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orden_compra_proveedores', function (Blueprint $table) {
            $table->dropForeign(['proveedor_id']);
            $table->dropForeign(['estado_id']);
            $table->dropForeign(['usuario_id']);

        });
        Schema::table('orden_compra_proveedores', function (Blueprint $table) {
            $table->dropUnique(['numero_orden']);
      
        });
        Schema::dropIfExists('orden_compra_proveedores');
    }
};
