<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kiosko_devices', function (Blueprint $table) {
            $table->string('guest_token_hash', 64)->nullable()->after('revoked_at');
            $table->timestamp('guest_expires_at')->nullable()->after('guest_token_hash');
        });
    }

    public function down(): void
    {
        Schema::table('kiosko_devices', function (Blueprint $table) {
            $table->dropColumn(['guest_token_hash', 'guest_expires_at']);
        });
    }
};
