<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Update any superadmin users to admin
        DB::table('users')->where('role', 'superadmin')->update(['role' => 'admin']);
        
        // Remove superadmin from enum (MySQL specific)
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'customer') DEFAULT 'customer'");
        
        // Drop tracking columns if they exist
        Schema::table('users', function (Blueprint $table) {
            $columns = ['last_login_at', 'last_activity_at', 'is_banned', 'banned_at', 'ban_reason', 'last_login_ip'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        // Re-add superadmin to enum
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'customer', 'superadmin') DEFAULT 'customer'");
        
        // Re-add tracking columns
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable();
            }
            if (!Schema::hasColumn('users', 'last_activity_at')) {
                $table->timestamp('last_activity_at')->nullable();
            }
            if (!Schema::hasColumn('users', 'is_banned')) {
                $table->boolean('is_banned')->default(false);
            }
            if (!Schema::hasColumn('users', 'banned_at')) {
                $table->timestamp('banned_at')->nullable();
            }
            if (!Schema::hasColumn('users', 'ban_reason')) {
                $table->string('ban_reason')->nullable();
            }
            if (!Schema::hasColumn('users', 'last_login_ip')) {
                $table->string('last_login_ip', 45)->nullable();
            }
        });
    }
};
