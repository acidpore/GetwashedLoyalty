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
        Schema::table('broadcasts', function (Blueprint $table) {
            // Modify the column to include 'sent'
            $table->enum('status', ['draft', 'queued', 'sending', 'sent', 'completed', 'failed'])->default('draft')->change();
        });
    }

    public function down(): void
    {
        Schema::table('broadcasts', function (Blueprint $table) {
            // Revert back to original enum values
            $table->enum('status', ['draft', 'queued', 'sending', 'completed', 'failed'])->default('draft')->change();
        });
    }
};
