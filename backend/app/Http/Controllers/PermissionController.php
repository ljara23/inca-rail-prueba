<?php

namespace App\Http\Controllers;

use App\Http\Resources\PermissionResource;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'permissions.view');

        $permissions = Permission::paginate((int) $request->query('per_page', 15));

        return response()->json([
            'data' => PermissionResource::collection($permissions),
            'meta' => [
                'current_page' => $permissions->currentPage(),
                'last_page' => $permissions->lastPage(),
                'total' => $permissions->total(),
            ],
        ]);
    }
}
