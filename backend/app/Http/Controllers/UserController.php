<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'users.view');

        $users = User::with('roles')->paginate((int) $request->query('per_page', 15));

        return response()->json([
            'data' => UserResource::collection($users),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    public function show(Request $request, User $user): JsonResponse
    {
        $this->requirePermission($request, 'users.view');

        return response()->json([
            'data' => new UserResource($user->load('roles')),
        ]);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $this->requirePermission($request, 'users.create');

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_active' => $request->boolean('is_active', true),
        ]);

        if ($request->has('role_ids')) {
            $user->roles()->sync($request->input('role_ids', []));
        }

        return response()->json([
            'data' => new UserResource($user->load('roles')),
        ], 201);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $this->requirePermission($request, 'users.update');

        $payload = $request->validated();

        if (isset($payload['password'])) {
            $payload['password'] = Hash::make($payload['password']);
        }

        $user->fill($payload);
        $user->save();

        if ($request->has('role_ids')) {
            $user->roles()->sync($request->input('role_ids', []));
        }

        return response()->json([
            'data' => new UserResource($user->fresh()->load('roles')),
        ]);
    }

    public function toggleStatus(Request $request, User $user): JsonResponse
    {
        $this->requirePermission($request, 'users.deactivate');

        DB::transaction(function () use ($user): void {
            $user->is_active = ! $user->is_active;
            $user->save();

            if (! $user->is_active) {
                $user->tokens()->delete();
            }
        });

        return response()->json([
            'data' => new UserResource($user->fresh()->load('roles')),
        ]);
    }
}
