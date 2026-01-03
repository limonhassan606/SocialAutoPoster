<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('scheduled_posts', function (Blueprint $table) {
            $table->id();
            $table->json('platforms');
            $table->text('content');
            $table->string('media_url')->nullable();
            $table->enum('media_type', ['image', 'video', 'document'])->nullable();
            $table->timestamp('publish_at');
            $table->string('timezone')->default('UTC');
            $table->tinyInteger('priority')->default(5);
            $table->json('metadata')->nullable();
            $table->enum('status', ['pending', 'published', 'failed', 'cancelled'])->default('pending');
            $table->timestamp('published_at')->nullable();
            $table->json('result')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['status', 'publish_at']);
        });

        Schema::create('recurring_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scheduled_post_id')->constrained('scheduled_posts')->onDelete('cascade');
            $table->enum('type', ['daily', 'weekly', 'monthly']);
            $table->string('time');
            $table->timestamp('until')->nullable();
            $table->string('timezone')->default('UTC');
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_posts');
        Schema::dropIfExists('scheduled_posts');
    }
};
