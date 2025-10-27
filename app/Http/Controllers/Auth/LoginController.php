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
     * Request Login Code
     * 
     * Send a 6-digit verification code to the user's email for login.
     * 
     * @bodyParam email string required The user's email address. Example: johndoe@example.com
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "A verification code has been sent to your email.",
     *   "email": "johndoe@example.com",
     *   "debug_code": "123456"
     * }
     * 
     * @response 403 {
     *   "success": false,
     *   "message": "Please accept the terms and conditions before logging in",
     *   "require_terms_acceptance": true
     * }
     * 
     * @response 404 {
     *   "success": false,
     *   "message": "User not found"
     * }
     */
    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
            ]);

            // Find user
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Check if user has accepted terms and conditions
            if (!$user->terms_accepted || $user->status != 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please accept the terms and conditions before logging in',
                    'require_terms_acceptance' => true,
                    'email' => $user->email
                ], 403);
            }

            // Send verification code
            $code = $this->verificationService->sendVerificationCode($request->email);

            return response()->json([
                'success' => true,
                'message' => 'A verification code has been sent to your email.',
                'email' => $request->email,
                'debug_code' => config('app.debug') ? $code : null
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to send login code', [
                'email' => $request->email,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send code: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify Email and Complete Login
     * 
     * Verify the code sent to email and authenticate the user with JWT token.
     * User must have status = 1 (active) to login successfully.
     * 
     * @bodyParam email string required The user's email address. Example: johndoe@example.com
     * @bodyParam code string required The 6-digit verification code. Example: 123456
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
     *     "email": "johndoe@example.com",
     *     "status": 1
     *   }
     * }
     * 
     * @response 401 {
     *   "success": false,
     *   "message": "Invalid verification code"
     * }
     * 
     * @response 403 {
     *   "success": false,
     *   "message": "Your account is not active. Please contact support.",
     *   "status": 0
     * }
     * 
     * @response 403 {
     *   "success": false,
     *   "require_password_change": true,
     *   "message": "You must change your password. A code has been sent to your email.",
     *   "email": "johndoe@example.com"
     * }
     * 
     * @response 404 {
     *   "success": false,
     *   "message": "User not found"
     * }
     */
    public function verifyEmail(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'code' => 'required|string',
            ]);

            // Verify the code using the service
            $result = $this->verificationService->verifyCode($request->email, $request->code);

            if (!$result['success']) {
                $statusCode = $result['message'] === 'User not found' ? 404 : 401;
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], $statusCode);
            }

            // Check if user status is active (1)
            if ($result['user']->status != 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account is not active. Please contact support.',
                    'status' => $result['user']->status
                ], 403);
            }

            // Check if password must be changed
            if ($result['user']->must_change_password) {
                $code = $this->passwordResetService->sendPasswordResetCode($result['user']->email);

                return response()->json([
                    'success' => false,
                    'require_password_change' => true,
                    'message' => 'You must change your password. A code has been sent to your email.',
                    'email' => $result['user']->email,
                    'debug_code' => config('app.debug') ? $code : null
                ], 403);
            }

            // Return success with token
            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'access_token' => $result['token'],
                'token_type' => 'bearer',
                'expires_in' => JWTAuth::factory()->getTTL() * 60,
                'user' => [
                    'id' => $result['user']->id,
                    'name' => $result['user']->name,
                    'email' => $result['user']->email,
                    'status' => $result['user']->status
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Email verification failed', [
                'email' => $request->email,
                'error' => $e->getMessage(),
            ]);
            
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
     *   "status": 1,
     *   "terms_accepted": true,
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