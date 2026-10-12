<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\Permission;
use App\Models\Player;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Passport\Client;
use Tests\TestCase;

class CompetitionImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
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

    public function test_competition_detail_imports_teams_and_players_from_the_teams_endpoint(): void
    {
        config([
            'services.football_data.base_url' => 'https://api.football-data.org/v4',
            'services.football_data.token' => 'test-token',
        ]);
        Http::fake([
            'https://api.football-data.org/v4/competitions/2021' => Http::response([
                'id' => 2021,
                'name' => 'Premier League',
                'code' => 'PL',
                'area' => ['name' => 'England'],
                'currentSeason' => ['startDate' => '2025-08-01'],
            ]),
            'https://api.football-data.org/v4/competitions/2021/teams' => Http::response([
                'teams' => [
                    [
                        'id' => 1001,
                        'name' => 'Arsenal FC',
                        'shortName' => 'Arsenal',
                        'tla' => 'ARS',
                        'crest' => 'https://example.test/arsenal.png',
                        'squad' => [
                            [
                                'id' => 5001,
                                'name' => 'Bukayo Saka',
                                'position' => 'Forward',
                                'shirtNumber' => 7,
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $competition = Competition::create([
            'external_id' => 2021,
            'name' => 'Premier League',
            'code' => 'PL',
        ]);

        $role = Role::create(['name' => 'Administrador', 'slug' => 'admin']);
        $viewPermission = Permission::create(['name' => 'Ver competiciones', 'slug' => 'competitions.view']);
        $importPermission = Permission::create(['name' => 'Importar competiciones', 'slug' => 'competitions.import']);
        $role->permissions()->sync([$viewPermission->id, $importPermission->id]);
        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'password' => 'secret1234',
            'is_active' => true,
        ]);
        $user->roles()->sync([$role->id]);

        $token = $user->createToken('competition-import-test')->accessToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/competitions/'.$competition->id)
            ->assertOk()
            ->assertJsonPath('meta.source', 'provider')
            ->assertJsonPath('data.teams.0.name', 'Arsenal FC')
            ->assertJsonPath('data.teams.0.players.0.name', 'Bukayo Saka')
            ->assertJsonPath('data.teams.0.players.0.shirt_number', 7);

        $this->assertSame(1, Team::query()->where('external_id', 1001)->count());
        $this->assertSame(1, Player::query()->where('external_id', 5001)->count());
        Http::assertSentCount(2);
    }

    public function test_read_only_users_get_local_competition_details_without_importing(): void
    {
        config([
            'services.football_data.base_url' => 'https://api.football-data.org/v4',
            'services.football_data.token' => 'test-token',
        ]);
        Http::fake();

        $competition = Competition::create([
            'external_id' => 2021,
            'name' => 'Premier League',
            'code' => 'PL',
        ]);
        $role = Role::create(['name' => 'Solo consulta', 'slug' => 'read-only']);
        $viewPermission = Permission::create(['name' => 'Ver competiciones', 'slug' => 'competitions.view']);
        $role->permissions()->sync([$viewPermission->id]);
        $user = User::create([
            'name' => 'Reader',
            'email' => 'reader-import@example.test',
            'password' => 'secret1234',
            'is_active' => true,
        ]);
        $user->roles()->sync([$role->id]);
        $token = $user->createToken('read-only-competition-detail')->accessToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/competitions/'.$competition->id)
            ->assertOk()
            ->assertJsonPath('meta.source', 'local')
            ->assertJsonPath('data.name', 'Premier League')
            ->assertJsonPath('data.teams', []);

        Http::assertNothingSent();
    }
}
