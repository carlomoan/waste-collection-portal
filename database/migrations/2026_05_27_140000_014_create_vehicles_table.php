<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('plate_number', 20)->unique();
            $table->string('type', 50);
            $table->foreignId('driver_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->enum('status', ['active', 'inactive', 'maintenance'])->default('active');
            $table->integer('fuel_level')->default(0);
            $table->date('last_service')->nullable();
            $table->date('purchase_date')->nullable();
            $table->date('insurance_expiry')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('vehicles');
    }
};
