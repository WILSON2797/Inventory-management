<?php
session_start();

// Periksa apakah pengguna adalah admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Akses ditolak']);
    exit;
}

// Periksa apakah request adalah POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Metode tidak diizinkan']);
    exit;
}

// Include file konfigurasi database
require_once '../php/config.php';

// Ambil dan sanitasi input
$user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
$new_password = $_POST['new_password'] ?? '';

// Validasi input
if ($user_id === false || $user_id === null || empty($new_password)) {
    http_response_code(400);
    echo json_encode(['error' => 'Data tidak valid']);
    exit;
}

// Validasi panjang password (minimal 8 karakter)
if (strlen($new_password) < 8) {
    http_response_code(400);
    echo json_encode(['error' => 'Password baru harus minimal 8 karakter']);
    exit;
}

try {
    // Hash password baru
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // Periksa apakah user_id ada di database
    $stmt = $pdo->prepare("SELECT id FROM data_username WHERE id = ?");
    $stmt->execute([$user_id]);
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Pengguna tidak ditemukan']);
        exit;
    }

    // Update password di database
    $stmt = $pdo->prepare("UPDATE data_username SET password = ? WHERE id = ?");
    $stmt->execute([$hashed_password, $user_id]);

    // Kembalikan respons sukses
    header('Content-Type: application/json');
    echo json_encode(['success' => 'Password berhasil direset']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Gagal mereset password: ' . $e->getMessage()]);
    exit;
}
?>