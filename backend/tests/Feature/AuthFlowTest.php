<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Client;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Client::forceCreate([
            'name' => 'Personal Access Client',
            'secret' => 'test-secret',
            'provider' => null,
            'redirect_uris' => ['http://localhost'],
            'grant_types' => ['personal_access', 'refresh_token'],
            'revoked' => false,
            'owner_id' => null,
            'owner_type' => null,
        ]);
    }

    public function test_user_can_log_in_and_receive_a_token(): void
    {
        $role = Role::create(['name' => 'Administrador', 'slug' => 'admin']);
        $permission = Permission::create(['name' => 'Usuarios ver', 'slug' => 'users.view']);
        $role->permissions()->sync([$permission->id]);

        $user = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.test',
            'password' => 'secret1234',
            'is_active' => true,
        ]);
        $user->roles()->sync([$role->id]);

        $response = $this->postJson('/api/login', [
            'email' => 'admin@example.test',
            'password' => 'secret1234',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.user.email', 'admin@example.test')
            ->assertJsonPath('data.user.roles.0', 'admin');

        $this->assertNotEmpty($response->json('data.token'));
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        User::create([
            'name' => 'Inactive User',
            'email' => 'inactive@example.test',
            'password' => 'secret1234',
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'inactive@example.test',
            'password' => 'secret1234',
        ]);

        $response->assertStatus(422);
    }

    public function test_unauthenticated_api_errors_have_a_consistent_json_shape(): void
    {
        $this->getJson('/api/me')
            ->assertUnauthorized()
            ->assertJsonStructure([
                'error' => ['code', 'message', 'details'],
            ])
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }

    public function test_logout_revokes_the_current_access_token(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'logout@example.test',
            'password' => 'secret1234',
            'is_active' => true,
        ]);
        $token = $user->createToken('logout-test')->accessToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/logout')
            ->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/me')
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }

    public function test_read_only_user_cannot_manage_users(): void
    {
        $adminRole = Role::create(['name' => 'Administrador', 'slug' => 'admin']);
        $readRole = Role::create(['name' => 'Solo consulta', 'slug' => 'read-only']);

        Permission::firstOrCreate(['slug' => 'users.view'], ['name' => 'Users View']);
        Permission::firstOrCreate(['slug' => 'users.create'], ['name' => 'Users Create']);
        Permission::firstOrCreate(['slug' => 'users.update'], ['name' => 'Users Update']);
        Permission::firstOrCreate(['slug' => 'users.deactivate'], ['name' => 'Users Deactivate']);

        $adminRole->permissions()->sync(Permission::whereIn('slug', ['users.view', 'users.create', 'users.update', 'users.deactivate'])->pluck('id'));

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin2@example.test',
            'password' => 'secret1234',
            'is_active' => true,
        ]);
        $admin->roles()->sync([$adminRole->id]);

        $reader = User::create([
            'name' => 'Reader',
            'email' => 'reader2@example.test',
            'password' => 'secret1234',
            'is_active' => true,
        ]);
        $reader->roles()->sync([$readRole->id]);

        $adminToken = $admin->createToken('admin-test')->accessToken;
        $readerToken = $reader->createToken('reader-test')->accessToken;

        $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->getJson('/api/users')
            ->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$readerToken)
            ->getJson('/api/users')
            ->assertStatus(403);
    }

    public function test_deactivating_a_user_revokes_existing_tokens(): void
    {
        $adminRole = Role::create(['name' => 'Administrador', 'slug' => 'admin']);
        $readerRole = Role::create(['name' => 'Solo consulta', 'slug' => 'read-only']);
        $deactivatePermission = Permission::create([
            'name' => 'Desactivar usuarios',
            'slug' => 'users.deactivate',
        ]);
        $viewPermission = Permission::create([
            'name' => 'Ver competiciones',
            'slug' => 'competitions.view',
        ]);
        $adminRole->permissions()->sync([$deactivatePermission->id]);
        $readerRole->permissions()->sync([$viewPermission->id]);

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'deactivate-admin@example.test',
            'password' => 'secret1234',
            'is_active' => true,
        ]);
        $admin->roles()->sync([$adminRole->id]);
        $reader = User::create([
            'name' => 'Reader',
            'email' => 'deactivate-reader@example.test',
            'password' => 'secret1234',
            'is_active' => true,
        ]);
        $reader->roles()->sync([$readerRole->id]);

        $adminToken = $admin->createToken('admin-test')->accessToken;
        $readerToken = $reader->createToken('reader-test')->accessToken;

        $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->patchJson('/api/users/'.$reader->id.'/status')
            ->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->withHeader('Authorization', 'Bearer '.$readerToken)
            ->getJson('/api/me')
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');

        $this->assertDatabaseMissing('oauth_access_tokens', [
            'user_id' => $reader->id,
        ]);
    }
}
