<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('budgets', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->decimal('total_budget',15,2)->default(0);
            $t->decimal('spent',15,2)->default(0);
            $t->integer('year');
            $t->integer('month')->nullable();
            $t->string('status')->default('active');
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('budgets'); }
};
