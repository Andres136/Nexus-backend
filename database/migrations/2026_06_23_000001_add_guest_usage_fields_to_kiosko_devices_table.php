<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kiosko_devices', function (Blueprint $table) {
            $table->timestamp('guest_used_at')->nullable()->after('guest_expires_at');
            $table->string('guest_fingerprint_hash', 64)->nullable()->after('guest_used_at');
        });
    }

    public function down(): void
    {
        Schema::table('kiosko_devices', function (Blueprint $table) {
            $table->dropColumn([
                'guest_used_at',
                'guest_fingerprint_hash',
            ]);
        });
    }
};
