<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthApiController;
use App\Http\Controllers\TrackingController;

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

Route::get('/tracking/save-address', [TrackingController::class, 'saveAddress']);
Route::get('/tracking/addresses/{livreurName?}', [TrackingController::class, 'getAddresses']);

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthApiController::class, 'apiLogin']);
    Route::post('/register', [AuthApiController::class, 'apiRegister']);
    Route::post('/logout', [AuthApiController::class, 'apiLogout']);
    Route::post('/verify', [AuthApiController::class, 'verifyLoginCode']);
});

// Protected route
Route::middleware('auth:api')->get('/profile', function () {
    return auth()->user();
});