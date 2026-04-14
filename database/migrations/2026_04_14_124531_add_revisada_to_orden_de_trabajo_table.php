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
                $table->boolean('revisada')->default(false)->after('estado_id');
        $table->unsignedBigInteger('revisada_por')->nullable()->after('revisada');
        $table->timestamp('revisada_at')->nullable()->after('revisada_por');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orden_de_trabajo', function (Blueprint $table) {
         $table->dropColumn(['revisada', 'revisada_por', 'revisada_at']);
        });
    }
};
