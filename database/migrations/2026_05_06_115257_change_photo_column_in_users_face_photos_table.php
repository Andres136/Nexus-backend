<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users_face_photos', function (Blueprint $table) {
            $table->string('photo')->change();
        });
    }

    public function down(): void
    {
        Schema::table('users_face_photos', function (Blueprint $table) {
            $table->integer('photo')->change();
        });
    }
};
