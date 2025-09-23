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
        Schema::table('posts', function (Blueprint $table) {
            $table->boolean('is_huge_news')->nullable()->after('user_notified_at');
            $table->integer('importance_score')->nullable()->after('is_huge_news');
            $table->timestamp('scored_at')->nullable()->after('importance_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn(['is_huge_news', 'importance_score', 'scored_at']);
        });
    }
};
