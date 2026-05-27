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
        Schema::create('payments', function (Blueprint $table) {
        $table->id();
        $table->string('control_number')->unique();    // 5260000007056
        $table->string('bill_reference');              // b08c7ca4-a378-...
        $table->foreignId('invoice_id')->nullable()->constrained();
        $table->foreignId('client_id')->constrained();
        $table->foreignId('collection_session_id')->constrained();
        $table->foreignId('staff_id')->constrained();  // collector
        $table->decimal('amount', 10, 2);
        $table->string('payer_name')->nullable();       // as on receipt
        $table->enum('payment_method', ['cash','mobile_money','bank'])->default('cash');
        $table->enum('status', ['paid','reversed'])->default('paid');
        $table->timestamp('paid_at');
        $table->text('notes')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
