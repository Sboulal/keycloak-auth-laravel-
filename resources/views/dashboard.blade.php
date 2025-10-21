{{-- <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen p-8">
        <div class="bg-white p-6 rounded-lg shadow-md max-w-2xl mx-auto">
            <h1 class="text-3xl font-bold mb-4 text-gray-800">Dashboard</h1>
            <div class="mb-6">
                <p class="text-lg">Welcome, <span class="font-semibold text-blue-600">{{ Auth::user()->name }}</span>!</p>
                <p class="text-gray-600">Email: {{ Auth::user()->email }}</p>
            </div>
            
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="bg-red-500 text-white px-6 py-2 rounded-lg hover:bg-red-600 transition">
                    Logout
                </button>
            </form>
        </div>
    </div>
</body>
</html> --}}

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <h1 class="text-xl font-bold text-gray-800">{{ config('app.name') }}</h1>
                    </div>
                    <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                        <a href="{{ route('dashboard') }}" class="border-indigo-500 text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Dashboard
                        </a>
                    </div>
                </div>
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <span class="text-gray-700 text-sm font-medium mr-4">
                            {{ Auth::user()->name }}
                        </span>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4">Welcome to your Dashboard!</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                        <div class="bg-blue-50 p-6 rounded-lg">
                            <h3 class="text-lg font-semibold text-blue-900 mb-2">User Information</h3>
                            <div class="space-y-2">
                                <p class="text-sm text-gray-700">
                                    <span class="font-medium">Name:</span> {{ Auth::user()->name }}
                                </p>
                                <p class="text-sm text-gray-700">
                                    <span class="font-medium">Email:</span> {{ Auth::user()->email }}
                                </p>
                                <p class="text-sm text-gray-700">
                                    <span class="font-medium">Keycloak ID:</span> {{ Auth::user()->keycloak_id ?? 'N/A' }}
                                </p>
                                <p class="text-sm text-gray-700">
                                    <span class="font-medium">Verified:</span> 
                                    @if(Auth::user()->email_verified_at)
                                        <span class="text-green-600">✓ Verified</span>
                                    @else
                                        <span class="text-red-600">✗ Not Verified</span>
                                    @endif
                                </p>
                            </div>
                        </div>

                        <div class="bg-green-50 p-6 rounded-lg">
                            <h3 class="text-lg font-semibold text-green-900 mb-2">Session Information</h3>
                            <div class="space-y-2">
                                <p class="text-sm text-gray-700">
                                    <span class="font-medium">Login Method:</span> 
                                    @if(session('keycloak_access_token'))
                                        Keycloak API
                                    @else
                                        Keycloak OAuth
                                    @endif
                                </p>
                                <p class="text-sm text-gray-700">
                                    <span class="font-medium">Member Since:</span> 
                                    {{ Auth::user()->created_at->format('M d, Y') }}
                                </p>
                                @if(session('keycloak_access_token'))
                                    <p class="text-sm text-gray-700">
                                        <span class="font-medium">Token Status:</span> 
                                        <span class="text-green-600">Active</span>
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 bg-gray-50 p-6 rounded-lg">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <button class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                                Update Profile
                            </button>
                            <button class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                                Change Password
                            </button>
                            <button class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                                View Settings
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Add notification popup component -->
    @auth
        @include('components.notification-popup')
    @endauth
</body>
</html>
