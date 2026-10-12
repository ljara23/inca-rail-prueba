<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ResetApiGuard
{
    public function handle(Request $request, Closure $next): Response
    {
        Auth::forgetGuards();
        Auth::shouldUse('api');

        return $next($request);
    }
}
