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
        Schema::create('errores', function (Blueprint $table) {
            $table->id();
            $table->text('descripcion');
            $table->foreignId('proceso_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('errores', function (Blueprint $table) {
            $table->dropForeign(['proceso_id']);
            $table->dropColumn(['proceso_id', 'descripcion']);
        });
    }
};
