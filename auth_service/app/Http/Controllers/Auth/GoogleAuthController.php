<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;
use Exception;

class GoogleAuthController extends Controller
{
    /**
     * Get Google redirect URL
     */
    public function redirectToGoogle()
    {
        try {
            $params = [
                'client_id' => config('services.google.client_id'),
                'redirect_uri' => config('services.google.redirect'),
                'response_type' => 'code',
                'scope' => 'openid email profile',
                'access_type' => 'online',
                'prompt' => 'select_account',
            ];

            $url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);

            return response()->json([
                'success' => true,
                'url' => $url
            ], 200);

        } catch (Exception $e) {
            Log::error('Google Redirect Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate Google authentication URL',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle Google OAuth callback
     */
    public function handleGoogleCallback(Request $request)
    {
        try {
            // Check for errors
            if ($request->has('error')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Google authentication cancelled',
                    'error' => $request->error
                ], 400);
            }

            // Check for authorization code
            if (!$request->has('code')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authorization code not provided'
                ], 400);
            }

            // Exchange code for access token
            $tokenResponse = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'code' => $request->code,
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'redirect_uri' => config('services.google.redirect'),
                'grant_type' => 'authorization_code',
            ]);

            if ($tokenResponse->failed()) {
                Log::error('Google Token Exchange Failed', $tokenResponse->json());
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to exchange authorization code',
                    'error' => $tokenResponse->json()
                ], 400);
            }

            $tokenData = $tokenResponse->json();
            $accessToken = $tokenData['access_token'];

            // Get user info from Google
            $userResponse = Http::withToken($accessToken)
                ->get('https://www.googleapis.com/oauth2/v2/userinfo');

            if ($userResponse->failed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to retrieve user information'
                ], 400);
            }

            $googleUser = $userResponse->json();

            // Validate email
            if (!isset($googleUser['email'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email not provided by Google'
                ], 400);
            }

            // Find or create user
            $user = User::where('email', $googleUser['email'])->first();

            if (!$user) {
                $user = User::create([
                    'name' => $googleUser['name'] ?? 'Google User',
                    'email' => $googleUser['email'],
                    'google_id' => $googleUser['id'],
                    'avatar' => $googleUser['picture'] ?? null,
                    'password' => bcrypt(Str::random(32)),
                    'email_verified_at' => now(),
                ]);

                Log::info('New user registered via Google: ' . $user->email);
            } else {
                if (!$user->google_id) {
                    $user->update([
                        'google_id' => $googleUser['id'],
                        'avatar' => $googleUser['picture'] ?? $user->avatar,
                        'email_verified_at' => $user->email_verified_at ?? now(),
                    ]);
                }

                Log::info('User logged in via Google: ' . $user->email);
            }

            // Generate JWT token
            $token = JWTAuth::fromUser($user);
            $ttl = config('jwt.ttl', 60);

            return response()->json([
                'success' => true,
                'message' => 'Authentication successful',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                    'email_verified_at' => $user->email_verified_at,
                ],
                'token' => $token,
                'token_type' => 'bearer',
                'expires_in' => $ttl * 60,
            ], 200);

        } catch (Exception $e) {
            Log::error('Google Auth Callback Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Authentication failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Unable to authenticate'
            ], 500);
        }
    }
}