<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class RoleController extends Controller
{
    public function index()
    {
        try {
            // Get roles with user count and permissions
            $roles = Role::with(['users', 'permissions'])
                ->get()
                ->map(function ($role) {
                    return [
                        'id' => $role->id ?? null,
                        'name' => $role->name ?? 'Unknown',
                        'users_count' => $role->users?->count() ?? 0,
                        'description' => $role->description ?? '',
                        'permissions' => $role->permissions?->pluck('name')->toArray() ?? [],
                    ];
                });

            return Inertia::render('Roles/Index', [
                'roles' => $roles,
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load roles: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:roles',
            'description' => 'nullable|string|max:500',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $role = Role::create([
                'name' => $request->name,
                'description' => $request->description,
            ]);

            if ($request->has('permissions')) {
                $role->permissions()->sync($request->permissions);
            }

            return back()->with('success', 'Role created successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to create role: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:roles,name,' . $id,
            'description' => 'nullable|string|max:500',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $role = Role::findOrFail($id);
            $role->update([
                'name' => $request->name,
                'description' => $request->description,
            ]);

            if ($request->has('permissions')) {
                $role->permissions()->sync($request->permissions);
            }

            return back()->with('success', 'Role updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update role: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $role = Role::findOrFail($id);
            
            // Prevent deletion if role has users
            if ($role->users()->count() > 0) {
                return back()->with('error', 'Cannot delete role with assigned users.');
            }

            $role->delete();

            return back()->with('success', 'Role deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete role: ' . $e->getMessage());
        }
    }

    public function assign(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'role_id' => 'required|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $user = \App\Models\User::findOrFail($request->user_id);
            $role = Role::findOrFail($request->role_id);
            
            $user->roles()->syncWithoutDetaching([$role->id]);

            return back()->with('success', 'Role assigned successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to assign role: ' . $e->getMessage());
        }
    }

    public function revoke(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'role_id' => 'required|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $user = \App\Models\User::findOrFail($request->user_id);
            $role = Role::findOrFail($request->role_id);
            
            $user->roles()->detach($role->id);

            return back()->with('success', 'Role revoked successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to revoke role: ' . $e->getMessage());
        }
    }
}
