<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Services\KeycloakApiService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Validator;
use App\Notifications\EmailVerificationCode;

/**
 * @group Authentication
 * 
 * APIs for user authentication and email verification
 */
class AuthApiController extends Controller
{
    /**
     * Register a new user
     * 
     * Creates a new user account and sends a verification code to the provided email address.
     * 
     * @bodyParam username string required The username for the account. Example: johndoe
     * @bodyParam email string required The email address. Must be unique. Example: johndoe@example.com
     * @bodyParam password string required The password (minimum 6 characters). Example: password123
     * @bodyParam first_name string optional The user's first name. Example: John
     * @bodyParam last_name string optional The user's last name. Example: Doe
     * 
     * @response 201 {
     *   "success": true,
     *   "message": "Registration successful! A verification code has been sent to your email.",
     *   "email": "johndoe@example.com",
     *   "debug_code": "123456"
     * }
     * 
     * @response 422 {
     *   "message": "The email has already been taken.",
     *   "errors": {
     *     "email": ["The email has already been taken."]
     *   }
     * }
     */
    public function apiRegister(Request $request)
    {
        try {
            $validated = $request->validate([
                'username' => 'required|string|min:3|max:50',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:6',
                'first_name' => 'nullable|string|max:50',
                'last_name' => 'nullable|string|max:50'
            ]);

            $user = User::create([
                'name' => $request->username,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            // Generate verification code
            $code = rand(100000, 999999);

            DB::table('email_verifications')->updateOrInsert(
                ['email' => $user->email],
                [
                    'code' => $code,
                    'expires_at' => Carbon::now()->addMinutes(10),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            // Send email
            try {
                Mail::raw("Your verification code is: {$code}", function ($message) use ($user) {
                    $message->to($user->email)->subject('Verify Your Account');
                });
            } catch (\Exception $e) {
                Log::error('Failed to send verification email', ['error' => $e->getMessage()]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Registration successful! A verification code has been sent to your email.',
                'email' => $user->email,
                'debug_code' => config('app.debug') ? $code : null
            ], 201);
        } catch (\Exception $e) {
            Log::error('Registration failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Registration failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Login
     * 
     * Authenticate a user and return a JWT token. If the email is not verified, 
     * a new verification code will be sent.
     * 
     * @bodyParam email string required The user's email address. Example: johndoe@example.com
     * @bodyParam password string required The user's password. Example: password123
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Login successful",
     *   "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
     *   "token_type": "bearer",
     *   "expires_in": 3600,
     *   "user": {
     *     "id": 1,
     *     "name": "johndoe",
     *     "email": "johndoe@example.com"
     *   }
     * }
     * 
     * @response 403 {
     *   "success": false,
     *   "require_verification": true,
     *   "message": "Please verify your email. A verification code has been sent.",
     *   "email": "johndoe@example.com",
     *   "debug_code": "123456"
     * }
     * 
     * @response 401 {
     *   "success": false,
     *   "message": "Invalid credentials"
     * }
     */
    public function apiLogin(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required|string'
            ]);

            // Find user
            $user = User::where('email', $request->email)->first();
            
            if (!$user) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Invalid credentials'
                ], 401);
            }

            // Verify password
            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Invalid credentials'
                ], 401);
            }

            // Check email verification
            if (!$user->email_verified_at) {
                $code = rand(100000, 999999);

                DB::table('email_verifications')->updateOrInsert(
                    ['email' => $user->email],
                    [
                        'code' => $code,
                        'expires_at' => Carbon::now()->addMinutes(10),
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );

                try {
                    Mail::raw("Your verification code is: {$code}", function ($message) use ($user) {
                        $message->to($user->email)->subject('Login Verification Code');
                    });
                } catch (\Exception $e) {
                    Log::error('Failed to send verification email', ['error' => $e->getMessage()]);
                }

                return response()->json([
                    'success' => false,
                    'require_verification' => true,
                    'message' => 'Please verify your email. A verification code has been sent.',
                    'email' => $user->email,
                    'debug_code' => config('app.debug') ? $code : null
                ], 403);
            }

            // Generate JWT token
            $token = JWTAuth::fromUser($user);

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => JWTAuth::factory()->getTTL() * 60,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Login failed', [
                'email' => $request->email,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Login failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify email with code
     * 
     * Verify the user's email address using the code sent during registration or login.
     * Upon successful verification, a JWT token is automatically generated.
     * 
     * @bodyParam email string required The user's email address. Example: johndoe@example.com
     * @bodyParam code string required The 6-digit verification code. Example: 123456
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Email verified successfully",
     *   "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
     *   "token_type": "bearer",
     *   "expires_in": 3600,
     *   "user": {
     *     "id": 1,
     *     "name": "johndoe",
     *     "email": "johndoe@example.com"
     *   }
     * }
     * 
     * @response 400 {
     *   "success": false,
     *   "message": "Invalid verification code"
     * }
     * 
     * @response 400 {
     *   "success": false,
     *   "message": "Verification code has expired"
     * }
     * 
     * @response 404 {
     *   "success": false,
     *   "message": "No verification record found"
     * }
     */
    public function verifyLoginCode(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'code' => 'required|string'
            ]);

            $record = DB::table('email_verifications')
                ->where('email', $request->email)
                ->first();

            if (!$record) {
                return response()->json([
                    'success' => false, 
                    'message' => 'No verification record found'
                ], 404);
            }

            if ($record->code !== $request->code) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Invalid verification code'
                ], 400);
            }

            if (Carbon::parse($record->expires_at)->isPast()) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Verification code has expired'
                ], 400);
            }

            // Find and update user
            $user = User::where('email', $request->email)->first();
            
            if (!$user) {
                return response()->json([
                    'success' => false, 
                    'message' => 'User not found'
                ], 404);
            }

            // Mark email as verified
            $user->update(['email_verified_at' => now()]);

            // Delete verification record
            DB::table('email_verifications')->where('email', $request->email)->delete();

            // Generate JWT token
            $token = JWTAuth::fromUser($user);

            return response()->json([
                'success' => true,
                'message' => 'Email verified successfully',
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => JWTAuth::factory()->getTTL() * 60,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Verification failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Verification failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Logout
     * 
     * Invalidate the current JWT token and log the user out.
     * 
     * @authenticated
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Logged out successfully"
     * }
     * 
     * @response 401 {
     *   "message": "Unauthenticated."
     * }
     */
    public function apiLogout(Request $request)
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Logout failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to logout: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user profile
     * 
     * Retrieve the authenticated user's profile information.
     * 
     * @authenticated
     * 
     * @response 200 {
     *   "id": 1,
     *   "name": "johndoe",
     *   "email": "johndoe@example.com",
     *   "email_verified_at": "2025-10-24T10:30:00.000000Z",
     *   "created_at": "2025-10-24T10:25:00.000000Z",
     *   "updated_at": "2025-10-24T10:30:00.000000Z"
     * }
     * 
     * @response 401 {
     *   "message": "Unauthenticated."
     * }
     */
    public function profile(Request $request)
    {
        return response()->json(auth()->user());
    }
}