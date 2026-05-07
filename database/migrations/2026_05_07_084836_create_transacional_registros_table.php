<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transacional_registros', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('users_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('kiosk_device_id')->constrained('kiosko_devices')->onDelete('cascade');
            $table->foreignId('tipo_marcacion_id')->constrained('tipo_registros')->onDelete('cascade');
            $table->string('foto_referencia')->nullable();
            $table->time('marked_ad');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transacional_registros');
    }
};
