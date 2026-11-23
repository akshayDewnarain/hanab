<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('help_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('help_categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon', 80)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('help_articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('help_categories')->nullOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('editor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('summary', 500)->nullable();
            $table->longText('content');
            $table->longText('search_text')->nullable();
            $table->string('keywords', 240)->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->string('visibility', 20)->default('authenticated')->index();
            $table->string('context_key', 80)->nullable()->index();
            $table->string('audience_permission', 80)->nullable()->index();
            $table->string('feature_path', 180)->nullable();
            $table->string('app_version', 40)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_getting_started')->default(false);
            $table->unsignedInteger('version')->default(1);
            $table->timestamp('published_at')->nullable();
            $table->date('review_due_on')->nullable();
            $table->timestamps();
        });

        Schema::create('help_article_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('help_article_id')->constrained('help_articles')->cascadeOnDelete();
            $table->unsignedInteger('number');
            $table->string('title');
            $table->string('summary', 500)->nullable();
            $table->longText('content');
            $table->foreignId('editor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note', 180)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->unique(['help_article_id', 'number']);
        });

        Schema::create('help_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('help_article_id')->constrained('help_articles')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('filename');
            $table->string('path');
            $table->string('mime', 120);
            $table->unsignedBigInteger('size');
            $table->string('caption', 180)->nullable();
            $table->string('alt_text', 180);
            $table->timestamps();
        });

        Schema::create('help_article_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('help_article_id')->constrained('help_articles')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('helpful');
            $table->timestamps();
            $table->unique(['help_article_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('help_article_feedback');
        Schema::dropIfExists('help_attachments');
        Schema::dropIfExists('help_article_revisions');
        Schema::dropIfExists('help_articles');
        Schema::dropIfExists('help_categories');
    }
};
