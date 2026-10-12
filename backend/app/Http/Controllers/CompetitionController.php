<?php

namespace App\Http\Controllers;

use App\Http\Resources\CompetitionResource;
use App\Models\Competition;
use App\Services\CompetitionSyncService;
use App\Services\FootballData\FootballDataClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class CompetitionController extends Controller
{
    public function __construct(
        protected FootballDataClient $footballDataClient,
        protected CompetitionSyncService $competitionSyncService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'competitions.view');

        $competitions = Competition::query()->with('teams')->paginate((int) $request->query('per_page', 15));
        $warning = null;
        $source = 'local';

        if ($this->footballDataClient->isConfigured()) {
            try {
                $payload = $this->footballDataClient->fetchCompetitions();
                $availableCompetitions = array_values(array_filter(
                    $payload['competitions'] ?? [],
                    fn (array $item) => isset($item['id'])
                ));

                if ($availableCompetitions !== []) {
                    $this->competitionSyncService->syncCompetitionList($availableCompetitions);
                    $externalIds = array_column($availableCompetitions, 'id');
                    $competitions = Competition::query()
                        ->whereIn('external_id', $externalIds)
                        ->with('teams')
                        ->paginate((int) $request->query('per_page', 15));
                    $source = 'provider';
                } else {
                    $warning = 'football-data.org no devolvió competiciones disponibles; se muestran los datos locales.';
                }
            } catch (RuntimeException $exception) {
                $warning = $exception->getMessage();

                if ($competitions->total() === 0) {
                    return $this->apiError(
                        $exception->getCode() === 429 ? 'RATE_LIMIT_EXCEEDED' : 'FOOTBALL_DATA_UNAVAILABLE',
                        $exception->getMessage(),
                        in_array($exception->getCode(), [429, 503], true) ? $exception->getCode() : 503,
                    );
                }
            }
        } elseif ($competitions->isEmpty()) {
            $warning = 'football-data.org no está configurado; no hay competiciones locales para mostrar.';
        }

        return response()->json([
            'data' => CompetitionResource::collection($competitions),
            'meta' => [
                'current_page' => $competitions->currentPage(),
                'last_page' => $competitions->lastPage(),
                'total' => $competitions->total(),
                'warning' => $warning,
                'source' => $source,
            ],
        ]);
    }

    public function show(Request $request, Competition $competition): JsonResponse
    {
        $this->requirePermission($request, 'competitions.view');

        $meta = ['source' => 'local'];

        if ($competition->external_id && $request->user('api')->hasPermission('competitions.import')) {
            try {
                $remoteCompetition = $this->footballDataClient->fetchCompetition((int) $competition->external_id);
                $teamsPayload = $this->footballDataClient->fetchCompetitionTeams((int) $competition->external_id);
                $remoteCompetition['teams'] = $teamsPayload['teams'] ?? [];
                $this->competitionSyncService->syncCompetition($remoteCompetition);
                $competition->refresh();
                $meta['source'] = 'provider';
            } catch (RuntimeException $exception) {
                $meta['warning'] = $exception->getMessage();

                if ($competition->teams()->doesntExist()) {
                    return $this->apiError(
                        $exception->getCode() === 429 ? 'RATE_LIMIT_EXCEEDED' : 'FOOTBALL_DATA_UNAVAILABLE',
                        $exception->getMessage(),
                        in_array($exception->getCode(), [429, 503], true) ? $exception->getCode() : 503,
                    );
                }

                $meta['stale'] = true;
            }
        }

        return response()->json([
            'data' => new CompetitionResource($competition->load('teams.players')),
            'meta' => $meta,
        ]);
    }
}
