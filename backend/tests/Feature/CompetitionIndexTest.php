<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Competition;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Passport\Client;
use Tests\TestCase;

class CompetitionIndexTest extends TestCase
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

    public function test_it_reports_when_the_provider_is_not_configured_and_no_local_competitions_exist(): void
    {
        config(['services.football_data.token' => null]);

        $role = Role::create(['name' => 'Solo consulta', 'slug' => 'read-only']);
        $permission = Permission::create(['name' => 'Ver competiciones', 'slug' => 'competitions.view']);
        $role->permissions()->sync([$permission->id]);

        $user = User::create([
            'name' => 'Reader',
            'email' => 'reader@example.test',
            'password' => 'secret1234',
            'is_active' => true,
        ]);
        $user->roles()->sync([$role->id]);

        $token = $user->createToken('competition-index-test')->accessToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/competitions')
            ->assertOk()
            ->assertJsonPath('data', [])
            ->assertJsonPath('meta.total', 0)
            ->assertJsonPath('meta.warning', 'football-data.org no está configurado; no hay competiciones locales para mostrar.');
    }

    public function test_it_only_lists_competitions_available_to_the_configured_subscription(): void
    {
        Cache::flush();
        config([
            'services.football_data.base_url' => 'https://api.football-data.org/v4',
            'services.football_data.token' => 'test-token',
        ]);
        Http::fake([
            'https://api.football-data.org/v4/competitions' => Http::response([
                'competitions' => [
                    [
                        'id' => 2021,
                        'name' => 'Premier League',
                        'code' => 'PL',
                        'area' => ['name' => 'England'],
                    ],
                ],
            ]),
        ]);

        Competition::create(['external_id' => 2021, 'name' => 'Premier League', 'code' => 'PL']);
        Competition::create(['external_id' => 2193, 'name' => 'Restricted Competition', 'code' => 'RC']);

        $role = Role::create(['name' => 'Solo consulta', 'slug' => 'read-only']);
        $permission = Permission::create(['name' => 'Ver competiciones', 'slug' => 'competitions.view']);
        $role->permissions()->sync([$permission->id]);
        $user = User::create([
            'name' => 'Reader',
            'email' => 'reader@example.test',
            'password' => 'secret1234',
            'is_active' => true,
        ]);
        $user->roles()->sync([$role->id]);
        $token = $user->createToken('competition-list-test')->accessToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/competitions')
            ->assertOk()
            ->assertJsonPath('meta.source', 'provider')
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.external_id', 2021);
    }

    public function test_it_returns_a_provider_rate_limit_error_when_no_local_catalog_exists(): void
    {
        Cache::flush();
        config([
            'services.football_data.base_url' => 'https://api.football-data.org/v4',
            'services.football_data.token' => 'test-token',
        ]);
        Http::fake([
            'https://api.football-data.org/v4/competitions' => Http::response([], 429),
        ]);

        $role = Role::create(['name' => 'Solo consulta', 'slug' => 'read-only']);
        $permission = Permission::create(['name' => 'Ver competiciones', 'slug' => 'competitions.view']);
        $role->permissions()->sync([$permission->id]);
        $user = User::create([
            'name' => 'Reader',
            'email' => 'rate-limit-reader@example.test',
            'password' => 'secret1234',
            'is_active' => true,
        ]);
        $user->roles()->sync([$role->id]);
        $token = $user->createToken('competition-rate-limit-test')->accessToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/competitions')
            ->assertStatus(429)
            ->assertJsonPath('error.code', 'RATE_LIMIT_EXCEEDED');
    }

    public function test_it_keeps_local_competitions_available_when_the_provider_fails(): void
    {
        Cache::flush();
        config([
            'services.football_data.base_url' => 'https://api.football-data.org/v4',
            'services.football_data.token' => 'test-token',
        ]);
        Http::fake([
            'https://api.football-data.org/v4/competitions' => Http::response([], 503),
        ]);

        Competition::create(['external_id' => 2021, 'name' => 'Premier League', 'code' => 'PL']);
        $role = Role::create(['name' => 'Solo consulta', 'slug' => 'read-only']);
        $permission = Permission::create(['name' => 'Ver competiciones', 'slug' => 'competitions.view']);
        $role->permissions()->sync([$permission->id]);
        $user = User::create([
            'name' => 'Reader',
            'email' => 'provider-failure-reader@example.test',
            'password' => 'secret1234',
            'is_active' => true,
        ]);
        $user->roles()->sync([$role->id]);
        $token = $user->createToken('competition-provider-failure-test')->accessToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/competitions')
            ->assertOk()
            ->assertJsonPath('meta.source', 'local')
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.name', 'Premier League')
            ->assertJsonStructure(['meta' => ['warning']]);
    }
}
