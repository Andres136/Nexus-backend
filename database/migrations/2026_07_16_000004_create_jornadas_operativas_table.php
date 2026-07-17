<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jornadas_operativas', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('work_session_id')->nullable()->constrained('work_sessions')->nullOnDelete();
            $table->date('fecha');
            $table->string('estado_actual')->default('DISPONIBLE');
            $table->dateTime('iniciada_at')->nullable();
            $table->dateTime('finalizada_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'fecha']);
            $table->index(['user_id', 'estado_actual']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jornadas_operativas');
    }
};
