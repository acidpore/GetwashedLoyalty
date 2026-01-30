<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_login_at')->nullable()->after('remember_token');
            $table->timestamp('last_activity_at')->nullable()->after('last_login_at');
            $table->boolean('is_banned')->default(false)->after('last_activity_at');
            $table->timestamp('banned_at')->nullable()->after('is_banned');
            $table->string('ban_reason')->nullable()->after('banned_at');
            $table->string('last_login_ip', 45)->nullable()->after('ban_reason');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'last_login_at',
                'last_activity_at',
                'is_banned',
                'banned_at',
                'ban_reason',
                'last_login_ip',
            ]);
        });
    }
};
