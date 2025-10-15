<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Vizir\KeycloakWebGuard\Facades\KeycloakWeb;

class AuthController extends Controller
{
      /**
     * Redirect the user to Keycloak login.
     */
    public function redirectToLogin()
    {
       KeycloakWeb::redirectToLogin();
        return  redirect('/'); // or frontend URL
    }

    /**
     * Handle Keycloak callback after login.
     */
    public function handleCallback()
    {
        KeycloakWeb::callback();
        return redirect()->intended('/home');
    }

    /**
     * Get the authenticated user.
     */
    public function user(Request $request)
    {
        return response()->json([
            'status' => true,
            'user' => Auth::user()
        ]);
    }

    /**
     * Logout user from Keycloak and Laravel session.
     */
    public function logout(Request $request)
    {
        KeycloakWeb::logout();
        return redirect('/'); // or frontend URL
    }
  
}