<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bulk_imports', function (Blueprint $table) {
            if (! Schema::hasColumn('bulk_imports', 'entity_type')) {
                $table->string('entity_type')->nullable()->after('file_path');
            }
            if (! Schema::hasColumn('bulk_imports', 'imported_ids')) {
                $table->json('imported_ids')->nullable();
            }
            if (! Schema::hasColumn('bulk_imports', 'total_rows')) {
                $table->integer('total_rows')->default(0);
            }
            if (! Schema::hasColumn('bulk_imports', 'success_count')) {
                $table->integer('success_count')->default(0);
            }
            if (! Schema::hasColumn('bulk_imports', 'failed_count')) {
                $table->integer('failed_count')->default(0);
            }
            if (! Schema::hasColumn('bulk_imports', 'error_log')) {
                $table->json('error_log')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('bulk_imports', function (Blueprint $table) {
            foreach (['entity_type', 'imported_ids', 'total_rows', 'success_count', 'failed_count', 'error_log'] as $column) {
                if (Schema::hasColumn('bulk_imports', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
