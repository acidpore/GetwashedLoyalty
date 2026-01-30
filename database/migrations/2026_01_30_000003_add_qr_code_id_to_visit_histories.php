<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visit_histories', function (Blueprint $table) {
            $table->foreignId('qr_code_id')->nullable()->after('customer_id')->constrained('qr_codes')->nullOnDelete();
            $table->index(['customer_id', 'qr_code_id', 'visited_at']);
        });
    }

    public function down(): void
    {
        Schema::table('visit_histories', function (Blueprint $table) {
            $table->dropForeign(['qr_code_id']);
            $table->dropColumn('qr_code_id');
        });
    }
};
