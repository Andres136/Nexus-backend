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
        Schema::table('orden__compra__detalles', function (Blueprint $table) {
             $table->string('tipo_embalaje')->nullable();
             $table->string('codigo_embalaje')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orden__compra__detalles', function (Blueprint $table) {
           
                $table->dropColumn('tipo_embalaje', 'codigo_embalaje');
        });
    }
};
