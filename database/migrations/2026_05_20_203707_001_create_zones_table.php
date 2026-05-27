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
        Schema::create('zones', function (Blueprint $table) {
        $table->id();
        $table->string('name');                        // "Zone A — Kariakoo"
        $table->string('code', 10)->unique();          // "ZA", "ZB"
        $table->text('description')->nullable();
        $table->string('color', 7)->default('#4caf76'); // hex for map display
        $table->boolean('is_active')->default(true);
        $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zones');
    }
};
