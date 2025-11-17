<?php

namespace Modules\MainService\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class GatewayAuth
{
    public function handle(Request $request, Closure $next)
    {
        $userId = $request->header('X-User-Id');

        if (!$userId) {
            return response()->json(['message' => 'Unauthorized: missing gateway user'], 401);
        }

        auth()->loginUsingId($userId);

        return $next($request);
    }
}
