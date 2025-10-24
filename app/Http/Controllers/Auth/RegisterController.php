<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Services\EmailVerificationService;

/**
 * @group Authentication - Registration
 * 
 * APIs for user registration
 */
class RegisterController extends Controller
{
    protected $verificationService;

    public function __construct(EmailVerificationService $verificationService)
    {
        $this->verificationService = $verificationService;
    }

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
    public function register(Request $request)
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

            // Send verification code via service
            $code = $this->verificationService->sendVerificationCode($user->email);

            return response()->json([
                'success' => true,
                'message' => 'Registration successful! A verification code has been sent to your email.',
                'email' => $user->email,
                'debug_code' => config('app.debug') ? $code : null
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Registration failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Registration failed: ' . $e->getMessage()
            ], 500);
        }
    }
}