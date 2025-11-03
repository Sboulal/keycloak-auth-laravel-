<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GatewayAuthMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if X-User-Id header exists (sent by nginx for protected routes)
        $userId = $request->header('X-User-Id');
        
        if (!$userId) {
            return response()->json([
                'message' => 'Unauthorized: missing gateway user'
            ], 401);
        }

        // Attach user_id to request for use in controllers
        $request->merge(['gateway_user_id' => $userId]);
        
        // Optionally attach new token if present
        $newToken = $request->header('X-New-Token');
        if ($newToken) {
            $request->merge(['gateway_new_token' => $newToken]);
        }

        return $next($request);
    }
}