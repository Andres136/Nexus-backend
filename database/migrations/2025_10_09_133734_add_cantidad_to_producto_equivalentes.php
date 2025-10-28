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
    Schema::table('producto_equivalentes', function (Blueprint $table) {
        // Cantidad usada del producto equivalente
        $table->integer('cantidad')
              ->default(0)
              ->after('equivalente_id');

        // Usuario que registró el uso
        $table->unsignedBigInteger('registrado_por')
              ->nullable()
              ->after('cantidad');

        $table->foreign('registrado_por')
              ->references('id')
              ->on('users')
              ->onDelete('set null');
    });
}

public function down(): void
{
    Schema::table('producto_equivalentes', function (Blueprint $table) {
        $table->dropForeign(['registrado_por']);
        $table->dropColumn(['registrado_por', 'cantidad']);
    });
}

};
