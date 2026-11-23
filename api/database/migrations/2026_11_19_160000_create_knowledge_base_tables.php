<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('knowledge_categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon', 80)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('knowledge_articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('knowledge_categories')->nullOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('editor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('summary')->nullable();
            $table->longText('content');
            $table->longText('search_text')->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->string('visibility', 20)->default('authenticated')->index();
            $table->boolean('featured')->default(false);
            $table->unsignedInteger('version')->default(1);
            $table->timestamp('published_at')->nullable()->index();
            $table->date('review_due_on')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('knowledge_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('knowledge_article_tag', function (Blueprint $table) {
            $table->foreignId('knowledge_article_id')->constrained('knowledge_articles')->cascadeOnDelete();
            $table->foreignId('knowledge_tag_id')->constrained('knowledge_tags')->cascadeOnDelete();
            $table->primary(['knowledge_article_id', 'knowledge_tag_id']);
        });

        Schema::create('knowledge_article_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('knowledge_article_id')->constrained('knowledge_articles')->cascadeOnDelete();
            $table->unsignedInteger('number');
            $table->string('title');
            $table->text('summary')->nullable();
            $table->longText('content');
            $table->foreignId('editor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->unique(['knowledge_article_id', 'number']);
        });

        Schema::create('knowledge_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('knowledge_article_id')->constrained('knowledge_articles')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('filename');
            $table->string('path');
            $table->string('mime', 120);
            $table->unsignedBigInteger('size');
            $table->timestamps();
        });

        Schema::create('knowledge_article_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('knowledge_article_id')->constrained('knowledge_articles')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('helpful');
            $table->timestamps();
            $table->unique(['knowledge_article_id', 'user_id']);
        });

        Schema::create('knowledge_article_skill', function (Blueprint $table) {
            $table->foreignId('knowledge_article_id')->constrained('knowledge_articles')->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained('skills')->cascadeOnDelete();
            $table->primary(['knowledge_article_id', 'skill_id']);
        });

        Schema::create('knowledge_article_certificate', function (Blueprint $table) {
            $table->foreignId('knowledge_article_id')->constrained('knowledge_articles')->cascadeOnDelete();
            $table->foreignId('certificate_id')->constrained('certificates')->cascadeOnDelete();
            $table->primary(['knowledge_article_id', 'certificate_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_article_certificate');
        Schema::dropIfExists('knowledge_article_skill');
        Schema::dropIfExists('knowledge_article_feedback');
        Schema::dropIfExists('knowledge_attachments');
        Schema::dropIfExists('knowledge_article_revisions');
        Schema::dropIfExists('knowledge_article_tag');
        Schema::dropIfExists('knowledge_tags');
        Schema::dropIfExists('knowledge_articles');
        Schema::dropIfExists('knowledge_categories');
    }
};
