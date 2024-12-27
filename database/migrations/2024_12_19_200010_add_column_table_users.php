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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignID('departamento_id')->constrained();
            $table->foreignID('role_id')->constrained()->onDelete('cascade');
            $table->foreignID('estado_id')->constrained()->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['departamento_id']);
            $table->dropForeign(['role_id']);
            $table->dropForeign(['estado_id']);
            $table->dropColumn(['departamento_id', 'role_id', 'estado_id']);
    
        });
    }
};
