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
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams');
            $table->string('fitid')->nullable();
            $table->foreignId('account_id')->nullable()->constrained('accounts');
            $table->foreignId('category_id')->nullable()->constrained('categories');
            $table->integer('first_amount')->nullable();
            $table->integer('amount');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->integer('installments')->nullable();
            $table->string('matcher')->nullable();
            $table->string('type')->default('expense');
            $table->string('variant')->default('variable');
            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
