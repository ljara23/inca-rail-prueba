<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'roles.view');

        $roles = Role::with('permissions')->paginate((int) $request->query('per_page', 15));

        return response()->json([
            'data' => RoleResource::collection($roles),
            'meta' => [
                'current_page' => $roles->currentPage(),
                'last_page' => $roles->lastPage(),
                'total' => $roles->total(),
            ],
        ]);
    }

    public function show(Request $request, Role $role): JsonResponse
    {
        $this->requirePermission($request, 'roles.view');

        return response()->json([
            'data' => new RoleResource($role->load('permissions')),
        ]);
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $this->requirePermission($request, 'roles.manage');

        $role = Role::create([
            'name' => $request->name,
            'slug' => $request->slug,
        ]);

        $role->permissions()->sync($request->input('permission_ids', []));

        return response()->json([
            'data' => new RoleResource($role->fresh()->load('permissions')),
        ], 201);
    }

    public function update(UpdateRoleRequest $request, Role $role): JsonResponse
    {
        $this->requirePermission($request, 'roles.manage');

        $role->fill($request->validated());
        $role->save();

        if ($request->has('permission_ids')) {
            $role->permissions()->sync($request->input('permission_ids', []));
        }

        return response()->json([
            'data' => new RoleResource($role->fresh()->load('permissions')),
        ]);
    }
}
