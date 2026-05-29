<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('vehicle_maintenances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->onDelete('cascade');
            $table->string('type', 100);
            $table->date('scheduled_date');
            $table->date('completed_date')->nullable();
            $table->enum('status', ['upcoming', 'scheduled', 'completed', 'cancelled'])->default('upcoming');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('vehicle_maintenances');
    }
};
