<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('scheduled_reports', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('type');
            $t->string('frequency')->default('weekly');
            $t->json('recipients')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamp('last_sent_at')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('scheduled_reports'); }
};
