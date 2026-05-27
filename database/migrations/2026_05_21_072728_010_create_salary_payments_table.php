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
        Schema::create('salary_payments', function (Blueprint $table) {
        $table->id();
        $table->foreignId('staff_id')->constrained();
        $table->integer('pay_month');
        $table->integer('pay_year');
        $table->decimal('base_salary', 10, 2);
        $table->decimal('allowances', 10, 2)->default(0);
        $table->decimal('commissions', 10, 2)->default(0); // collector performance bonus
        $table->decimal('deductions', 10, 2)->default(0);
        $table->decimal('net_salary', 10, 2);
        $table->enum('status', ['pending','paid'])->default('pending');
        $table->date('paid_date')->nullable();
        $table->timestamps();

        $table->unique(['staff_id', 'pay_month', 'pay_year']);
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_payments');
    }
};
