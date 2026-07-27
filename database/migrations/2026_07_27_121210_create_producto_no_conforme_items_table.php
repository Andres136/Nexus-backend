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
        Schema::create('producto_no_conforme_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_no_conforme_id')->constrained('productos_no_conformes')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('products');
            $table->integer('cantidad_afectada')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('producto_no_conforme_items');
    }
};
