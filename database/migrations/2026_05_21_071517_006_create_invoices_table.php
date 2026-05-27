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
        //
        Schema::create('invoices', function (Blueprint $table) {
        $table->id();
        $table->string('invoice_number')->unique();    // INV-2026-05-00001
        $table->foreignId('client_id')->constrained();
        $table->integer('billing_month');              // 5
        $table->integer('billing_year');               // 2026
        $table->decimal('amount_due', 10, 2);
        $table->decimal('amount_paid', 10, 2)->default(0);
        $table->decimal('balance', 10, 2);             // computed: due - paid
        $table->decimal('penalty_amount', 10, 2)->default(0);
        $table->date('due_date');
        $table->date('grace_period_end');
        $table->enum('status', ['unpaid','partial','paid','overdue','penalized'])->default('unpaid');
        $table->boolean('penalty_applied')->default(false);
        $table->timestamp('paid_at')->nullable();
        $table->timestamps();

        $table->unique(['client_id', 'billing_month', 'billing_year']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::dropIfExists('invoices');

    }
};
