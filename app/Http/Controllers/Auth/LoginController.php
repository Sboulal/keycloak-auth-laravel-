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
 * Authenticate a user using email and verification code.
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
 *     "email": "johndoe@example.com"
 *   }
 * }
 * 
 * @response 401 {
 *   "success": false,
 *   "message": "Invalid verification code"
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
            'code' => 'required|string',
        ]);

        // Verify the code using the service
        $result = $this->verificationService->verifyLoginCode($request->email, $request->code);

        if (!$result['success']) {
            $statusCode = $result['message'] === 'User not found' ? 404 : 401;
            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], $statusCode);
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