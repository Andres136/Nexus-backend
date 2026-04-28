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
        Schema::create('hallazgos_seguimiento', function (Blueprint $table) {
            $table->id();

    $table->foreignId('hallazgo_id')
        ->constrained('hallazgo_novedades')
        ->onDelete('cascade');
            $table->foreignId('usuario_id')
        ->constrained('users')
        ->onDelete('cascade');

        $table->text('observacion');

    $table->date('fecha');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hallazgos_seguimiento');
    }
};
