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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->unique(); // One-to-one with users
            $table->integer('current_points')->default(0); // Active points for rewards
            $table->integer('total_visits')->default(0); // Lifetime visit count for analytics
            $table->dateTime('last_visit_at')->nullable(); // Track last check-in time
            $table->timestamps();
            
            // Index for reporting queries
            $table->index('current_points'); // Filter customers by points
            $table->index('last_visit_at'); // Sort by recent activity
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
