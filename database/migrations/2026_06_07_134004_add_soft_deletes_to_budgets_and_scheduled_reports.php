<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('budgets') && ! Schema::hasColumn('budgets', 'deleted_at')) {
            Schema::table('budgets', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        if (Schema::hasTable('scheduled_reports') && ! Schema::hasColumn('scheduled_reports', 'deleted_at')) {
            Schema::table('scheduled_reports', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('budgets', 'deleted_at')) {
            Schema::table('budgets', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasColumn('scheduled_reports', 'deleted_at')) {
            Schema::table('scheduled_reports', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
