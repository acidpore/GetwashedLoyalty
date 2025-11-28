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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique()->nullable(); // Nullable for customers (phone-based)
            $table->string('phone', 20)->unique()->nullable(); // Phone as unique ID for customers
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable(); // Nullable for customers (passwordless)
            $table->enum('role', ['admin', 'customer'])->default('customer'); // Role-based system
            $table->rememberToken();
            $table->timestamps();
            
            // Indexes for better performance
            $table->index('phone'); // Fast lookup by phone
            $table->index('role'); // Filter by role
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
