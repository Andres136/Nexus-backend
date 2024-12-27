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
        Schema::create('documentos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('documento');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('proceso_id')->constrained()->onDelete('cascade');
            $table->integer('version');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documentos', function (Blueprint $table) {
            $table->dropForeign(['proceso_id']);
            $table->dropForeign(['usuario_id']);
            $table->dropColumn(['proceso_id', 'user_id','documento', 'nombre', 'version']);
        });
    }
};
