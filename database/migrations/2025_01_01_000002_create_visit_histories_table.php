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
        Schema::create('visit_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade'); // Link to customers
            $table->integer('points_earned')->default(1); // Points awarded for this visit
            $table->dateTime('visited_at'); // Timestamp of check-in
            $table->string('ip_address', 45)->nullable(); // Track IP for anti-spam
            $table->timestamps();
            
            // Indexes for filtering and reporting
            $table->index('customer_id'); // Fast customer history lookup
            $table->index('visited_at'); // Sort by date
            $table->index(['customer_id', 'visited_at']); // Combined index for customer timeline
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visit_histories');
    }
};
