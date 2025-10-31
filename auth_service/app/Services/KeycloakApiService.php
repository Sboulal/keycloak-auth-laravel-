<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class KeycloakApiService
{
    protected $baseUrl;
    protected $realm;
    protected $clientId;
    protected $clientSecret;
    protected $adminUsername;
    protected $adminPassword;
    protected $timeout = 10; // seconds

    public function __construct()
    {
        // Read from config/services.php first, fallback to env() for flexibility
        $this->baseUrl = rtrim(
            config('services.keycloak.base_url', env('KEYCLOAK_BASE_URL')), 
            '/'
        );
        $this->realm = config('services.keycloak.realm', env('KEYCLOAK_REALM'));
        $this->clientId = config('services.keycloak.client_id', env('KEYCLOAK_CLIENT_ID'));
        $this->clientSecret = config('services.keycloak.client_secret', env('KEYCLOAK_CLIENT_SECRET'));
        $this->adminUsername = config('services.keycloak.admin_username', env('KEYCLOAK_ADMIN_USERNAME'));
        $this->adminPassword = config('services.keycloak.admin_password', env('KEYCLOAK_ADMIN_PASSWORD'));

        $this->validateConfiguration();
    }

    /**
     * Validate required configuration exists
     */
    protected function validateConfiguration()
    {                                                                                                                                                                                                                               
        $required = ['baseUrl', 'realm', 'clientId'];
        $missing = [];

        foreach ($required as $field) {
            if (empty($this->$field)) {
                $missing[] = $field;
            }
        }

        if (!empty($missing)) {
            throw new \RuntimeException(
                'Missing required Keycloak configuration: ' . implode(', ', $missing)
            );
        }
    }

    /**
     * Authenticate user and get access token
     */
    public function login($username, $password)
    {
        try {
            // Validate input
            if (empty($username) || empty($password)) {
                return [
                    'success' => false,
                    'message' => 'Username and password are required',
                    'error' => 'invalid_request'
                ];
            }

            $url = "{$this->baseUrl}/realms/{$this->realm}/protocol/openid-connect/token";
            
            // Log attempt without sensitive data
            Log::info('Keycloak login attempt', [
                'username' => $username,
                'realm' => $this->realm,
            ]);

            $params = [
                'client_id' => $this->clientId,
                'grant_type' => 'password',
                'username' => $username,
                'password' => $password,
                'scope' => 'openid profile email'
            ];

            // Only add client_secret if it exists (for confidential clients)
            if (!empty($this->clientSecret)) {
                $params['client_secret'] = $this->clientSecret;
            }

            $response = Http::timeout($this->timeout)
                ->asForm()
                ->post($url, $params);

            // Log response status without sensitive data
            Log::info('Keycloak login response', [
                'status' => $response->status(),
                'successful' => $response->successful(),
                'has_access_token' => isset($response->json()['access_token'])
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Verify required tokens
                if (!isset($data['access_token'])) {
                    Log::error('No access_token in successful response');
                    return [
                        'success' => false,
                        'message' => 'Authentication succeeded but no access token received',
                        'error' => 'missing_token'
                    ];
                }
                
                return [
                    'success' => true,
                    'data' => [
                        'access_token' => $data['access_token'],
                        'refresh_token' => $data['refresh_token'] ?? null,
                        'expires_in' => $data['expires_in'] ?? 300,
                        'refresh_expires_in' => $data['refresh_expires_in'] ?? 1800,
                        'token_type' => $data['token_type'] ?? 'Bearer',
                        'scope' => $data['scope'] ?? ''
                    ]
                ];
            }

            // Handle error responses
            return $this->handleErrorResponse($response, 'login');

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Keycloak connection error', [
                'message' => $e->getMessage(),
                'operation' => 'login'
            ]);
            
            return [
                'success' => false,
                'message' => 'Unable to connect to authentication server',
                'error' => 'connection_error'
            ];
        } catch (\Exception $e) {
            Log::error('Keycloak login exception', [
                'message' => $e->getMessage(),
                'type' => get_class($e)
            ]);
            
            return [
                'success' => false,
                'message' => 'An unexpected error occurred during authentication',
                'error' => 'internal_error'
            ];
        }
    }

    /**
     * Handle error responses from Keycloak
     */
    protected function handleErrorResponse($response, $operation)
    {
        $errorData = $response->json() ?? [];
        $error = $errorData['error'] ?? 'unknown_error';
        $errorDescription = $errorData['error_description'] ?? '';
        
        Log::error("Keycloak {$operation} failed", [
            'status' => $response->status(),
            'error' => $error,
            'error_description' => $errorDescription
        ]);

        // Map errors to user-friendly messages
        $errorMessages = [
            'invalid_grant' => 'Invalid username or password',
            'unauthorized_client' => 'Client not authorized. Please contact support.',
            'invalid_client' => 'Invalid client configuration. Please contact support.',
            'invalid_request' => 'Invalid request. Please check your input.',
            'access_denied' => 'Access denied',
            'unsupported_grant_type' => 'Authentication method not supported'
        ];

        $message = $errorMessages[$error] ?? $errorDescription ?: 'Authentication failed';

        return [
            'success' => false,
            'message' => $message,
            'error' => $error
        ];
    }

    /**
     * Get user info from access token
     */
    public function getUserInfo($accessToken)
    {
        try {
            if (empty($accessToken)) {
                return [
                    'success' => false,
                    'message' => 'Access token is required'
                ];
            }

            $url = "{$this->baseUrl}/realms/{$this->realm}/protocol/openid-connect/userinfo";

            $response = Http::timeout($this->timeout)
                ->withToken($accessToken)
                ->get($url);

            if ($response->successful()) {
                $userData = $response->json();
                
                Log::info('User info retrieved', [
                    'has_sub' => isset($userData['sub']),
                    'has_email' => isset($userData['email'])
                ]);
                
                return [
                    'success' => true,
                    'data' => $userData
                ];
            }

            Log::error('Failed to get user info', [
                'status' => $response->status()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to retrieve user information'
            ];

        } 
        catch (\Exception $e) {
            Log::error('Get user info exception', [
                'message' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Error retrieving user information'
            ];
        }
    }

    /**
     * Get admin access token with caching
     */
    protected function getAdminToken()
    {
        try {
            // Check if we have a cached admin token
            $cacheKey = 'keycloak_admin_token';
            $cachedToken = Cache::get($cacheKey);
            
            if ($cachedToken) {
                return $cachedToken;
            }

            if (empty($this->adminUsername) || empty($this->adminPassword)) {
                Log::error('Admin credentials not configured');
                return null;
            }

            $response = Http::timeout($this->timeout)
                ->asForm()
                ->post(
                    "{$this->baseUrl}/realms/master/protocol/openid-connect/token",
                    [
                        'client_id' => 'admin-cli',
                        'grant_type' => 'password',
                        'username' => $this->adminUsername,
                        'password' => $this->adminPassword,
                    ]
                );

            if ($response->successful()) {
                $data = $response->json();
                $token = $data['access_token'];
                $expiresIn = $data['expires_in'] ?? 60;
                
                // Cache token for slightly less than its expiry time
                Cache::put($cacheKey, $token, now()->addSeconds($expiresIn - 10));
                
                Log::info('Admin token obtained and cached');
                return $token;
            }

            Log::error('Failed to get admin token', [
                'status' => $response->status()
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Admin token exception', [
                'message' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Register new user
     */
    public function register(array $userData)
    {
        try {
            // Validate required fields
            $required = ['username', 'email', 'password'];
            foreach ($required as $field) {
                if (empty($userData[$field])) {
                    return [
                        'success' => false,
                        'message' => "Field '{$field}' is required"
                    ];
                }
            }

            // Validate email format
            if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
                return [
                    'success' => false,
                    'message' => 'Invalid email format'
                ];
            }

            $adminToken = $this->getAdminToken();
            
            if (!$adminToken) {
                return [
                    'success' => false,
                    'message' => 'Unable to access user management system'
                ];
            }

            Log::info('Registering new user', [
                'username' => $userData['username'],
                'email' => $userData['email']
            ]);

            $response = Http::timeout($this->timeout)
                ->withToken($adminToken)
                ->post(
                    "{$this->baseUrl}/admin/realms/{$this->realm}/users",
                    [
                        'username' => $userData['username'],
                        'email' => $userData['email'],
                        'firstName' => $userData['first_name'] ?? '',
                        'lastName' => $userData['last_name'] ?? '',
                        'enabled' => true,
                        'emailVerified' => $userData['email_verified'] ?? false,
                        'credentials' => [
                            [
                                'type' => 'password',
                                'value' => $userData['password'],
                                'temporary' => false
                            ]
                        ]
                    ]
                );

            if ($response->status() === 201) {
                Log::info('User registered successfully', [
                    'username' => $userData['username']
                ]);
                
                return [
                    'success' => true,
                    'message' => 'User registered successfully'
                ];
            }

            // Handle specific error cases
            if ($response->status() === 409) {
                return [
                    'success' => false,
                    'message' => 'Username or email already exists'
                ];
            }

            $error = $response->json();
            $errorMessage = $error['errorMessage'] ?? 'Registration failed';

            Log::error('User registration failed', [
                'status' => $response->status(),
                'error' => $errorMessage
            ]);

            return [
                'success' => false,
                'message' => $errorMessage
            ];

        } catch (\Exception $e) {
            Log::error('Register exception', [
                'message' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'An error occurred during registration'
            ];
        }
    }

    /**
     * Logout user
     */
    public function logout($refreshToken)
    {
        try {
            if (empty($refreshToken)) {
                Log::warning('Logout attempted without refresh token');
                return false;
            }

            $params = [
                'client_id' => $this->clientId,
                'refresh_token' => $refreshToken
            ];

            if (!empty($this->clientSecret)) {
                $params['client_secret'] = $this->clientSecret;
            }

            $response = Http::timeout($this->timeout)
                ->asForm()
                ->post(
                    "{$this->baseUrl}/realms/{$this->realm}/protocol/openid-connect/logout",
                    $params
                );

            $success = $response->successful() || $response->status() === 204;
            
            Log::info('Logout completed', ['success' => $success]);

            return $success;

        } catch (\Exception $e) {
            Log::error('Logout exception', [
                'message' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Refresh access token
     */
    public function refreshToken($refreshToken)
    {
        try {
            if (empty($refreshToken)) {
                return [
                    'success' => false,
                    'message' => 'Refresh token is required'
                ];
            }

            $params = [
                'client_id' => $this->clientId,
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken
            ];
            if (!empty($this->clientSecret)) {
                $params['client_secret'] = $this->clientSecret;
            }

            $response = Http::timeout($this->timeout)
                ->asForm()
                ->post(
                    "{$this->baseUrl}/realms/{$this->realm}/protocol/openid-connect/token",
                    $params
                );

            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('Token refreshed successfully');
                
                return [
                    'success' => true,
                    'data' => [
                        'access_token' => $data['access_token'],
                        'refresh_token' => $data['refresh_token'] ?? $refreshToken,
                        'expires_in' => $data['expires_in'] ?? 300,
                        'refresh_expires_in' => $data['refresh_expires_in'] ?? 1800,
                        'token_type' => $data['token_type'] ?? 'Bearer'
                    ]
                ];
            }

            return $this->handleErrorResponse($response, 'refresh_token');

        } catch (\Exception $e) {
            Log::error('Refresh token exception', [
                'message' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to refresh authentication'
            ];
        }
    }

    /**
     * Validate access token
     */
    public function validateToken($accessToken)
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withToken($accessToken)
                ->get("{$this->baseUrl}/realms/{$this->realm}/protocol/openid-connect/userinfo");

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }
}