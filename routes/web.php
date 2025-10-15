<?php

use Illuminate\Support\Facades\Route;
use Vizir\KeycloakWebGuard\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| Here is where you can register web routes for your application.
| These routes are handled by Keycloak Web Guard (session-based login).
*/

// Route::get('/', function () {
//     return view('welcome');
// });


// Route::get('/login', [AuthController::class, 'login'])->name('keycloak.login');
// Route::get('/logout', [AuthController::class, 'logout'])->name('keycloak.logout');
// Route::get('/callback', [AuthController::class, 'callback'])->name('keycloak.callback');


// Route::middleware('keycloak-web')->group(function () {
//     Route::get('/dashboard', function () {
//         $user = auth()->user();
//         return view('dashboard', compact('user'));
//     })->name('dashboard');
// });


// Route::get('/home', function () {
//     if (auth()->check()) {
//         return redirect()->route('dashboard');
//     }
//     return redirect()->route('keycloak.login');
// });





Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return view('welcome');
})->name('home');

// Keycloak routes - using package controller
Route::get('/login', [AuthController::class, 'login'])->name('keycloak.login');
Route::get('/logout', [AuthController::class, 'logout'])->name('keycloak.logout');
Route::get('/callback', [AuthController::class, 'callback'])->name('keycloak.callback');

// Protected routes
Route::middleware('keycloak-web')->group(function () {
    Route::get('/dashboard', function () {
        $user = auth()->user();
        return view('dashboard', compact('user'));
    })->name('dashboard');
});
