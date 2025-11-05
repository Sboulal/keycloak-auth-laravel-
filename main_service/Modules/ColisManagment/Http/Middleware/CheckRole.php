<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Response;

function handle(Request $request, Closure $next, ...$roles)
{
    $user = auth()->user();

    if (!$user) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    if (!$user->hasAnyRole($roles)) {
        return response()->json(['error' => 'Forbidden'], 403);
    }

    return $next($request);
}