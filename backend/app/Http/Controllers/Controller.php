<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected function requirePermission(Request $request, string $permission): void
    {
        $user = $request->user('api');

        if (! $user || ! $user->hasPermission($permission)) {
            abort(403, 'No tienes permisos para esta acción.');
        }
    }

    protected function apiError(string $code, string $message, int $status): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => [],
            ],
        ], $status);
    }
}
