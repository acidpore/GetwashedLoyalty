<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'customer', 'superadmin') DEFAULT 'customer'");
        
        DB::table('users')->where('id', 1)->update(['role' => 'superadmin']);
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'superadmin')->update(['role' => 'admin']);
        
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'customer') DEFAULT 'customer'");
    }
};
