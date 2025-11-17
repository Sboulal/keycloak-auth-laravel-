<?php

namespace Modules\ColisManagment\Http\Middleware;

use Closure;
use Modules\UserManagment\Entities\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class FakeAuthMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Vérifier si fake auth est activé (sécurité)
        if (!config('app.fake_auth_enabled', false)) {
            return response()->json(['error' => 'Fake auth is disabled'], 403);
        }

        try {
            // Récupérer le premier utilisateur existant ou en créer un
            $user = User::first();

            if (!$user) {
                // Créer un utilisateur de test basé sur les colonnes disponibles
                $columns = Schema::getColumnListing('users');
                
                $userData = [];
                
                // Mapper les champs probables
                if (in_array('first_name', $columns)) {
                    $userData['first_name'] = 'Test';
                }
                if (in_array('last_name', $columns)) {
                    $userData['last_name'] = 'User';
                }
                if (in_array('phone', $columns)) {
                    $userData['phone'] = '0600000000';
                }
                if (in_array('email', $columns)) {
                    $userData['email'] = 'test@example.com';
                }
                if (in_array('password', $columns)) {
                    $userData['password'] = bcrypt('password123');
                }
                if (in_array('address', $columns)) {
                    $userData['address'] = 'Test Address';
                }
                if (in_array('city', $columns)) {
                    $userData['city'] = 'Casablanca';
                }
                if (in_array('postal_code', $columns)) {
                    $userData['postal_code'] = '20000';
                }
                
                $user = User::create($userData);
            }

            // Authentifier l'utilisateur pour cette requête
            auth()->login($user);

        } catch (\Exception $e) {
            Log::error('FakeAuth Error: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Authentication failed',
                'message' => $e->getMessage()
            ], 500);
        }

        return $next($request);
    }
}