<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('dashboard_token', 64)->nullable()->after('motorwash_last_visit_at');
            $table->datetime('token_expires_at')->nullable()->after('dashboard_token');
            $table->index('dashboard_token');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['dashboard_token']);
            $table->dropColumn(['dashboard_token', 'token_expires_at']);
        });
    }
};
