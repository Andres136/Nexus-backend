<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chatbot_conversaciones', function (Blueprint $table) {
            $table->string('nit_lead')->nullable()->after('telefono_lead');
            $table->string('direccion_lead')->nullable()->after('nit_lead');
            $table->foreignId('cliente_id')->nullable()->after('direccion_lead')->constrained('clientes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('chatbot_conversaciones', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cliente_id');
            $table->dropColumn(['nit_lead', 'direccion_lead']);
        });
    }
};
