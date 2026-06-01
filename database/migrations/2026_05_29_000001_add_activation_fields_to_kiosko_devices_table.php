<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kiosko_devices', function (Blueprint $table) {
            $table->string('activation_token_hash', 64)->nullable()->after('tipo_registros_id');
            $table->timestamp('activation_expires_at')->nullable()->after('activation_token_hash');
            $table->timestamp('activation_used_at')->nullable()->after('activation_expires_at');
            $table->string('device_session_token_hash', 64)->nullable()->after('activation_used_at');
            $table->string('device_fingerprint_hash', 64)->nullable()->after('device_session_token_hash');
            $table->timestamp('activated_at')->nullable()->after('device_fingerprint_hash');
            $table->timestamp('last_seen_at')->nullable()->after('activated_at');
            $table->string('last_ip', 45)->nullable()->after('last_seen_at');
            $table->string('status', 20)->default('pending')->after('last_ip');
            $table->timestamp('revoked_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('kiosko_devices', function (Blueprint $table) {
            $table->dropColumn([
                'activation_token_hash',
                'activation_expires_at',
                'activation_used_at',
                'device_session_token_hash',
                'device_fingerprint_hash',
                'activated_at',
                'last_seen_at',
                'last_ip',
                'status',
                'revoked_at',
            ]);
        });
    }
};
