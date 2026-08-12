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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams');
            $table->string('acct_id');
            $table->string('title');
            $table->string('type')->default('checking');
            $table->integer('initial_balance')->default(0);
            $table->string('currency')->default('BRL');
            $table->string('fid');

            // CreditCard only
            $table->integer('due_day')->nullable();
            $table->integer('closing_day')->nullable();

            // Checking Only
            $table->string('bank_id')->nullable();
            $table->string('branch_id')->nullable();
            $table->integer('limit')->nullable();

            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
