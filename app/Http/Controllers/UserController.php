<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'role', 'status']);

        $users = User::query()
            ->with(['roles:id,name'])
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('nida_id', 'like', "%{$search}%");
                });
            })
            ->when($filters['role'] ?? null, function ($query, string $role) {
                $query->whereHas('roles', fn ($q) => $q->where('name', $role));
            })
            ->when(isset($filters['status']) && $filters['status'] !== '', function ($query) use ($filters) {
                $query->where('is_active', $filters['status'] === 'active');
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'nida_id' => $user->nida_id,
                'phone' => $user->phone,
                'is_active' => $user->is_active,
                'last_login_at' => $user->last_login_at?->toDateTimeString(),
                'created_at' => $user->created_at?->toDateString(),
                'roles' => $user->roles->map(fn (Role $role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                ]),
            ]);

        return Inertia::render('Users/Index', [
            'users' => $users,
            'filters' => $filters,
            'roles' => Role::orderBy('name')->get(['id', 'name', 'description']),
            'stats' => [
                'total' => User::count(),
                'active' => User::where('is_active', true)->count(),
                'inactive' => User::where('is_active', false)->count(),
                'roles' => Role::count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'username' => ['nullable', 'string', 'max:255', 'unique:users,username'],
            'nida_id' => ['nullable', 'string', 'max:50', 'unique:users,nida_id'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'is_active' => ['boolean'],
            'roles' => ['array'],
            'roles.*' => ['integer', 'exists:roles,id'],
        ]);

        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'username' => $validated['username'] ?? null,
                'nida_id' => $validated['nida_id'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'password' => Hash::make($validated['password']),
                'is_active' => $validated['is_active'] ?? true,
            ]);

            $user->syncRoles($validated['roles'] ?? []);

            AuditLog::log('user.create', 'User', $user->id, [
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $validated['roles'] ?? [],
            ]);

            DB::commit();

            return back()->with('success', 'User created successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Failed to create user: '.$e->getMessage());
        }
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'username' => ['nullable', 'string', 'max:255', Rule::unique('users', 'username')->ignore($user->id)],
            'nida_id' => ['nullable', 'string', 'max:50', Rule::unique('users', 'nida_id')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'is_active' => ['boolean'],
            'roles' => ['array'],
            'roles.*' => ['integer', 'exists:roles,id'],
        ]);

        DB::beginTransaction();
        try {
            $old = $user->only(['name', 'email', 'username', 'nida_id', 'phone', 'is_active']);

            $user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'username' => $validated['username'] ?? null,
                'nida_id' => $validated['nida_id'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'is_active' => $validated['is_active'] ?? $user->is_active,
            ]);

            if (array_key_exists('roles', $validated)) {
                $user->syncRoles($validated['roles']);
            }

            AuditLog::log('user.update', 'User', $user->id, $validated, $old);

            DB::commit();

            return back()->with('success', 'User updated successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Failed to update user: '.$e->getMessage());
        }
    }

    public function updateRoles(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'roles' => ['array'],
            'roles.*' => ['integer', 'exists:roles,id'],
        ]);

        $user->syncRoles($validated['roles'] ?? []);
        AuditLog::log('user.roles.update', 'User', $user->id, ['roles' => $validated['roles'] ?? []]);

        return back()->with('success', 'Roles updated successfully.');
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user->update(['password' => Hash::make($validated['password'])]);
        AuditLog::log('user.password.reset', 'User', $user->id);

        return back()->with('success', 'Password reset successfully.');
    }

    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot change the status of your own account.');
        }

        $user->update(['is_active' => ! $user->is_active]);
        AuditLog::log('user.status.toggle', 'User', $user->id, ['is_active' => $user->is_active]);

        return back()->with('success', $user->is_active ? 'User activated.' : 'User deactivated.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        if ($user->hasRole('super_admin') && $this->lastSuperAdmin($user)) {
            return back()->with('error', 'Cannot delete the last super admin.');
        }

        DB::beginTransaction();
        try {
            $user->roles()->detach();
            AuditLog::log('user.delete', 'User', $user->id, ['email' => $user->email]);
            $user->delete();

            DB::commit();

            return back()->with('success', 'User deleted successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Failed to delete user: '.$e->getMessage());
        }
    }

    private function lastSuperAdmin(User $user): bool
    {
        return User::whereHas('roles', fn ($q) => $q->where('name', 'super_admin'))
            ->where('id', '!=', $user->id)
            ->doesntExist();
    }
}
