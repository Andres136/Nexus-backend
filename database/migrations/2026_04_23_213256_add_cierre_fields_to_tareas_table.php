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
        Schema::table('tareas', function (Blueprint $table) {
              $table->timestamp('fecha_cerrado')->nullable()->after('fecha_fin');
        $table->foreignId('user_id_creo')->nullable()->constrained('users')->nullOnDelete()->after('user_id');
     
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tareas', function (Blueprint $table) {
            $table->dropForeign(['user_id_creo']);
            $table->dropColumn(['fecha_cerrado', 'user_id_creo']);
        });
    }
};
