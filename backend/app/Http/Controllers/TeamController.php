<?php

namespace App\Http\Controllers;

use App\Http\Resources\TeamResource;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'teams.view');

        $teams = Team::with('players')->paginate((int) $request->query('per_page', 15));

        return response()->json([
            'data' => TeamResource::collection($teams),
            'meta' => [
                'current_page' => $teams->currentPage(),
                'last_page' => $teams->lastPage(),
                'total' => $teams->total(),
            ],
        ]);
    }

    public function show(Request $request, Team $team): JsonResponse
    {
        $this->requirePermission($request, 'teams.view');

        return response()->json([
            'data' => new TeamResource($team->load('players')),
        ]);
    }
}
