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
            ],
            'Payments' => [
                'payments.view' => 'View payments',
                'payments.record' => 'Record payments',
                'payments.reverse' => 'Reverse payments',
            ],
            'Invoices' => [
                'invoices.view' => 'View invoices',
                'invoices.generate' => 'Generate invoices',
                'invoices.export' => 'Export invoices',
            ],
            'Finance' => [
                'expenses.view' => 'View expenses',
                'expenses.create' => 'Create expenses',
                'expenses.approve' => 'Approve expenses',
                'banking.view' => 'View banking',
                'banking.record' => 'Record banking',
            ],
            'Reports' => [
                'reports.monthly' => 'View monthly reports',
                'reports.yearly' => 'View yearly reports',
                'reports.export' => 'Export reports',
            ],
            'HR' => [
                'staff.view' => 'View staff',
                'staff.manage' => 'Manage staff',
                'attendance.view' => 'View attendance',
                'attendance.manage' => 'Manage attendance',
            ],
            'System' => [
                'settings.manage' => 'Manage settings',
                'users.manage' => 'Manage users',
                'roles.manage' => 'Manage roles',
                'audit.view' => 'View audit log',
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
                    'clients.view', 'clients.create', 'clients.edit',
                    'payments.view', 'invoices.view', 'invoices.generate',
                    'expenses.view', 'expenses.approve', 'banking.view',
                    'reports.monthly', 'reports.yearly', 'reports.export',
                    'staff.view', 'attendance.view', 'attendance.manage',
                ]),
            ],
            'accountant' => [
                'description' => 'Handles finance, invoices and reconciliation.',
                'permissions' => $this->idsFor($permissionIds, [
                    'clients.view', 'payments.view', 'invoices.view', 'invoices.export',
                    'expenses.view', 'expenses.create', 'banking.view', 'banking.record',
                    'reports.monthly', 'reports.yearly', 'reports.export',
                ]),
            ],
            'collector' => [
                'description' => 'Field collector recording payments and attendance.',
                'permissions' => $this->idsFor($permissionIds, [
                    'clients.view', 'payments.view', 'payments.record',
                    'invoices.view', 'attendance.view',
                ]),
            ],
            'viewer' => [
                'description' => 'Read-only access to core records.',
                'permissions' => $this->idsFor($permissionIds, [
                    'clients.view', 'payments.view', 'invoices.view', 'reports.monthly',
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
