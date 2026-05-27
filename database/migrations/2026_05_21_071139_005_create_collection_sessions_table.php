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
        // A "session" = one receipt batch from the POS (e.g. receipt 993110504177)
        Schema::create('collection_sessions', function (Blueprint $table) {
        $table->id();
        $table->string('session_reference')->unique(); // 993110504177
        $table->foreignId('staff_id')->constrained();
        $table->date('session_date');
        $table->decimal('expected_amount', 10, 2)->default(0);
        $table->decimal('actual_amount', 10, 2)->default(0);
        $table->decimal('banked_amount', 10, 2)->default(0);
        $table->enum('status', ['open','submitted','reconciled'])->default('open');
        $table->timestamp('submitted_at')->nullable();
        $table->timestamp('reconciled_at')->nullable();
        $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::dropIfExists('collection_sessions');
    }
};
