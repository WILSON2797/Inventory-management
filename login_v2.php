<?php
session_start();

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
}

// Fungsi getAssetUrl tetap sama
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
    <!-- Meta tags untuk kontrol cache -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">

    <title>Inventory Management</title>
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
                <!-- <div class="social-container">
                    <a href="#"><span>Apps</span></a>
                    <a href="#"><span>G</span></a>
                    <a href="#"><span>in</span></a>
                </div> -->
                <p>Silahkan Login</p>
                <div class="form-group">
                    <input type="text" id="login-username" name="username" placeholder=" " required>
                    <label for="login-username">Username</label>
                </div>
                <div class="form-group">
                    <input type="password" id="login-password" name="password" placeholder=" " required>
                    <label for="login-password">Password</label>
                </div>
                <div class="forgot-password">
                    <a href="#">Lupa password?</a>
                </div>
                <button type="submit">Login</button>
            </form>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="<?php echo getAssetUrl('assets/js/toastr-init.js'); ?>"></script>
    <script src="<?php echo getAssetUrl('assets/js/auth.js'); ?>"></script>
    <script src="<?php echo getAssetUrl('assets/js/login.js'); ?>"></script>
</body>
</html>