<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the old constraint
        DB::statement('ALTER TABLE payments DROP CONSTRAINT IF EXISTS payments_status_check');

        // Recreate with 'pending' included
        DB::statement("
            ALTER TABLE payments
            ADD CONSTRAINT payments_status_check
            CHECK (status IN ('paid', 'pending', 'refunded', 'reversed', 'failed'))
        ");
    }

    public function down(): void
    {
        // Rollback to original constraint
        DB::statement('ALTER TABLE payments DROP CONSTRAINT IF EXISTS payments_status_check');

        DB::statement("
            ALTER TABLE payments
            ADD CONSTRAINT payments_status_check
            CHECK (status IN ('paid', 'refunded', 'reversed', 'failed'))
        ");
    }
};
