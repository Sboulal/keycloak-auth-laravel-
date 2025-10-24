<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class PasswordResetService
{
    /**
     * Send password reset code to email
     * 
     * @param string $email
     * @return string The generated code
     */
    public function sendPasswordResetCode(string $email): string
    {
        $code = $this->generateCode();

        DB::table('password_resets')->updateOrInsert(
            ['email' => $email],
            [
                'code' => $code,
                'expires_at' => Carbon::now()->addMinutes(10),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $this->sendResetEmail($email, $code);

        return $code;
    }

    /**
     * Verify password reset code and change password
     * 
     * @param string $email
     * @param string $code
     * @param string $newPassword
     * @return array ['success' => bool, 'message' => string]
     */
    public function verifyAndResetPassword(string $email, string $code, string $newPassword): array
    {
        try {
            $record = DB::table('password_resets')
                ->where('email', $email)
                ->first();

            if (!$record) {
                return [
                    'success' => false,
                    'message' => 'No reset record found for this email.'
                ];
            }

            if ($record->code !== $code) {
                return [
                    'success' => false,
                    'message' => 'Invalid verification code.'
                ];
            }

            if (Carbon::parse($record->expires_at)->isPast()) {
                return [
                    'success' => false,
                    'message' => 'Verification code has expired.'
                ];
            }

            $user = User::where('email', $email)->first();

            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'User not found.'
                ];
            }

            // Update password
            $user->password = Hash::make($newPassword);
            $user->must_change_password = false;
            $user->save();

            // Delete reset record
            DB::table('password_resets')->where('email', $email)->delete();

            return [
                'success' => true,
                'message' => 'Password changed successfully. You can now log in.'
            ];

        } catch (\Exception $e) {
            Log::error('Password reset failed', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Password reset failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Request password reset (forgot password)
     * 
     * @param string $email
     * @return array ['success' => bool, 'code' => string|null, 'message' => string]
     */
    public function requestPasswordReset(string $email): array
    {
        try {
            $user = User::where('email', $email)->first();

            if (!$user) {
                // For security, don't reveal if email exists
                return [
                    'success' => true,
                    'code' => null,
                    'message' => 'If the email exists, a reset code has been sent.'
                ];
            }

            $code = $this->sendPasswordResetCode($email);

            return [
                'success' => true,
                'code' => $code,
                'message' => 'Password reset code sent successfully.'
            ];

        } catch (\Exception $e) {
            Log::error('Password reset request failed', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'code' => null,
                'message' => 'Failed to send reset code: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Resend password reset code
     * 
     * @param string $email
     * @return array ['success' => bool, 'code' => string|null]
     */
    public function resendResetCode(string $email): array
    {
        try {
            $user = User::where('email', $email)->first();

            if (!$user) {
                return [
                    'success' => false,
                    'code' => null,
                    'message' => 'User not found'
                ];
            }

            $code = $this->sendPasswordResetCode($email);

            return [
                'success' => true,
                'code' => $code,
                'message' => 'Reset code sent successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Resend reset code failed', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'code' => null,
                'message' => 'Failed to resend code: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generate a 6-digit code
     * 
     * @return string
     */
    private function generateCode(): string
    {
        return (string) rand(100000, 999999);
    }

    /**
     * Send password reset email
     * 
     * @param string $email
     * @param string $code
     * @return void
     */
    private function sendResetEmail(string $email, string $code): void
    {
        try {
            Mail::raw("Your password reset code is: {$code}", function ($message) use ($email) {
                $message->to($email)->subject('Password Reset Code');
            });
        } catch (\Exception $e) {
            Log::error('Failed to send password reset email', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
        }
    }
}