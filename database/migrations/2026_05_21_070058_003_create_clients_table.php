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
        Schema::create('clients', function (Blueprint $table) {
        $table->id();
        $table->string('client_number')->unique();     // WCP-2024-00001
        $table->string('name');
        $table->string('phone')->nullable();
        $table->string('email')->nullable();
        $table->foreignId('zone_id')->constrained();
        $table->foreignId('client_type_id')->constrained();
        $table->decimal('monthly_fee', 10, 2);         // can differ from type default
        $table->string('address')->nullable();
        $table->text('notes')->nullable();
        $table->enum('status', ['active','inactive','suspended'])->default('active');
        $table->date('contract_start_date')->nullable();
        $table->decimal('credit_balance', 10, 2)->default(0); // overpayment carried forward
        $table->timestamps();
        $table->softDeletes();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::dropIfExists('client_types');
    }
};
