<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->date('paid_on');
            $table->string('name', 80);
            $table->unsignedBigInteger('amount');
            $table->enum('method', ['cash', 'atm']);
            $table->unsignedSmallInteger('spread_weeks')->default(1);
            $table->string('note', 160)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->index('paid_on');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
