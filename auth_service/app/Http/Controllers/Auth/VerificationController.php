<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use App\Services\EmailVerificationService;
use App\Services\PasswordResetService;



/**
 * @group Authentication - Verification
 * 
 * APIs for email and password verification
 */
class VerificationController extends Controller
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
     */
    public function verifyEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string'
        ]);

        $result = $this->verificationService->verifyCode(
            $request->email,
            $request->code
        );

        if (!$result['success']) {
            $statusCode = match($result['message']) {
                'No verification record found', 'User not found' => 404,
                default => 400
            };

            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], $statusCode);
        }

        // Check if user must change password
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

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'access_token' => $result['token'],
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
            'user' => [
                'id' => $result['user']->id,
                'name' => $result['user']->name,
                'email' => $result['user']->email,
            ]
        ]);
    }

    /**
 * Check if JWT token is valid
 * 
 * This endpoint verifies if a provided JWT token is still valid.
 * 
 * @header Authorization string required The Bearer token. Example: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
 * 
 * @response 200 {
 *   "success": true,
 *   "message": "Token is valid",
 *   "user": {
 *     "id": 1,
 *     "name": "John Doe",
 *     "email": "john@example.com"
 *   }
 * }
 * 
 * @response 401 {
 *   "success": false,
 *   "message": "Token is invalid or expired"
 * }
 */
public function checkToken(Request $request)
{
    try {
        // Vérifie si le token est valide
        $user = JWTAuth::parseToken()->authenticate();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Token is valid',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email
            ]
        ], 200);

    } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Token has expired'
        ], 401);
    } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Token is invalid'
        ], 401);
    } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Token is missing'
        ], 400);
    }
}

    /**
     * Resend verification code
     * 
     * @bodyParam email string required The user's email address. Example: johndoe@example.com
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Verification code sent successfully",
     *   "debug_code": "123456"
     * }
     */
    public function resendVerificationCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $result = $this->verificationService->resendCode($request->email);

        $statusCode = $result['success'] ? 200 : 400;

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'debug_code' => config('app.debug') ? $result['code'] : null
        ], $statusCode);
    }

    /**
     * Verify password reset code and set new password
     * 
     * @bodyParam email string required The user's email address. Example: johndoe@example.com
     * @bodyParam code string required The 6-digit verification code. Example: 123456
     * @bodyParam new_password string required The new password (min 6 characters). Example: NewPass123
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Password changed successfully. You can now log in."
     * }
     */
    public function verifyPasswordReset(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string',
            'new_password' => 'required|string|min:6'
        ]);

        $result = $this->passwordResetService->verifyAndResetPassword(
            $request->email,
            $request->code,
            $request->new_password
        );

        $statusCode = $result['success'] ? 200 : 400;

        return response()->json($result, $statusCode);
    }

    /**
     * Request password reset (Forgot password)
     * 
     * @bodyParam email string required The user's email address. Example: johndoe@example.com
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "If the email exists, a reset code has been sent.",
     *   "debug_code": "123456"
     * }
     */
    public function requestPasswordReset(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $result = $this->passwordResetService->requestPasswordReset($request->email);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'debug_code' => config('app.debug') ? $result['code'] : null
        ]);
    }

    /**
     * Resend password reset code
     * 
     * @bodyParam email string required The user's email address. Example: johndoe@example.com
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Reset code sent successfully",
     *   "debug_code": "123456"
     * }
     */
    public function resendPasswordResetCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $result = $this->passwordResetService->resendResetCode($request->email);

        $statusCode = $result['success'] ? 200 : 400;

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'debug_code' => config('app.debug') ? $result['code'] : null
        ], $statusCode);
    }
}