<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Support\Facades\Log;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class KeycloakController extends Controller
{
  public function redirect()
    {
        return Socialite::driver('keycloak')->redirect();
    }

    public function callback()
    {
        try {
            $keycloakUser = Socialite::driver('keycloak')->user();
            
            Log::info('Keycloak User Data:', [
                'id' => $keycloakUser->getId(),
                'email' => $keycloakUser->getEmail(),
                'name' => $keycloakUser->getName(),
            ]);
            
            $user = User::updateOrCreate(
                ['email' => $keycloakUser->getEmail()],
                [
                    'name' => $keycloakUser->getName() ?? $keycloakUser->getNickname() ?? 'User',
                    'keycloak_id' => $keycloakUser->getId(),
                    'email_verified_at' => now(), // Mark email as verified
                ]
            );

            Auth::login($user);

            return redirect()->intended('/dashboard');
            
        } catch (\Exception $e) {
            Log::error('Keycloak Auth Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect('/')->with('error', 'Salma: ' . $e->getMessage());
        }
    }

    public function logout()
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        
        return redirect('/');
    }
}
