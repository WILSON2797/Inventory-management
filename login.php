<?php
session_start();

// Generate CSRF Token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Jika sudah login → langsung ke select_context
if (isset($_SESSION['user_id'])) {
    header('Location: select_wh_project.php');
    exit();
}

// Jika hanya username (lama) → logout dulu
if (isset($_SESSION['username']) && !isset($_SESSION['user_id'])) {
    session_unset();
    session_destroy();
    session_start();
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function getAssetUrl($filePath) {
    $fullPath = __DIR__ . '/' . $filePath;
    if (file_exists($fullPath)) {
        $mtime = filemtime($fullPath);
        return $filePath . '?v=' . $mtime;
    }
    return $filePath;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Security Headers -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">

    <title>Inventory Management - Login</title>
    <link rel="stylesheet" href="<?php echo getAssetUrl('assets/css/login.css'); ?>">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/toastr-custom.css?v=<?php echo getAssetUrl('css/toastr-custom.css'); ?>">
</head>
<body>
    <div class="light"></div>
    <div class="light"></div>
    <div class="light"></div>

    <div class="container" id="container">
        <!-- Form Login -->
        <div class="form-container sign-in-container">
            <form id="loginForm">
                <h1>Inventory Management</h1>
                <p>Silahkan Login</p>
                
                <!-- CSRF Token Hidden Field -->
                <input type="hidden" name="csrf_token" id="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div class="form-group">
                    <input type="text" 
                           id="login-username" 
                           name="username" 
                           placeholder=" " 
                           autocomplete="username"
                           maxlength="50"
                           required>
                    <label for="login-username">Username</label>
                </div>
                
                <div class="form-group">
                    <input type="password" 
                           id="login-password" 
                           name="password" 
                           placeholder=" "
                           autocomplete="current-password"
                           maxlength="100"
                           required>
                    <label for="login-password">Password</label>
                </div>
                
                <div class="forgot-password">
                    <a href="forgot_password.php">Lupa password?</a>
                </div>
                
                <button type="submit">Login</button>
            </form>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" 
            integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" 
            crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="<?php echo getAssetUrl('assets/js/toastr-init.js'); ?>"></script>
    <script src="<?php echo getAssetUrl('assets/js/auth.js'); ?>"></script>
    <script src="<?php echo getAssetUrl('assets/js/login.js'); ?>"></script>
</body>
</html>