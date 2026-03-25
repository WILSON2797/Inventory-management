<?php
session_start();
include 'config.php';

// 1. VALIDASI METHOD
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Metode tidak diizinkan']);
    exit();
}

// 2. VALIDASI CSRF TOKEN (Simple)
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['status' => 'error', 'message' => 'Token tidak valid. Refresh halaman.']);
    exit();
}

// 3. SANITASI INPUT
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'Username dan password wajib diisi']);
    exit();
}

// 4. RATE LIMITING SEDERHANA (Pakai session)
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt_time'] = time();
}

// Reset jika sudah lewat 15 menit
if (time() - $_SESSION['last_attempt_time'] > 900) {
    $_SESSION['login_attempts'] = 0;
}

// Cek apakah terlalu banyak percobaan
if ($_SESSION['login_attempts'] >= 5) {
    $waitTime = 900 - (time() - $_SESSION['last_attempt_time']);
    $waitMinutes = ceil($waitTime / 60);
    echo json_encode([
        'status' => 'error', 
        'message' => "Terlalu banyak percobaan. Coba lagi dalam {$waitMinutes} menit."
    ]);
    exit();
}

try {
    // 5. QUERY DATABASE (Prepared Statement)
    $stmt = $conn->prepare("
        SELECT id, nama, username, password, role, profile_picture 
        FROM data_username 
        WHERE username = ? AND is_active = 1
    ");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        // Increment attempt
        $_SESSION['login_attempts']++;
        $_SESSION['last_attempt_time'] = time();
        
        echo json_encode([
            'status' => 'error', 
            'message' => 'Username atau password salah' // TIDAK SPESIFIK
        ]);
        exit();
    }

    $user = $result->fetch_assoc();

    // 6. VERIFIKASI PASSWORD
    if (!password_verify($password, $user['password'])) {
        // Increment attempt
        $_SESSION['login_attempts']++;
        $_SESSION['last_attempt_time'] = time();
        
        echo json_encode([
            'status' => 'error', 
            'message' => 'Username atau password salah'
        ]);
        exit();
    }

    // ✅ LOGIN BERHASIL

    // 7. REGENERATE SESSION ID (Prevent Session Fixation)
    session_regenerate_id(true);

    // 8. SET SESSION DATA
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['nama'] = $user['nama'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['profile_picture'] = $user['profile_picture'] ?? '';
    $_SESSION['login_time'] = time();
    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR']; // Track IP

    // Hapus data lama
    unset($_SESSION['wh_id'], $_SESSION['wh_name'], $_SESSION['project_name']);
    
    // Reset login attempts
    $_SESSION['login_attempts'] = 0;

    echo json_encode([
        'status' => 'success',
        'message' => 'Login berhasil!',
        'redirect' => 'select_wh_project.php'
    ]);

} catch (Exception $e) {
    error_log("Login Error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Terjadi kesalahan. Silakan coba lagi.'
    ]);
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($conn)) $conn->close();
}
?>