<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public routes
Route::prefix('ptc')->group(function () {
    Route::get('health', function () {
        return response()->json([
            'success' => true,
            'service' => 'main-service',
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String()
        ]);
    });
});

// Protected routes
Route::prefix('prv')->group(function () {
    Route::get('test', function (Request $request) {
        return response()->json([
            'success' => true,
            'message' => 'Access granted to protected route!',
            'user_id' => $request->header('X-User-Id'),
            'new_token' => $request->header('X-New-Token'),
            'all_headers' => $request->headers->all()
        ]);
    });
});