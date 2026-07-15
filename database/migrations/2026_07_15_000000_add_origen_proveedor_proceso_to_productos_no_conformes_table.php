<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('productos_no_conformes', function (Blueprint $table) {
            $table->string('origen')->default('cliente')->after('comercial_id');
            $table->foreignId('proceso_id')->nullable()->after('comercial_id')->constrained('procesos')->nullOnDelete();
            $table->foreignId('proveedor_id')->nullable()->after('cliente_id')->constrained('proveedores')->nullOnDelete();
            $table->foreignId('orden_compra_proveedor_id')->nullable()->after('orden_compra_id')->constrained('orden_compra_proveedores')->nullOnDelete();
        });

        // cliente_id era obligatorio; ahora es opcional cuando el origen es "proveedor" o "interno".
        DB::statement('ALTER TABLE productos_no_conformes DROP FOREIGN KEY productos_no_conformes_cliente_id_foreign');
        DB::statement('ALTER TABLE productos_no_conformes MODIFY cliente_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE productos_no_conformes ADD CONSTRAINT productos_no_conformes_cliente_id_foreign FOREIGN KEY (cliente_id) REFERENCES clientes(id)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productos_no_conformes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('proveedor_id');
            $table->dropConstrainedForeignId('orden_compra_proveedor_id');
            $table->dropConstrainedForeignId('proceso_id');
            $table->dropColumn('origen');
        });
    }
};
