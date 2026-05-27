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
        Schema::create('bank_deposits', function (Blueprint $table) {
        $table->id();
        $table->string('deposit_reference')->unique();
        $table->foreignId('staff_id')->constrained();   // who deposited
        $table->date('deposit_date');
        $table->decimal('amount', 10, 2);
        $table->string('bank_name');
        $table->string('account_number');
        $table->string('slip_number')->nullable();
        $table->string('slip_file')->nullable();
        $table->enum('status', ['pending','confirmed'])->default('pending');
        $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_deposits');
    }
};
