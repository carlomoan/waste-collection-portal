<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('receipt_number')->nullable()->after('control_number');
            $table->string('pos_number')->nullable()->after('receipt_number');
            $table->index('receipt_number');
            $table->index('pos_number');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['receipt_number']);
            $table->dropIndex(['pos_number']);
            $table->dropColumn(['receipt_number', 'pos_number']);
        });
    }
};
