<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('content');
            $table->string('url');
            $table->timestamp('published_at');
            $table->string('external_id')->nullable(); // For tracking duplicates
            $table->timestamp('user_notified_at')->nullable(); // When user was notified
            $table->timestamps();
            
            $table->unique(['company_id', 'external_id']);
            $table->index(['company_id', 'published_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
