<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
            max-width: 600px;
            width: 100%;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 32px;
        }
        .welcome-text {
            color: #667eea;
            font-size: 24px;
            margin-bottom: 30px;
        }
        .user-info {
            background: #f7f7f7;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .info-row {
            display: flex;
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: bold;
            color: #555;
            width: 150px;
        }
        .info-value {
            color: #333;
            flex: 1;
        }
        .logout-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 10px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.2s;
        }
        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Welcome to Dashboard!</h1>
        <p class="welcome-text">Hello, {{ $user->name ?? $user->preferred_username ?? 'User' }}! 👋</p>
        
        <div class="user-info">
            <h2 style="margin-bottom: 15px; color: #555;">Your Profile Information</h2>
            
            @if($user->name)
            <div class="info-row">
                <span class="info-label">Name:</span>
                <span class="info-value">{{ $user->name }}</span>
            </div>
            @endif
            
            @if($user->email)
            <div class="info-row">
                <span class="info-label">Email:</span>
                <span class="info-value">{{ $user->email }}</span>
            </div>
            @endif
            
            @if($user->preferred_username)
            <div class="info-row">
                <span class="info-label">Username:</span>
                <span class="info-value">{{ $user->preferred_username }}</span>
            </div>
            @endif
            
            @if($user->given_name)
            <div class="info-row">
                <span class="info-label">First Name:</span>
                <span class="info-value">{{ $user->given_name }}</span>
            </div>
            @endif
            
            @if($user->family_name)
            <div class="info-row">
                <span class="info-label">Last Name:</span>
                <span class="info-value">{{ $user->family_name }}</span>
            </div>
            @endif
            
            <div class="info-row">
                <span class="info-label">Email Verified:</span>
                <span class="info-value">{{ $user->email_verified ? 'Yes ✓' : 'No ✗' }}</span>
            </div>
        </div>
        
        <a href="{{ route('keycloak.logout') }}" class="logout-btn">Logout</a>
    </div>
</body>
</html>