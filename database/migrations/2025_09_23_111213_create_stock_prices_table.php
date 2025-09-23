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
        Schema::create('stock_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->decimal('price', 10, 4); // Stock price with 4 decimal places
            $table->decimal('open', 10, 4)->nullable(); // Opening price
            $table->decimal('high', 10, 4)->nullable(); // High price
            $table->decimal('low', 10, 4)->nullable(); // Low price
            $table->decimal('close', 10, 4)->nullable(); // Closing price
            $table->bigInteger('volume')->nullable(); // Trading volume
            $table->decimal('change', 10, 4)->nullable(); // Price change
            $table->decimal('change_percent', 8, 4)->nullable(); // Percentage change
            $table->timestamp('price_at'); // When this price was recorded
            $table->timestamps();
            
            $table->index(['company_id', 'price_at']);
            $table->unique(['company_id', 'price_at']); // Prevent duplicate entries for same time
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_prices');
    }
};
