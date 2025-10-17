<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestKeycloakConnection extends Command
{
    protected $signature = 'keycloak:test {username} {password}';
    protected $description = 'Test Keycloak connection and authentication';

    public function handle()
    {
        $username = $this->argument('username');
        $password = $this->argument('password');

        $this->info('Testing Keycloak Connection...');
        $this->newLine();

        // Display configuration
        $baseUrl = config('services.keycloak.base_url');
        $realm = config('services.keycloak.realm');
        $clientId = config('services.keycloak.client_id');
        $clientSecret = config('services.keycloak.client_secret');

        $this->info("Configuration:");
        $this->line("Base URL: {$baseUrl}");
        $this->line("Realm: {$realm}");
        $this->line("Client ID: {$clientId}");
        $this->line("Client Secret: " . (empty($clientSecret) ? '❌ NOT SET' : '✅ SET'));
        $this->line("Username: {$username}");
        $this->newLine();

        // Test token endpoint
        $tokenUrl = "{$baseUrl}/realms/{$realm}/protocol/openid-connect/token";
        $this->info("Token URL: {$tokenUrl}");
        $this->newLine();

        try {
            $this->info('Attempting authentication...');
            
            $response = Http::asForm()->post($tokenUrl, [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'grant_type' => 'password',
                'username' => $username,
                'password' => $password,
                'scope' => 'openid profile email'
            ]);

            $this->info("Response Status: {$response->status()}");
            $this->newLine();

            if ($response->successful()) {
                $this->info('✅ Authentication SUCCESSFUL!');
                $this->newLine();
                
                $data = $response->json();
                $this->line("Access Token (first 50 chars): " . substr($data['access_token'], 0, 50) . '...');
                $this->line("Token Type: " . ($data['token_type'] ?? 'N/A'));
                $this->line("Expires In: " . ($data['expires_in'] ?? 'N/A') . ' seconds');
                
                // Test userinfo endpoint
                $this->newLine();
                $this->info('Testing UserInfo endpoint...');
                
                $userinfoUrl = "{$baseUrl}/realms/{$realm}/protocol/openid-connect/userinfo";
                $userResponse = Http::withToken($data['access_token'])->get($userinfoUrl);
                
                if ($userResponse->successful()) {
                    $this->info('✅ UserInfo retrieved successfully!');
                    $userData = $userResponse->json();
                    $this->newLine();
                    $this->line("User Data:");
                    $this->line("  - Sub: " . ($userData['sub'] ?? 'N/A'));
                    $this->line("  - Email: " . ($userData['email'] ?? 'N/A'));
                    $this->line("  - Name: " . ($userData['name'] ?? 'N/A'));
                    $this->line("  - Username: " . ($userData['preferred_username'] ?? 'N/A'));
                    $this->line("  - Email Verified: " . ($userData['email_verified'] ? 'Yes' : 'No'));
                } else {
                    $this->error('❌ Failed to get user info');
                    $this->line(json_encode($userResponse->json(), JSON_PRETTY_PRINT));
                }
                
            } else {
                $this->error('❌ Authentication FAILED!');
                $this->newLine();
                
                $errorData = $response->json();
                $this->error("Error: " . ($errorData['error'] ?? 'Unknown'));
                $this->error("Description: " . ($errorData['error_description'] ?? 'No description'));
                $this->newLine();
                
                $this->warn('Common Solutions:');
                $this->line('1. Enable "Direct Access Grants" in Keycloak client settings');
                $this->line('2. Try using username instead of email (or vice versa)');
                $this->line('3. Check if user has "Required User Actions" that need completion');
                $this->line('4. Verify user is enabled in Keycloak');
                $this->line('5. Ensure email is verified in Keycloak');
                $this->line('6. Check client_id and client_secret are correct');
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Exception occurred:');
            $this->error($e->getMessage());
        }

        return 0;
    }
}