<?php

namespace App\Http\Controllers;

use App\Http\Resources\PlayerResource;
use App\Models\Player;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlayerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'players.view');

        $players = Player::with('team')->paginate((int) $request->query('per_page', 15));

        return response()->json([
            'data' => PlayerResource::collection($players),
            'meta' => [
                'current_page' => $players->currentPage(),
                'last_page' => $players->lastPage(),
                'total' => $players->total(),
            ],
        ]);
    }

    public function show(Request $request, Player $player): JsonResponse
    {
        $this->requirePermission($request, 'players.view');

        return response()->json([
            'data' => new PlayerResource($player->load('team')),
        ]);
    }
}
