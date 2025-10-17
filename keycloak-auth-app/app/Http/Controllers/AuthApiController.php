<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\KeycloakApiService;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthApiController extends Controller
{
   protected $keycloakService;

    public function __construct(KeycloakApiService $keycloakService)
    {
        $this->keycloakService = $keycloakService;
    }

    /**
     * Show login form
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle login request
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            // Authenticate with Keycloak
            $result = $this->keycloakService->login(
                $request->username,
                $request->password
            );
           
          

            // Log the response for debugging
            Log::info('Keycloak login response', [
                'success' => $result['success'],
                'message' => $result['message'] ?? 'No message',
                'has_data' => isset($result['data'])
            ]);

            if (!$result['success']) {
                $errorMessage = $result['message'] ?? 'Authentication failed. Please check your credentials.';
                
                // Check for specific error messages
                if (isset($result['error'])) {
                    if ($result['error'] === 'invalid_grant') {
                        $errorMessage = 'Invalid username or password.';
                    } elseif ($result['error'] === 'user_disabled') {
                        $errorMessage = 'Your account has been disabled. Please contact support.';
                    }
                }
                
                return back()->with('error', $errorMessage)->withInput($request->only('username'));
            }

            // Check if access token exists
            if (!isset($result['data']['access_token'])) {
                Log::error('No access token in Keycloak response', ['result' => $result]);
                return back()->with('error', 'Authentication failed: No access token received.')->withInput($request->only('username'));
            }

            // Get user info from Keycloak
            $userInfoResult = $this->keycloakService->getUserInfo($result['data']['access_token']);

            if (!$userInfoResult['success']) {
                Log::error('Failed to get user info from Keycloak', ['result' => $userInfoResult]);
                return back()->with('error', 'Failed to retrieve user information.')->withInput($request->only('username'));
            }

            $keycloakUser = $userInfoResult['data'];

            // Check if email exists
            if (!isset($keycloakUser['email'])) {
                Log::error('No email in Keycloak user data', ['user_data' => $keycloakUser]);
                return back()->with('error', 'User email not found in Keycloak.')->withInput($request->only('username'));
            }

            // Create or update user in local database
            $user = User::updateOrCreate(
                ['email' => $keycloakUser['email']],
                [
                    'name' => $keycloakUser['name'] ?? $keycloakUser['preferred_username'] ?? $keycloakUser['email'],
                    'keycloak_id' => $keycloakUser['sub'],
                    'email_verified_at' => isset($keycloakUser['email_verified']) && $keycloakUser['email_verified'] ? now() : null,
                ]
            );

            // Store tokens in session
            session([
                'keycloak_access_token' => $result['data']['access_token'],
                'keycloak_refresh_token' => $result['data']['refresh_token'] ?? null,
                'keycloak_expires_in' => $result['data']['expires_in'] ?? 300,
                'keycloak_token_time' => now(),
            ]);
 
            // Login user
            Auth::login($user, $request->has('remember'));

            Log::info('User logged in successfully', ['user_id' => $user->id, 'email' => $user->email]);

            return redirect()->intended('/dashboard');
            
        } catch (\Exception $e) {
            Log::error('Login exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->with('error', 'An error occurred during login. Please try again.')->withInput($request->only('username'));
        }
    }

    /**
     * Show registration form
     */
    public function showRegisterForm()
    {
        return view('auth.register');
    }

    /**
     * Handle registration request
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|min:3|max:50',
            'email' => 'required|email|max:255',
            'first_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            // Register user in Keycloak
            $result = $this->keycloakService->register([
                'username' => $request->username,
                'email' => $request->email,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'password' => $request->password,
            ]);

            if (!$result['success']) {
                Log::error('Keycloak registration failed', ['result' => $result]);
                return back()->with('error', $result['message'])->withInput($request->except('password', 'password_confirmation'));
            }

            return redirect()->route('login')
                ->with('success', 'Registration successful! Please login with your credentials.');
                
        } catch (\Exception $e) {
            Log::error('Registration exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->with('error', 'An error occurred during registration. Please try again.')->withInput($request->except('password', 'password_confirmation'));
        }
    }

    /**
     * Handle logout request
     */
    public function logout(Request $request)
    {
        try {
            $refreshToken = session('keycloak_refresh_token');

            if ($refreshToken) {
                $this->keycloakService->logout($refreshToken);
            }

            Auth::logout();
            
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/')->with('success', 'You have been logged out successfully.');
            
        } catch (\Exception $e) {
            Log::error('Logout exception', [
                'error' => $e->getMessage()
            ]);
            
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            return redirect('/');
        }
    }
 /**
     * Redirect to Google OAuth
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')
            ->redirect();
    }

    /**
     * Handle Google OAuth Callback
     */
    public function handleGoogleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            
            // Rechercher l'utilisateur par email
            $user = User::where('email', $googleUser->getEmail())->first();

            if (!$user) {
                // Créer un nouvel utilisateur
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'password' => bcrypt(Str::random(32)),
                    'email_verified_at' => now(),
                ]);
                
                Log::info('New user created via Google', ['email' => $user->email]);
            } else {
                // Mettre à jour google_id si vide
                if (!$user->google_id) {
                    $user->update(['google_id' => $googleUser->getId()]);
                }
            }

            // Authentifier l'utilisateur
            Auth::login($user, true);

            Log::info('User logged in via Google', ['user_id' => $user->id, 'email' => $user->email]);

            return redirect()->intended('/dashboard');
            
        } catch (\Exception $e) {
            Log::error('Google Auth Error: ' . $e->getMessage());
            return redirect('/login')->with('error', 'Authentication failed. Please try again.');
        }
    }
}
