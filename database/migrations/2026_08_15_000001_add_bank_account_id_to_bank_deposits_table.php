<?php

use App\Models\BankAccount;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_deposits', function (Blueprint $table) {
            if (! Schema::hasColumn('bank_deposits', 'bank_account_id')) {
                $table->foreignId('bank_account_id')->nullable()->after('staff_id')->constrained('bank_accounts')->nullOnDelete();
            }
        });

        if (Schema::hasColumn('bank_deposits', 'bank_name') && Schema::hasColumn('bank_deposits', 'account_number')) {
            DB::table('bank_deposits')
                ->whereNull('bank_account_id')
                ->orderBy('id')
                ->each(function ($deposit) {
                    $account = BankAccount::where('bank_name', $deposit->bank_name)
                        ->where('account_number', $deposit->account_number)
                        ->first();

                    if ($account) {
                        DB::table('bank_deposits')
                            ->where('id', $deposit->id)
                            ->update(['bank_account_id' => $account->id]);
                    }
                });
        }
    }

    public function down(): void
    {
        Schema::table('bank_deposits', function (Blueprint $table) {
            if (Schema::hasColumn('bank_deposits', 'bank_account_id')) {
                $table->dropConstrainedForeignId('bank_account_id');
            }
        });
    }
};
