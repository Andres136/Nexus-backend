<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('corporate_documents', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 120)->unique();
            $table->string('title', 180);
            $table->string('subtitle', 220);
            $table->text('description');
            $table->text('long_description')->nullable();
            $table->string('icon', 60)->default('file-text');
            $table->string('file_type', 20)->default('PDF');
            $table->string('file_size', 40)->nullable();
            $table->unsignedInteger('pages')->nullable();
            $table->date('last_update')->nullable();
            $table->string('category', 80);
            $table->string('theme', 60)->default('institucional');
            $table->string('file_path', 500);
            $table->unsignedBigInteger('downloads_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('corporate_document_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('corporate_document_id')->constrained('corporate_documents')->cascadeOnDelete();
            $table->string('text', 255);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('corporate_document_benefits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('corporate_document_id')->constrained('corporate_documents')->cascadeOnDelete();
            $table->string('text', 255);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('corporate_document_downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('corporate_document_id')->constrained('corporate_documents')->cascadeOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('referer', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('corporate_document_downloads');
        Schema::dropIfExists('corporate_document_benefits');
        Schema::dropIfExists('corporate_document_features');
        Schema::dropIfExists('corporate_documents');
    }
};
