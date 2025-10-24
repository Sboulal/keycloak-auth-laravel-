<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Services\EmailVerificationService;
use App\Services\PasswordResetService;

/**
 * @group Authentication - Login
 * 
 * APIs for user authentication
 */
class LoginController extends Controller
{
    protected $verificationService;
    protected $passwordResetService;

    public function __construct(
        EmailVerificationService $verificationService,
        PasswordResetService $passwordResetService
    ) {
        $this->verificationService = $verificationService;
        $this->passwordResetService = $passwordResetService;
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
    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required|string'
            ]);

            // Find user
            $user = User::where('email', $request->email)->first();
            
            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Invalid credentials'
                ], 401);
            }

            // Check email verification
            if (!$user->email_verified_at) {
                $code = $this->verificationService->sendVerificationCode($user->email);

                return response()->json([
                    'success' => false,
                    'require_verification' => true,
                    'message' => 'Please verify your email. A verification code has been sent.',
                    'email' => $user->email,
                    'debug_code' => config('app.debug') ? $code : null
                ], 403);
            }

            // Check if password must be changed
            if ($user->must_change_password) {
                $code = $this->passwordResetService->sendPasswordResetCode($user->email);

                return response()->json([
                    'success' => false,
                    'require_password_change' => true,
                    'message' => 'You must change your password. A code has been sent to your email.',
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
    public function logout(Request $request)
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