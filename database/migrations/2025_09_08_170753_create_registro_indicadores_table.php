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
        Schema::create('registro_indicadores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indicador_id')->constrained('indicadores_procesos')->onDelete('cascade');
            $table->date('fecha');
            $table->decimal('valor', 10, 2);
            $table->text('observaciones')->nullable();
            $table->string('documento')->nullable();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

            Schema::table('registro_indicadores', function (Blueprint $table) {
                $table->dropForeign(['indicador_id']);
                $table->dropForeign(['user_id']);
            });
        Schema::dropIfExists('registro_indicadores');
    }
};
