<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Contracts\Factory;

class KeycloakServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $socialite = $this->app->make(Factory::class);
        
        $socialite->extend('keycloak', function ($app) use ($socialite) {
            $config = $app['config']['services.keycloak'];
            
            return $socialite->buildProvider(
                \SocialiteProviders\Keycloak\Provider::class,
                $config
            );
        });
    }
}