<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Models\User;

class CustomAuthController extends Controller
{
   public function showRegisterForm()
    {
        return view('auth.register');
    }

    // 🔹 Soumission du formulaire d'inscription
    public function register(Request $request)
    {
        $validated = $request->validate([
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6|confirmed',
        ]);

        $token = $this->getAdminToken();

        $response = Http::withToken($token)->post(env('KEYCLOAK_BASE_URL') . '/admin/realms/' . env('KEYCLOAK_REALM') . '/users', [
            'username' => $validated['email'],
            'email' => $validated['email'],
            'firstName' => $validated['firstName'],
            'lastName' => $validated['lastName'],
            'enabled' => true,
            'credentials' => [[
                'type' => 'password',
                'value' => $validated['password'],
                'temporary' => false,
            ]],
        ]);

        if (!$response->successful()) {
            return back()->withErrors(['error' => 'Erreur lors de la création sur Keycloak.']);
        }

        // Optionnel : Créer aussi localement pour auth() Laravel
        $user = User::create([
            'name' => $validated['firstName'] . ' ' . $validated['lastName'],
            'email' => $validated['email'],
        ]);

        Auth::login($user);

        return redirect('/dashboard')->with('success', 'Compte créé avec succès !');
    }

    // 🔹 Page de connexion
    public function showLoginForm()
    {
        return view('auth.login');
    }

    // 🔹 Connexion via API Keycloak
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $response = Http::asForm()->post(env('KEYCLOAK_BASE_URL') . '/realms/' . env('KEYCLOAK_REALM') . '/protocol/openid-connect/token', [
            'client_id' => env('KEYCLOAK_CLIENT_ID'),
            'client_secret' => env('KEYCLOAK_CLIENT_SECRET'),
            'grant_type' => 'password',
            'username' => $credentials['email'],
            'password' => $credentials['password'],
        ]);

        if (!$response->successful()) {
            return back()->withErrors(['error' => 'Identifiants invalides']);
        }

        $data = $response->json();
        $userInfo = Http::withToken($data['access_token'])
            ->get(env('KEYCLOAK_BASE_URL') . '/realms/' . env('KEYCLOAK_REALM') . '/protocol/openid-connect/userinfo')
            ->json();

        $user = User::updateOrCreate(
            ['email' => $userInfo['email']],
            ['name' => $userInfo['given_name'] . ' ' . $userInfo['family_name']]
        );

        Auth::login($user);
        session(['keycloak_token' => $data]);

        return redirect('/dashboard');
    }

    // 🔹 Logout
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }

    // 🔹 Récupère le token admin
    private function getAdminToken()
    {
        $response = Http::asForm()->post(env('KEYCLOAK_BASE_URL') . '/realms/master/protocol/openid-connect/token', [
            'client_id' => env('KEYCLOAK_ADMIN_CLIENT_ID'),
            'client_secret' => env('KEYCLOAK_ADMIN_CLIENT_SECRET'),
            'grant_type' => 'client_credentials',
        ]);

        return $response->json('access_token');
    }
}

