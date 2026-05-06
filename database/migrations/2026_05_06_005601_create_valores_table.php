<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('valores', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->decimal('valor_hora_normal', 10, 2);
            $table->decimal('valor_hora_nocturna', 10, 2);
            $table->decimal('valor_hora_dominical', 10, 2);
            $table->decimal('valor_hora_dominical_extra', 10, 2);
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('valores');
    }
};