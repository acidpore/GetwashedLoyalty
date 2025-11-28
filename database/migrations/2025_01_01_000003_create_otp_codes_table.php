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
        Schema::create('otp_codes', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20); // Phone number that requested OTP
            $table->string('otp_code', 6); // 6-digit OTP code
            $table->dateTime('expires_at'); // OTP expiration time (5 minutes from creation)
            $table->boolean('is_used')->default(false); // Track if OTP has been used
            $table->timestamps();
            
            // Indexes for fast OTP verification
            $table->index('phone'); // Lookup by phone
            $table->index(['phone', 'otp_code']); // Combined index for verification
            $table->index('expires_at'); // For cleanup of expired OTPs
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otp_codes');
    }
};
