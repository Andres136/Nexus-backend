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
        Schema::create('incapacidades', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('tipo_incapacidad', 45);
            $table->foreignId('identidad_medica_id')->constrained('seguridad_socials');
            $table->date('inicio');
            $table->date('fin');
            $table->string('soporte', 45);
            $table->boolean('status')->default(true);
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('user_reviso_id')->constrained('users');
            $table->timestamps();
            $table->softDeletes(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incapacidades');
    }
};
