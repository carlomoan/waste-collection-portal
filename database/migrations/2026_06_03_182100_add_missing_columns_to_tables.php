<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('collection_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('collection_sessions', 'planned_amount')) {
                $table->decimal('planned_amount', 15, 2)->nullable()->after('banked_amount');
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'is_reconciled')) {
                $table->boolean('is_reconciled')->default(false)->after('status');
            }
        });

        Schema::table('expenses', function (Blueprint $table) {
            if (!Schema::hasColumn('expenses', 'current_approval_level')) {
                $table->integer('current_approval_level')->default(0)->after('status');
            }
        });

        Schema::table('staff', function (Blueprint $table) {
            if (!Schema::hasColumn('staff', 'name')) {
                $table->string('name')->nullable()->after('id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('collection_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('collection_sessions', 'planned_amount')) {
                $table->dropColumn('planned_amount');
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'is_reconciled')) {
                $table->dropColumn('is_reconciled');
            }
        });

        Schema::table('expenses', function (Blueprint $table) {
            if (Schema::hasColumn('expenses', 'current_approval_level')) {
                $table->dropColumn('current_approval_level');
            }
        });

        Schema::table('staff', function (Blueprint $table) {
            if (Schema::hasColumn('staff', 'name')) {
                $table->dropColumn('name');
            }
        });
    }
};
