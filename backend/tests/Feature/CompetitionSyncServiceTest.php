<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\Player;
use App\Models\Team;
use App\Services\CompetitionSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompetitionSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_syncs_a_competition_without_creating_duplicate_rows(): void
    {
        $service = new CompetitionSyncService;
        $payload = [
            'id' => 2024,
            'name' => 'Premier League',
            'code' => 'PL',
            'area' => ['name' => 'England'],
            'emblem' => 'https://example.test/emblem.png',
            'currentSeason' => ['startDate' => '2024-08-01'],
            'teams' => [
                [
                    'id' => 1001,
                    'name' => 'Arsenal FC',
                    'shortName' => 'ARS',
                    'tla' => 'ARS',
                    'crest' => 'https://example.test/arsenal.png',
                    'squad' => [
                        [
                            'id' => 5001,
                            'name' => 'Bukayo Saka',
                            'position' => 'Midfielder',
                            'shirtNumber' => 7,
                        ],
                    ],
                ],
            ],
        ];

        $service->syncCompetition($payload);
        $service->syncCompetition($payload);

        $this->assertSame(1, Competition::query()->where('external_id', 2024)->count());
        $this->assertSame(1, Team::query()->where('external_id', 1001)->count());
        $this->assertSame(1, Player::query()->where('external_id', 5001)->count());
        $this->assertSame(1, Competition::findOrFail(1)->teams()->count());
    }

    public function test_it_updates_an_existing_player_when_the_player_is_imported_with_another_team(): void
    {
        $service = new CompetitionSyncService;

        $service->syncCompetition([
            'id' => 2024,
            'name' => 'Premier League',
            'teams' => [
                [
                    'id' => 1001,
                    'name' => 'Arsenal FC',
                    'squad' => [
                        ['id' => 5001, 'name' => 'Player Name', 'position' => 'Midfielder'],
                    ],
                ],
            ],
        ]);

        $service->syncCompetition([
            'id' => 2025,
            'name' => 'La Liga',
            'teams' => [
                [
                    'id' => 1002,
                    'name' => 'FC Barcelona',
                    'squad' => [
                        ['id' => 5001, 'name' => 'Updated Player Name', 'position' => 'Defence'],
                    ],
                ],
            ],
        ]);

        $player = Player::query()->where('external_id', 5001)->sole();

        $this->assertSame(1, Player::query()->where('external_id', 5001)->count());
        $this->assertSame(1002, $player->team->external_id);
        $this->assertSame('Updated Player Name', $player->name);
        $this->assertSame('Defence', $player->position);
    }

    public function test_deleting_a_team_keeps_players_without_an_orphaned_team_reference(): void
    {
        $team = Team::create([
            'external_id' => 1001,
            'name' => 'Arsenal FC',
        ]);
        $player = Player::create([
            'external_id' => 5001,
            'team_id' => $team->id,
            'name' => 'Bukayo Saka',
        ]);

        $team->delete();

        $this->assertDatabaseHas('players', [
            'id' => $player->id,
            'team_id' => null,
        ]);
    }
}
