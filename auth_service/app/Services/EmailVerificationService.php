<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tymon\JWTAuth\Facades\JWTAuth;

class EmailVerificationService
{
    /**
     * Send verification code to email
     * 
     * @param string $email
     * @return string The generated code
     */
    public function sendVerificationCode(string $email): string
    {
        $code = $this->generateCode();

        DB::table('email_verifications')->updateOrInsert(
            ['email' => $email],
            [
                'code' => $code,
                'expires_at' => Carbon::now()->addMinutes(10),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $this->sendVerificationEmail($email, $code);

        return $code;
    }

    /**
     * Verify the email code
     * 
     * @param string $email
     * @param string $code
     * @return array ['success' => bool, 'message' => string, 'user' => User|null, 'token' => string|null]
     */
    public function verifyCode(string $email, string $code): array
    {
        try {
            $record = DB::table('email_verifications')
                ->where('email', $email)
                ->first();

            if (!$record) {
                return [
                    'success' => false,
                    'message' => 'No verification record found',
                    'user' => null,
                    'token' => null
                ];
            }

            if ($record->code !== $code) {
                return [
                    'success' => false,
                    'message' => 'Invalid verification code',
                    'user' => null,
                    'token' => null
                ];
            }

            if (Carbon::parse($record->expires_at)->isPast()) {
                return [
                    'success' => false,
                    'message' => 'Verification code has expired',
                    'user' => null,
                    'token' => null
                ];
            }

            // Find and update user
            $user = User::where('email', $email)->first();
            
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'User not found',
                    'user' => null,
                    'token' => null
                ];
            }

            // Mark email as verified
            $user->update(['email_verified_at' => now()]);

            // Delete verification record
            DB::table('email_verifications')->where('email', $email)->delete();

            // Generate JWT token
            $token = JWTAuth::fromUser($user);

            return [
                'success' => true,
                'message' => 'Email verified successfully',
                'user' => $user,
                'token' => $token
            ];

        } catch (\Exception $e) {
            Log::error('Email verification failed', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Verification failed: ' . $e->getMessage(),
                'user' => null,
                'token' => null
            ];
        }
    }
    // Dans votre VerificationService
public function verifyLoginCode(string $email, string $code): array
{
    try {
        $record = DB::table('email_verifications')
            ->where('email', $email)
            ->first();

        if (!$record || $record->code !== $code) {
            return [
                'success' => false,
                'message' => 'Invalid verification code',
                'user' => null,
                'token' => null
            ];
        }

        if (Carbon::parse($record->expires_at)->isPast()) {
            return [
                'success' => false,
                'message' => 'Verification code has expired',
                'user' => null,
                'token' => null
            ];
        }

        $user = User::where('email', $email)->first();
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'User not found',
                'user' => null,
                'token' => null
            ];
        }

        // Delete verification record (but don't mark email as verified)
        DB::table('email_verifications')->where('email', $email)->delete();

        // Generate JWT token
        $token = JWTAuth::fromUser($user);

        return [
            'success' => true,
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token
        ];

    } catch (\Exception $e) {
        Log::error('Login code verification failed', ['error' => $e->getMessage()]);
        return [
            'success' => false,
            'message' => 'Verification failed: ' . $e->getMessage(),
            'user' => null,
            'token' => null
        ];
    }
}

    /**
     * Resend verification code
     * 
     * @param string $email
     * @return array ['success' => bool, 'code' => string|null]
     */
    public function resendCode(string $email): array
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

            if ($user->email_verified_at) {
                return [
                    'success' => false,
                    'code' => null,
                    'message' => 'Email already verified'
                ];
            }

            $code = $this->sendVerificationCode($email);

            return [
                'success' => true,
                'code' => $code,
                'message' => 'Verification code sent successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Resend verification code failed', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'code' => null,
                'message' => 'Failed to resend code: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generate a 6-digit verification code
     * 
     * @return string
     */
    private function generateCode(): string
    {
        return (string) rand(100000, 999999);
    }

    /**
     * Send verification email
     * 
     * @param string $email
     * @param string $code
     * @return void
     */
    private function sendVerificationEmail(string $email, string $code): void
    {
        try {
            Mail::raw("Your verification code is: {$code}", function ($message) use ($email) {
                $message->to($email)->subject('Verify Your Account');
            });
        } catch (\Exception $e) {
            Log::error('Failed to send verification email', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
        }
    }
}