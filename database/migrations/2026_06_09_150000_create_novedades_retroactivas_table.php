<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('novedades_retroactivas')) {
            Schema::create('novedades_retroactivas', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('user_id')->constrained('users');
                $table->date('fecha_origen');
                $table->date('aplicar_desde');
                $table->date('aplicar_hasta')->nullable();
                $table->enum('tipo', ['devengo', 'deduccion']);
                $table->string('concepto');
                $table->decimal('valor', 14, 2);
                $table->enum('status', ['pendiente', 'aprobada', 'rechazada', 'aplicada'])->default('pendiente');
                $table->text('observacion')->nullable();
                $table->foreignId('registrado_por')->nullable()->constrained('users');
                $table->foreignId('autorizado_por')->nullable()->constrained('users');
                $table->timestamp('fecha_gestion')->nullable();
                $table->string('observacion_gestion')->nullable();
                $table->foreignId('nomina_id')->nullable()->constrained('nomina');
                $table->timestamps();
                $table->softDeletes();

                $table->index(['user_id', 'status', 'aplicar_desde', 'aplicar_hasta'], 'novretro_user_status_fechas_idx');
            });
        } elseif (! $this->indexExists('novedades_retroactivas', 'novretro_user_status_fechas_idx')) {
            Schema::table('novedades_retroactivas', function (Blueprint $table) {
                $table->index(['user_id', 'status', 'aplicar_desde', 'aplicar_hasta'], 'novretro_user_status_fechas_idx');
            });
        }

        Schema::table('nomina', function (Blueprint $table) {
            if (! Schema::hasColumn('nomina', 'total_novedades_retroactivas')) {
                $table->decimal('total_novedades_retroactivas', 14, 2)->default(0)->after('total_comisiones');
            }
            if (! Schema::hasColumn('nomina', 'detalle_novedades_retroactivas')) {
                $table->json('detalle_novedades_retroactivas')->nullable()->after('total_novedades_retroactivas');
            }
        });
    }

    public function down(): void
    {
        Schema::table('nomina', function (Blueprint $table) {
            if (Schema::hasColumn('nomina', 'total_novedades_retroactivas')) {
                $table->dropColumn('total_novedades_retroactivas');
            }
            if (Schema::hasColumn('nomina', 'detalle_novedades_retroactivas')) {
                $table->dropColumn('detalle_novedades_retroactivas');
            }
        });

        Schema::dropIfExists('novedades_retroactivas');
    }

    private function indexExists(string $table, string $index): bool
    {
        return collect(DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$index]))->isNotEmpty();
    }
};
