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
        Schema::table('vehicles', function (Blueprint $table) {
            $table->boolean('is_hired')->default(false)->after('insurance_expiry');
            $table->date('hire_start_date')->nullable()->after('is_hired');
            $table->date('hire_end_date')->nullable()->after('hire_start_date');
            $table->decimal('hire_cost', 10, 2)->nullable()->after('hire_end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn(['is_hired', 'hire_start_date', 'hire_end_date', 'hire_cost']);
        });
    }
};
