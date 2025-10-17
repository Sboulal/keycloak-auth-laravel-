<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;
use App\Http\Controllers\AuthApiController;
use Laravel\Socialite\Two\User as OAuth2User;
use Laravel\Socialite\Two\InvalidStateException;
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

// API-based authentication routes
Route::get('/login', [AuthApiController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthApiController::class, 'login'])->name('login.post');

Route::get('/register', [AuthApiController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [AuthApiController::class, 'register'])->name('register.post');

// Logout route (choose one implementation)
Route::post('/logout', [AuthApiController::class, 'logout'])->name('logout');

// Keycloak SSO authentication routes (alternative to direct login)
Route::get('/auth/keycloak', [KeycloakController::class, 'redirect'])->name('keycloak.login');
Route::get('/auth/keycloak/callback', [KeycloakController::class, 'callback'])->name('keycloak.callback');
Route::post('/auth/keycloak/logout', [KeycloakController::class, 'logout'])->name('keycloak.logout');


Route::get('auth/google', [AuthApiController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('auth/google/callback', [AuthApiController::class, 'handleGoogleCallback'])->name('google.callback');


// Protected dashboard route
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});