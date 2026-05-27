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
        Schema::create('expense_categories', function (Blueprint $table) {
        $table->id();
        $table->string('name');                        // "Fuel", "Vehicle Maintenance"
        $table->string('code', 20)->unique();
        $table->timestamps();
    });

        Schema::create('expenses', function (Blueprint $table) {
        $table->id();
        $table->foreignId('expense_category_id')->constrained();
        $table->foreignId('staff_id')->nullable()->constrained(); // who incurred it
        $table->decimal('amount', 10, 2);
        $table->date('expense_date');
        $table->string('description');
        $table->string('receipt_number')->nullable();
        $table->string('receipt_file')->nullable();    // stored path
        $table->enum('status', ['pending','approved','rejected'])->default('pending');
        $table->foreignId('approved_by')->nullable()->constrained('users');
        $table->timestamp('approved_at')->nullable();
        $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_categories');
        Schema::dropIfExists('expenses');
    }
};
