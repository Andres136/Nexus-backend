<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->foreignId('chatbot_conversacion_id')->nullable()->after('id')->constrained('chatbot_conversaciones')->nullOnDelete();
            $table->foreignId('responsable_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->string('estado_aprobacion', 20)->default('borrador')->after('responsable_id');
            $table->foreignId('aprobado_por')->nullable()->after('estado_aprobacion')->constrained('users')->nullOnDelete();
            $table->timestamp('aprobado_at')->nullable()->after('aprobado_por');
            $table->text('motivo_rechazo')->nullable()->after('aprobado_at');
            $table->index(['responsable_id', 'estado_aprobacion']);
        });
    }

    public function down(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->dropConstrainedForeignId('chatbot_conversacion_id');
            $table->dropConstrainedForeignId('responsable_id');
            $table->dropConstrainedForeignId('aprobado_por');
            $table->dropColumn(['estado_aprobacion', 'aprobado_at', 'motivo_rechazo']);
        });
    }
};
