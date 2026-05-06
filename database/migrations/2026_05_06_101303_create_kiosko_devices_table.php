<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kiosko_devices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('sede_id')->constrained('sedes')->onDelete('cascade');
            $table->string('name', 45);
            $table->string('code', 45)->unique();
            $table->string('ip_adres', 45);
            $table->string('descripcion', 255)->nullable();
            $table->foreignId('bodega_id')->constrained('bodegas')->onDelete('cascade');
            $table->foreignId('tipo_registros_id')->constrained('tipo_registros')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kiosko_devices');
    }
};
