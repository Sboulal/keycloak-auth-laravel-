<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthApiController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\VerificationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});





// Public routes (no authentication required)
Route::prefix('auth')->group(function () {
    
    // Registration
    Route::post('/register', [RegisterController::class, 'register']);
    
    // Login & Logout
    Route::post('/login', [LoginController::class, 'login']);
    
    // Email Verification
    Route::post('/verify-email', [VerificationController::class, 'verifyEmail']);
    Route::post('/resend-verification', [VerificationController::class, 'resendVerificationCode']);
    
    // Password Reset
    Route::post('/forgot-password', [VerificationController::class, 'requestPasswordReset']);
    Route::post('/verify-password-reset', [VerificationController::class, 'verifyPasswordReset']);
    Route::post('/resend-password-reset', [VerificationController::class, 'resendPasswordResetCode']);
});

// Protected routes (authentication required)
Route::middleware('auth:api')->prefix('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout']);
    Route::get('/profile', [LoginController::class, 'profile']);
});