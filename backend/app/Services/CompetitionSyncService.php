<?php

namespace App\Services;

use App\Models\Competition;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Support\Facades\DB;

class CompetitionSyncService
{
    public function syncCompetitionList(array $items): void
    {
        foreach ($items as $item) {
            $this->syncCompetition($item);
        }
    }

    public function syncCompetition(array $payload): ?Competition
    {
        $externalId = $payload['id'] ?? null;

        if ($externalId === null || $externalId === '') {
            return null;
        }

        return DB::transaction(function () use ($payload, $externalId) {
            $competition = Competition::updateOrCreate(
                ['external_id' => (int) $externalId],
                [
                    'name' => $payload['name'] ?? 'Unknown',
                    'code' => $payload['code'] ?? null,
                    'area_name' => $payload['area']['name'] ?? null,
                    'emblem' => $payload['emblem'] ?? null,
                    'season' => $payload['currentSeason']['startDate'] ?? null,
                    'last_synced_at' => now(),
                ]
            );

            foreach ($payload['teams'] ?? [] as $teamPayload) {
                $teamExternalId = $teamPayload['id'] ?? null;

                if ($teamExternalId === null || $teamExternalId === '') {
                    continue;
                }

                $team = Team::updateOrCreate(
                    ['external_id' => (int) $teamExternalId],
                    [
                        'name' => $teamPayload['name'] ?? 'Unknown team',
                        'short_name' => $teamPayload['shortName'] ?? null,
                        'tla' => $teamPayload['tla'] ?? null,
                        'crest' => $teamPayload['crest'] ?? null,
                    ]
                );

                $competition->teams()->syncWithoutDetaching([$team->id]);

                foreach ($teamPayload['squad'] ?? [] as $playerPayload) {
                    $playerExternalId = $playerPayload['id'] ?? null;

                    if ($playerExternalId === null || $playerExternalId === '') {
                        continue;
                    }

                    Player::updateOrCreate(
                        ['external_id' => (int) $playerExternalId],
                        [
                            'team_id' => $team->id,
                            'name' => $playerPayload['name'] ?? 'Unknown player',
                            'position' => $playerPayload['position'] ?? null,
                            'shirt_number' => $playerPayload['shirtNumber'] ?? null,
                        ]
                    );
                }
            }

            return $competition->refresh();
        });
    }
}
