<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MYREALM</title>
    <style>
        /* Your existing styles */
    </style>
</head>
<body>
    <div class="container">
        <h1>MYREALM</h1>
        <div class="login-box">
            <h2>Sign in to your account</h2>
            
           
            <form action="{{ route('keycloak.login') }}" method="GET">
               
                <button type="submit" class="btn-signin">Sign In with Keycloak</button>
            </form>
        </div>
    </div>
</body>
</html>