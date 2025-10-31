<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * @group Authentication - Registration
 * 
 * APIs for user registration
 */
class RegisterController extends Controller
{
    /**
     * Register a new user
     * 
     * Creates a new user account WITHOUT sending verification code.
     * User must accept terms in next step before receiving verification code.
     * 
     * @bodyParam username string required The username for the account. Example: johndoe
     * @bodyParam email string required The email address. Must be unique. Example: johndoe@example.com
     * @bodyParam password string required The password (minimum 6 characters). Example: password123
     * @bodyParam first_name string optional The user's first name. Example: John
     * @bodyParam last_name string optional The user's last name. Example: Doe
     * 
     * @response 201 {
     *   "success": true,
     *   "message": "Registration successful! Please accept the terms and conditions to continue.",
     *   "user_id": 1,
     *   "email": "johndoe@example.com",
     *   "next_step": "accept_terms"
     * }
     * 
     * @response 422 {
     *   "success": false,
     *   "message": "Validation failed",
     *   "errors": {
     *     "email": ["The email has already been taken."]
     *   }
     * }
     * 
     * @response 500 {
     *   "success": false,
     *   "message": "Registration failed: Database connection error"
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

            // Create user with status = 0 (pending) - waiting for terms acceptance
            $user = User::create([
                'name' => $request->username,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'status' => 0, // Pending - waiting for terms acceptance
                'terms_accepted' => false,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Registration successful! Please accept the terms and conditions to continue.',
                'user_id' => $user->id,
                'email' => $user->email,
                'next_step' => 'accept_terms'
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