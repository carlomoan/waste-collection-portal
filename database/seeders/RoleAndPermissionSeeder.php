<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Seed roles and permissions matching the application's custom RBAC tables.
     */
    public function run(): void
    {
        $permissions = [
            'Clients' => [
                'clients.view' => 'View clients',
                'clients.create' => 'Create clients',
                'clients.edit' => 'Edit clients',
                'clients.delete' => 'Delete clients',
                'clients.export' => 'Export clients',
                'clients.import' => 'Import clients',
            ],
            'Client Types' => [
                'client-types.view' => 'View client types',
                'client-types.create' => 'Create client types',
                'client-types.edit' => 'Edit client types',
                'client-types.delete' => 'Delete client types',
            ],
            'Zones' => [
                'zones.view' => 'View zones',
                'zones.create' => 'Create zones',
                'zones.edit' => 'Edit zones',
                'zones.delete' => 'Delete zones',
            ],
            'Payments' => [
                'payments.view' => 'View payments',
                'payments.record' => 'Record payments',
                'payments.reverse' => 'Reverse payments',
                'payments.export' => 'Export payments',
                'payments.import' => 'Import payments',
                'payments.reconcile' => 'Reconcile payments',
            ],
            'Invoices' => [
                'invoices.view' => 'View invoices',
                'invoices.generate' => 'Generate invoices',
                'invoices.export' => 'Export invoices',
                'invoices.send' => 'Send invoices',
                'invoices.cancel' => 'Cancel invoices',
            ],
            'Debts' => [
                'debts.view' => 'View debts',
                'debts.manage' => 'Manage debts',
                'debts.export' => 'Export debts',
            ],
            'Finance' => [
                'expenses.view' => 'View expenses',
                'expenses.create' => 'Create expenses',
                'expenses.edit' => 'Edit expenses',
                'expenses.delete' => 'Delete expenses',
                'expenses.approve' => 'Approve expenses',
                'expenses.export' => 'Export expenses',
                'expense-categories.view' => 'View expense categories',
                'expense-categories.create' => 'Create expense categories',
                'expense-categories.edit' => 'Edit expense categories',
                'expense-categories.delete' => 'Delete expense categories',
                'banking.view' => 'View banking',
                'banking.record' => 'Record banking',
                'banking.confirm' => 'Confirm deposits',
                'banking.reconcile' => 'Reconcile statements',
                'banking.export' => 'Export banking',
                'bank-accounts.view' => 'View bank accounts',
                'bank-accounts.create' => 'Create bank accounts',
                'bank-accounts.edit' => 'Edit bank accounts',
                'bank-accounts.delete' => 'Delete bank accounts',
                'budgets.view' => 'View budgets',
                'budgets.create' => 'Create budgets',
                'budgets.edit' => 'Edit budgets',
                'budgets.delete' => 'Delete budgets',
            ],
            'Reports' => [
                'reports.monthly' => 'View monthly reports',
                'reports.yearly' => 'View yearly reports',
                'reports.export' => 'Export reports',
                'reports.scheduled.view' => 'View scheduled reports',
                'reports.scheduled.create' => 'Create scheduled reports',
                'reports.scheduled.edit' => 'Edit scheduled reports',
                'reports.scheduled.delete' => 'Delete scheduled reports',
                'analytics.view' => 'View analytics',
                'analytics.export' => 'Export analytics',
            ],
            'HR' => [
                'staff.view' => 'View staff',
                'staff.create' => 'Create staff',
                'staff.edit' => 'Edit staff',
                'staff.delete' => 'Delete staff',
                'staff.export' => 'Export staff',
                'attendance.view' => 'View attendance',
                'attendance.record' => 'Record attendance',
                'attendance.edit' => 'Edit attendance',
                'attendance.export' => 'Export attendance',
                'leave.view' => 'View leave requests',
                'leave.approve' => 'Approve leave requests',
                'leave.reject' => 'Reject leave requests',
                'payroll.view' => 'View payroll',
                'payroll.process' => 'Process payroll',
                'payroll.export' => 'Export payroll',
                'salary-advances.view' => 'View salary advances',
                'salary-advances.approve' => 'Approve salary advances',
                'salary-payments.view' => 'View salary payments',
                'salary-payments.process' => 'Process salary payments',
            ],
            'Operations' => [
                'schedules.view' => 'View schedules',
                'schedules.create' => 'Create schedules',
                'schedules.edit' => 'Edit schedules',
                'schedules.delete' => 'Delete schedules',
                'collection-sessions.view' => 'View collection sessions',
                'collection-sessions.create' => 'Create collection sessions',
                'collection-sessions.complete' => 'Complete collection sessions',
                'vehicles.view' => 'View vehicles',
                'vehicles.create' => 'Create vehicles',
                'vehicles.edit' => 'Edit vehicles',
                'vehicles.delete' => 'Delete vehicles',
                'vehicle-maintenance.view' => 'View vehicle maintenance',
                'vehicle-maintenance.create' => 'Create vehicle maintenance',
                'vehicle-maintenance.complete' => 'Complete vehicle maintenance',
                'bulk-import.view' => 'View bulk imports',
                'bulk-import.create' => 'Create bulk imports',
                'bulk-import.rollback' => 'Rollback bulk imports',
            ],
            'System' => [
                'settings.manage' => 'Manage settings',
                'settings.database.export' => 'Export database',
                'settings.database.backup' => 'Backup database',
                'settings.database.restore' => 'Restore database',
                'settings.cache.clear' => 'Clear cache',
                'users.manage' => 'Manage users',
                'users.view' => 'View users',
                'users.create' => 'Create users',
                'users.edit' => 'Edit users',
                'users.delete' => 'Delete users',
                'roles.manage' => 'Manage roles',
                'roles.view' => 'View roles',
                'roles.create' => 'Create roles',
                'roles.edit' => 'Edit roles',
                'roles.delete' => 'Delete roles',
                'permissions.view' => 'View permissions',
                'audit.view' => 'View audit log',
                'audit.export' => 'Export audit log',
                'maintenance.mode' => 'Toggle maintenance mode',
            ],
        ];

        $permissionIds = [];

        foreach ($permissions as $group => $items) {
            foreach ($items as $name => $description) {
                $permission = Permission::updateOrCreate(
                    ['name' => $name],
                    ['group' => $group, 'description' => $description],
                );
                $permissionIds[$name] = $permission->id;
            }
        }

        $allPermissionIds = array_values($permissionIds);

        $roles = [
            'super_admin' => [
                'description' => 'Full access to every part of the system.',
                'permissions' => $allPermissionIds,
            ],
            'manager' => [
                'description' => 'Operational management across collections and finance.',
                'permissions' => $this->idsFor($permissionIds, [
                    'clients.view', 'clients.create', 'clients.edit', 'clients.export',
                    'client-types.view', 'client-types.create', 'client-types.edit',
                    'zones.view', 'zones.create', 'zones.edit',
                    'payments.view', 'payments.record', 'payments.export', 'payments.reconcile',
                    'invoices.view', 'invoices.generate', 'invoices.export', 'invoices.send',
                    'debts.view', 'debts.manage', 'debts.export',
                    'expenses.view', 'expenses.create', 'expenses.edit', 'expenses.approve', 'expenses.export',
                    'expense-categories.view', 'expense-categories.create', 'expense-categories.edit',
                    'banking.view', 'banking.record', 'banking.confirm', 'banking.reconcile', 'banking.export',
                    'bank-accounts.view', 'bank-accounts.create', 'bank-accounts.edit',
                    'budgets.view', 'budgets.create', 'budgets.edit',
                    'reports.monthly', 'reports.yearly', 'reports.export',
                    'reports.scheduled.view', 'reports.scheduled.create', 'reports.scheduled.edit',
                    'analytics.view', 'analytics.export',
                    'staff.view', 'staff.create', 'staff.edit', 'staff.export',
                    'attendance.view', 'attendance.record', 'attendance.edit', 'attendance.export',
                    'leave.view', 'leave.approve', 'leave.reject',
                    'payroll.view', 'payroll.process', 'payroll.export',
                    'salary-advances.view', 'salary-advances.approve',
                    'salary-payments.view', 'salary-payments.process',
                    'schedules.view', 'schedules.create', 'schedules.edit',
                    'collection-sessions.view', 'collection-sessions.create', 'collection-sessions.complete',
                    'vehicles.view', 'vehicles.create', 'vehicles.edit',
                    'vehicle-maintenance.view', 'vehicle-maintenance.create', 'vehicle-maintenance.complete',
                    'bulk-import.view', 'bulk-import.create', 'bulk-import.rollback',
                    'settings.manage',
                    'users.view', 'users.create', 'users.edit',
                    'roles.view', 'roles.create', 'roles.edit',
                    'permissions.view',
                    'audit.view', 'audit.export',
                ]),
            ],
            'accountant' => [
                'description' => 'Handles finance, invoices and reconciliation.',
                'permissions' => $this->idsFor($permissionIds, [
                    'clients.view',
                    'payments.view', 'payments.export', 'payments.reconcile',
                    'invoices.view', 'invoices.export', 'invoices.send',
                    'debts.view', 'debts.export',
                    'expenses.view', 'expenses.create', 'expenses.edit', 'expenses.approve', 'expenses.export',
                    'expense-categories.view', 'expense-categories.create', 'expense-categories.edit',
                    'banking.view', 'banking.record', 'banking.confirm', 'banking.reconcile', 'banking.export',
                    'bank-accounts.view', 'bank-accounts.create', 'bank-accounts.edit',
                    'budgets.view', 'budgets.create', 'budgets.edit',
                    'reports.monthly', 'reports.yearly', 'reports.export',
                    'analytics.view', 'analytics.export',
                    'settings.manage',
                    'audit.view',
                ]),
            ],
            'collector' => [
                'description' => 'Field collector recording payments and attendance.',
                'permissions' => $this->idsFor($permissionIds, [
                    'clients.view',
                    'payments.view', 'payments.record',
                    'invoices.view',
                    'attendance.view', 'attendance.record',
                    'collection-sessions.view', 'collection-sessions.create', 'collection-sessions.complete',
                    'schedules.view',
                ]),
            ],
            'viewer' => [
                'description' => 'Read-only access to core records.',
                'permissions' => $this->idsFor($permissionIds, [
                    'clients.view',
                    'payments.view',
                    'invoices.view',
                    'reports.monthly',
                    'analytics.view',
                    'staff.view',
                    'attendance.view',
                ]),
            ],
        ];

        foreach ($roles as $name => $config) {
            $role = Role::updateOrCreate(
                ['name' => $name],
                ['description' => $config['description']],
            );
            $role->permissions()->sync($config['permissions']);
        }
    }

    /**
     * Map permission names to their ids.
     *
     * @param  array<string, int>  $permissionIds
     * @param  array<int, string>  $names
     * @return array<int, int>
     */
    private function idsFor(array $permissionIds, array $names): array
    {
        return collect($names)
            ->map(fn (string $name) => $permissionIds[$name] ?? null)
            ->filter()
            ->values()
            ->all();
    }
}