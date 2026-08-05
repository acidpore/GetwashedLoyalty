<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reward_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('loyalty_type');
            $table->unsignedInteger('points_used');
            $table->string('reward');
            $table->foreignId('claimed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('claimed_at');
            $table->timestamps();

            $table->index(['customer_id', 'loyalty_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reward_claims');
    }
};
