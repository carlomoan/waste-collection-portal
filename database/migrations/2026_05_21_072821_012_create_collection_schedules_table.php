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
        Schema::create('collection_schedules', function (Blueprint $table) {
        $table->id();
        $table->foreignId('zone_id')->constrained();
        $table->foreignId('staff_id')->constrained();
        $table->enum('frequency', ['weekly','biweekly','monthly']);
        $table->json('days_of_week');                  // [1,3] = Mon, Wed
        $table->date('effective_from');
        $table->date('effective_to')->nullable();
        $table->boolean('is_active')->default(true);
        $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collection_schedules');
    }
};
