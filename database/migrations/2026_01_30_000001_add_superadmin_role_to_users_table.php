<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add temp column, copy data, drop old, rename new
        DB::statement("ALTER TABLE users ADD COLUMN role_new ENUM('admin', 'customer', 'superadmin') DEFAULT 'customer' AFTER role");
        DB::statement("UPDATE users SET role_new = role");
        DB::statement("ALTER TABLE users DROP COLUMN role");
        DB::statement("ALTER TABLE users CHANGE role_new role ENUM('admin', 'customer', 'superadmin') DEFAULT 'customer'");
        
        // Update user ID 1 to superadmin
        DB::table('users')->where('id', 1)->update(['role' => 'superadmin']);
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'superadmin')->update(['role' => 'admin']);
        
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'customer') DEFAULT 'customer'");
    }
};
