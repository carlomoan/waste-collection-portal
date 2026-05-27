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
        Schema::create('debts', function (Blueprint $table) {
        $table->id();
        $table->foreignId('client_id')->constrained();
        $table->foreignId('invoice_id')->constrained();
        $table->decimal('original_amount', 10, 2);
        $table->decimal('paid_amount', 10, 2)->default(0);
        $table->decimal('outstanding', 10, 2);
        $table->decimal('penalty_rate', 5, 2)->default(10.00); // % per month
        $table->decimal('penalty_amount', 10, 2)->default(0);
        $table->boolean('penalty_applied')->default(false);
        $table->date('penalty_applied_at')->nullable();
        $table->enum('status', ['active','partially_paid','settled','written_off'])
            ->default('active');
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('debts');
    }
};
