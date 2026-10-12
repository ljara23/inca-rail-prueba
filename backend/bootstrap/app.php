<?php

use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\ResetApiGuard;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'active.user' => EnsureUserIsActive::class,
            'api.guard.reset' => ResetApiGuard::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (ValidationException $exception, Request $request): ?JsonResponse {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'error' => [
                    'code' => 'VALIDATION_FAILED',
                    'message' => 'Los datos enviados no son válidos.',
                    'details' => $exception->errors(),
                ],
            ], 422);
        });

        $exceptions->render(function (AuthenticationException $exception, Request $request): ?JsonResponse {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'error' => [
                    'code' => 'UNAUTHENTICATED',
                    'message' => 'Se requiere una sesión válida.',
                    'details' => [],
                ],
            ], 401);
        });

        $exceptions->render(function (ModelNotFoundException|RouteNotFoundException|HttpExceptionInterface $exception, Request $request): ?JsonResponse {
            if (! $request->is('api/*')) {
                return null;
            }

            $status = $exception instanceof HttpExceptionInterface
                ? $exception->getStatusCode()
                : 404;
            $code = match ($status) {
                401 => 'UNAUTHENTICATED',
                403 => 'FORBIDDEN',
                404 => 'RESOURCE_NOT_FOUND',
                429 => 'RATE_LIMIT_EXCEEDED',
                default => 'HTTP_ERROR',
            };

            return response()->json([
                'error' => [
                    'code' => $code,
                    'message' => $exception->getMessage() ?: match ($status) {
                        401 => 'Se requiere una sesión válida.',
                        403 => 'No tienes permiso para realizar esta acción.',
                        404 => 'No se encontró el recurso solicitado.',
                        429 => 'Se alcanzó el límite de solicitudes.',
                        default => 'La solicitud no pudo procesarse.',
                    },
                    'details' => [],
                ],
            ], $status);
        });
    })->create();
