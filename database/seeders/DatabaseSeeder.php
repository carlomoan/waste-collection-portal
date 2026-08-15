<?php

namespace Database\Seeders;

use App\Models\BankAccount;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleAndPermissionSeeder::class);

        // Create the specific super admin user
        $superAdmin = User::updateOrCreate(
            ['email' => 'agbbelly89@gmail.com'],
            [
                'name' => 'Andrew Bung\'ombe',
                'username' => 'Agbbelly89',
                'password' => Hash::make('Agbbills29!'),
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        if ($superAdminRole = Role::where('name', 'super_admin')->first()) {
            $superAdmin->roles()->syncWithoutDetaching([$superAdminRole->id]);
        }

        // Create default bank accounts
        BankAccount::firstOrCreate(
            ['account_number' => 'CRDB-001-123456'],
            [
                'bank_name' => 'CRDB Bank',
                'account_number' => 'CRDB-001-123456',
                'account_holder' => 'Waste Collection Portal',
                'balance' => 0,
                'is_active' => true,
            ]
        );

        BankAccount::firstOrCreate(
            ['account_number' => 'NMB-002-789012'],
            [
                'bank_name' => 'NMB Bank',
                'account_number' => 'NMB-002-789012',
                'account_holder' => 'Waste Collection Portal',
                'balance' => 0,
                'is_active' => true,
            ]
        );
    }
}