<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('record_id')->nullable()->after('module');
            $table->text('old_values')->nullable()->after('record_id');
            $table->text('new_values')->nullable()->after('old_values');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn(['record_id', 'old_values', 'new_values']);
        });
    }
};
