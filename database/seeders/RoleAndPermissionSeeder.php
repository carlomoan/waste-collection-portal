<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Permissions
        $permissions = [
            // Clients
            'clients.view', 'clients.create', 'clients.edit', 'clients.delete',
            // Payments
            'payments.view', 'payments.record', 'payments.reverse',
            // Invoices
            'invoices.view', 'invoices.generate', 'invoices.export',
            // Debts
            'debts.view', 'debts.apply_penalty', 'debts.write_off',
            // Finance
            'expenses.view', 'expenses.create', 'expenses.approve',
            'salaries.view', 'salaries.process',
            'banking.view', 'banking.record',
            // Reports
            'reports.collector_performance', 'reports.monthly',
            'reports.yearly', 'reports.export',
            // Staff & HR
            'staff.view', 'staff.manage', 'attendance.view', 'attendance.manage',
            // System
            'settings.manage', 'users.manage', 'roles.manage', 'audit.view',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // Roles
        $roles = [
            'super_admin' => $permissions, // all
            'manager' => [
                'clients.view','clients.create','clients.edit',
                'payments.view','invoices.view','invoices.generate',
                'debts.view','debts.apply_penalty',
                'expenses.view','expenses.approve',
                'salaries.view','banking.view','banking.record',
                'reports.collector_performance','reports.monthly','reports.yearly','reports.export',
                'staff.view','attendance.view','attendance.manage',
            ],
            'accountant' => [
                'clients.view','payments.view','invoices.view','invoices.export',
                'debts.view','expenses.view','expenses.create',
                'salaries.view','salaries.process','banking.view','banking.record',
                'reports.monthly','reports.yearly','reports.export',
            ],
            'collector' => [
                'clients.view','payments.view','payments.record',
                'invoices.view','attendance.view',
            ],
            'supervisor' => [
                'clients.view','payments.view',
                'invoices.view','debts.view',
                'reports.collector_performance',
                'staff.view','attendance.view','attendance.manage',
            ],
            'viewer' => ['clients.view','payments.view','invoices.view','reports.monthly'],
        ];

        foreach ($roles as $roleName => $perms) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $role->syncPermissions($perms);
        }
    }
}
