<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->enum('loyalty_type', ['carwash', 'coffeeshop', 'both']);
            $table->enum('qr_type', ['permanent', 'onetime'])->default('permanent');
            $table->string('name')->nullable();
            $table->string('location')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_used')->default(false);
            $table->datetime('expires_at')->nullable();
            $table->integer('scan_count')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('code');
            $table->index('loyalty_type');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_codes');
    }
};
