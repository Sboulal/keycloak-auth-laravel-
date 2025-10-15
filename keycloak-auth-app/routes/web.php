<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\KeycloakController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});
// Keycloak authentication routes
Route::get('/auth/keycloak', [KeycloakController::class, 'redirect'])->name('keycloak.login');
Route::get('/auth/keycloak/callback', [KeycloakController::class, 'callback']);

// Protected dashboard route
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});

// Logout route
Route::post('/logout', [KeycloakController::class, 'logout'])->name('logout');
Route::get('/test-keycloak-config', function () {
    return [
        'client_id' => config('services.keycloak.client_id'),
        'base_url' => config('services.keycloak.base_url'),
        'realm' => config('services.keycloak.realms'),
        'redirect' => config('services.keycloak.redirect'),
    ];
});