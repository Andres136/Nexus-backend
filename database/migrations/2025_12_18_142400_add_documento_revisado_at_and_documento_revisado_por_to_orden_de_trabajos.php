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
        Schema::table('orden_de_trabajos', function (Blueprint $table) {

              $table->timestamp('documento_revisado_at')->nullable();
              $table->string('code',30)->unique()->nullable();
          $table->foreignId('documento_revisado_por')
          ->nullable()
          ->after('documento_revisado_at')
          ->constrained('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orden_de_trabajos', function (Blueprint $table) {
            $table->dropForeign(['documento_revisado_por']);
            $table->dropColumn(['documento_revisado_at', 'documento_revisado_por', 'code']);
        });
    }
};
