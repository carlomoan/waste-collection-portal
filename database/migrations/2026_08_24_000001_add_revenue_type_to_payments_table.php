<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add revenue_type to payments so TZS 200 market levies
     * (Ushuru wa Mnada Soko la Kikundi) are never counted as
     * household waste collection monthly fees.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('revenue_type', 40)->default('household_waste')->after('amount')
                ->comment('household_waste | market_levy | other');
            $table->index('revenue_type');
        });

        // Backfill: any existing payment of exactly 200 is a market levy.
        DB::table('payments')->where('amount', 200)->update(['revenue_type' => 'market_levy']);
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['revenue_type']);
            $table->dropColumn('revenue_type');
        });
    }
};
