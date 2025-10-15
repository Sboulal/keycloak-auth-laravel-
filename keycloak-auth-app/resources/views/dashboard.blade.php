<!DOCTYPE html>
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
</html>