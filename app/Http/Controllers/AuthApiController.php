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

class AuthApiController extends Controller
{
  
public function apiRegister(Request $request)
    {
        $request->validate([
            'username' => 'required|string|min:3|max:50',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
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
        Mail::raw("Your verification code is: {$code}", function ($message) use ($user) {
            $message->to($user->email)->subject('Verify Your Account');
        });

        return response()->json([
            'success' => true,
            'message' => 'Registration successful! A verification code has been sent to your email.'
        ], 201);
    }

    public function apiLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        $credentials = $request->only('email', 'password');

        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json(['success' => false, 'message' => 'Invalid credentials'], 401);
        }
        
        $user = Auth::user();

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

            Mail::raw("Your verification code is: {$code}", function ($message) use ($user) {
                $message->to($user->email)->subject('Login Verification Code');
            });

            return response()->json([
                'success' => false,
                'require_verification' => true,
                'message' => 'A verification code has been sent to your email.'
            ], 202);
        }

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
            'user' => $user
        ]);
    }


public function verifyLoginCode(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'code' => 'required|string'
    ]);

    $record = DB::table('email_verifications')->where('email', $request->email)->first();

    if (!$record) {
        return response()->json(['success' => false, 'message' => 'No verification record found'], 404);
    }

    if ($record->code !== $request->code) {
        return response()->json(['success' => false, 'message' => 'Invalid code'], 400);
    }

    if (Carbon::parse($record->expires_at)->isPast()) {
        return response()->json(['success' => false, 'message' => 'Code expired'], 400);
    }

    $user = \App\Models\User::where('email', $request->email)->first();
    if ($user) {
        $user->update(['email_verified_at' => now()]);
    }

    DB::table('email_verifications')->where('email', $request->email)->delete();

    return response()->json([
        'success' => true,
        'message' => 'Email verified successfully. You can now log in.'
    ]);
}

}
