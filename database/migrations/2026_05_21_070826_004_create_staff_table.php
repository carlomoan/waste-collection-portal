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
        Schema::create('staff', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained();
        $table->string('staff_number')->unique();      // WCP-STF-001
        $table->string('national_id')->nullable();
        $table->string('phone');
        $table->foreignId('zone_id')->nullable()->constrained();
        $table->enum('role', ['collector','supervisor','accountant','manager','admin']);
        $table->decimal('base_salary', 10, 2)->default(0);
        $table->date('hire_date');
        $table->boolean('is_active')->default(true);
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
        Schema::dropIfExists('staff');
    }
};
