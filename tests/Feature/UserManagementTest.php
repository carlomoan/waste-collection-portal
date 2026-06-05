<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function actingAdmin(): User
    {
        $admin = User::factory()->create();
        $role = Role::create(['name' => 'super_admin', 'description' => 'Admin']);
        $admin->roles()->attach($role->id);
        $this->actingAs($admin);

        return $admin;
    }

    public function test_index_page_renders_for_authenticated_user(): void
    {
        $this->actingAdmin();

        $this->get('/users')->assertOk();
    }

    public function test_guest_is_redirected_from_users_index(): void
    {
        $this->get('/users')->assertRedirect('/login');
    }

    public function test_a_user_can_be_created_with_roles(): void
    {
        $this->actingAdmin();
        $role = Role::create(['name' => 'collector']);

        $this->post('/users', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'username' => 'jane',
            'phone' => '255700000000',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'is_active' => true,
            'roles' => [$role->id],
        ])->assertSessionHasNoErrors();

        $user = User::where('email', 'jane@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->roles->contains($role));
    }

    public function test_creating_a_user_validates_unique_email(): void
    {
        $this->actingAdmin();
        User::factory()->create(['email' => 'dup@example.com']);

        $this->post('/users', [
            'name' => 'Dup',
            'email' => 'dup@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ])->assertSessionHasErrors('email');
    }

    public function test_a_user_can_be_updated(): void
    {
        $this->actingAdmin();
        $user = User::factory()->create(['name' => 'Old Name']);

        $this->patch("/users/{$user->id}", [
            'name' => 'New Name',
            'email' => $user->email,
            'is_active' => true,
        ])->assertSessionHasNoErrors();

        $this->assertSame('New Name', $user->fresh()->name);
    }

    public function test_roles_can_be_synced(): void
    {
        $this->actingAdmin();
        $user = User::factory()->create();
        $role = Role::create(['name' => 'manager']);

        $this->patch("/users/{$user->id}/roles", ['roles' => [$role->id]])
            ->assertSessionHasNoErrors();

        $this->assertTrue($user->fresh()->roles->contains($role));
    }

    public function test_password_can_be_reset(): void
    {
        $this->actingAdmin();
        $user = User::factory()->create();

        $this->patch("/users/{$user->id}/reset-password", [
            'password' => 'BrandNew123',
            'password_confirmation' => 'BrandNew123',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('BrandNew123', $user->fresh()->password));
    }

    public function test_status_can_be_toggled(): void
    {
        $this->actingAdmin();
        $user = User::factory()->create(['is_active' => true]);

        $this->patch("/users/{$user->id}/toggle-status");

        $this->assertFalse($user->fresh()->is_active);
    }

    public function test_user_cannot_toggle_own_status(): void
    {
        $admin = $this->actingAdmin();

        $this->patch("/users/{$admin->id}/toggle-status")
            ->assertSessionHas('error');

        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_a_user_can_be_deleted(): void
    {
        $this->actingAdmin();
        $user = User::factory()->create();

        $this->delete("/users/{$user->id}")->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_user_cannot_delete_own_account(): void
    {
        $admin = $this->actingAdmin();

        $this->delete("/users/{$admin->id}")->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_users_can_be_filtered_by_search(): void
    {
        $this->actingAdmin();
        User::factory()->create(['name' => 'Findable Person', 'email' => 'findable@example.com']);

        $this->get('/users?search=Findable')->assertOk();
    }
}
