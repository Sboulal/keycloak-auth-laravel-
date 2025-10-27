<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

/**
 * @group Authentication - Terms & Conditions
 * 
 * APIs for accepting terms and conditions
 */
class AcceptConditionsController extends Controller
{
    /**
     * Accept Terms and Conditions
     * 
     * User must accept terms and conditions after registration and before email verification.
     * This sets the status to 1 (active) allowing the user to login after email verification.
     * 
     * @bodyParam email string required The user's email address. Example: johndoe@example.com
     * @bodyParam accept_conditions boolean required Must be true to accept. Example: true
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Terms and conditions accepted successfully. You can now verify your email.",
     *   "email": "johndoe@example.com",
     *   "status": 1
     * }
     * 
     * @response 400 {
     *   "success": false,
     *   "message": "You must accept the terms and conditions to continue"
     * }
     * 
     * @response 404 {
     *   "success": false,
     *   "message": "User not found"
     * }
     * 
     * @response 409 {
     *   "success": false,
     *   "message": "Terms and conditions already accepted"
     * }
     */
    public function acceptConditions(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'accept_conditions' => 'required|boolean',
            ]);

            // Find user by email
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Check if conditions must be accepted
            if (!$request->accept_conditions) {
                return response()->json([
                    'success' => false,
                    'message' => 'You must accept the terms and conditions to continue'
                ], 400);
            }

            // Check if already accepted
            if ($user->terms_accepted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terms and conditions already accepted'
                ], 409);
            }

            // Update user status and terms acceptance
            $user->update([
                'terms_accepted' => true,
                'terms_accepted_at' => now(),
                'status' => 1 // Active status
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Terms and conditions accepted successfully. You can now verify your email.',
                'email' => $user->email,
                'status' => $user->status
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Accept conditions failed', [
                'email' => $request->email ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to accept conditions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check Terms Acceptance Status
     * 
     * Check if a user has accepted the terms and conditions.
     * 
     * @bodyParam email string required The user's email address. Example: johndoe@example.com
     * 
     * @response 200 {
     *   "success": true,
     *   "email": "johndoe@example.com",
     *   "terms_accepted": true,
     *   "terms_accepted_at": "2025-10-27T10:30:00.000000Z",
     *   "status": 1
     * }
     * 
     * @response 404 {
     *   "success": false,
     *   "message": "User not found"
     * }
     */
    public function checkStatus(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
            ]);

            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'email' => $user->email,
                'terms_accepted' => $user->terms_accepted,
                'terms_accepted_at' => $user->terms_accepted_at,
                'status' => $user->status
            ], 200);

        } catch (\Exception $e) {
            Log::error('Check terms status failed', [
                'email' => $request->email ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to check status: ' . $e->getMessage()
            ], 500);
        }
    }
}