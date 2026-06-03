<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::withCount('users')->with('permissions')->get();
        $permissionGroups = Permission::all()->groupBy('group');

        return Inertia::render('Roles/Index', [
            'roles' => $roles,
            'permissionGroups' => $permissionGroups,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:roles',
            'description' => 'nullable|string',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        DB::beginTransaction();
        try {
            $role = Role::create($request->only('name', 'description'));
            if ($request->has('permissions')) {
                $role->permissions()->sync($request->permissions);
            }
            AuditLog::log('role.create', 'Role', $role->id, $request->only('name'));
            DB::commit();
            return back()->with('success', 'Role created.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:roles,name,'.$role->id,
            'description' => 'nullable|string',
            'permissions' => 'array',
        ]);

        DB::beginTransaction();
        try {
            $role->update($request->only('name', 'description'));
            $role->permissions()->sync($request->permissions ?? []);
            AuditLog::log('role.update', 'Role', $role->id, $request->all());
            DB::commit();
            return back()->with('success', 'Role updated.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function destroy(Role $role)
    {
        if ($role->users()->exists()) {
            return back()->with('error', 'Cannot delete role with assigned users.');
        }
        $role->delete();
        return back()->with('success', 'Role deleted.');
    }

    public function clone(Role $role, Request $request)
    {
        $request->validate(['new_name' => 'required|string|unique:roles,name']);

        DB::beginTransaction();
        try {
            $newRole = $role->replicate();
            $newRole->name = $request->new_name;
            $newRole->save();
            $newRole->permissions()->sync($role->permissions->pluck('id'));
            AuditLog::log('role.clone', 'Role', $newRole->id, ['source_role_id' => $role->id]);
            DB::commit();
            return back()->with('success', 'Role cloned.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function seedDefaults()
    {
        $defaults = [
            'super-admin' => ['all' => true],
            'manager' => ['view_reports', 'approve_expense', 'manage_staff'],
            'collector' => ['record_payment', 'view_my_routes', 'clock_in_out'],
            'accountant' => ['manage_transactions', 'reconcile_bank', 'export_financials'],
        ];

        foreach ($defaults as $roleName => $perms) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            if ($perms['all'] ?? false) {
                $role->permissions()->sync(Permission::pluck('id'));
            } else {
                $permIds = Permission::whereIn('name', $perms)->pluck('id');
                $role->permissions()->sync($permIds);
            }
        }

        return back()->with('success', 'Default roles seeded.');
    }
}