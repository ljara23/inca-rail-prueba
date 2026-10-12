<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Player;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Client;
use Tests\TestCase;

class PlayerDetailTest extends TestCase
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

    public function test_a_user_with_permission_can_view_a_local_player_detail(): void
    {
        $team = Team::create([
            'external_id' => 1001,
            'name' => 'Arsenal FC',
        ]);
        $player = Player::create([
            'external_id' => 5001,
            'team_id' => $team->id,
            'name' => 'Bukayo Saka',
            'position' => 'Forward',
            'shirt_number' => 7,
        ]);
        $role = Role::create(['name' => 'Solo consulta', 'slug' => 'read-only']);
        $permission = Permission::create(['name' => 'Ver jugadores', 'slug' => 'players.view']);
        $role->permissions()->sync([$permission->id]);
        $user = User::create([
            'name' => 'Reader',
            'email' => 'player-reader@example.test',
            'password' => 'secret1234',
            'is_active' => true,
        ]);
        $user->roles()->sync([$role->id]);
        $token = $user->createToken('player-detail-test')->accessToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/players/'.$player->id)
            ->assertOk()
            ->assertJsonPath('data.name', 'Bukayo Saka')
            ->assertJsonPath('data.position', 'Forward')
            ->assertJsonPath('data.shirt_number', 7)
            ->assertJsonPath('data.team.id', $team->id);
    }

    public function test_a_missing_player_returns_a_formatted_404(): void
    {
        $role = Role::create(['name' => 'Solo consulta', 'slug' => 'read-only']);
        $permission = Permission::create(['name' => 'Ver jugadores', 'slug' => 'players.view']);
        $role->permissions()->sync([$permission->id]);
        $user = User::create([
            'name' => 'Reader',
            'email' => 'missing-player-reader@example.test',
            'password' => 'secret1234',
            'is_active' => true,
        ]);
        $user->roles()->sync([$role->id]);
        $token = $user->createToken('missing-player-test')->accessToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/players/999')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'RESOURCE_NOT_FOUND');
    }
}
