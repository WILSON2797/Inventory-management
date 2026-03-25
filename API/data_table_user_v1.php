<?php
session_start();

// Periksa apakah pengguna adalah admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Akses ditolak']);
    exit;
}

// Include file konfigurasi database
require_once '../php/config.php';

try {
    // Query untuk mengambil semua data user (admin bisa melihat semua)
    $query = "SELECT id, nama, username, email, wh_name, project_name, role FROM data_username";
    
    // Siapkan dan jalankan query menggunakan PDO
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Sanitasi data untuk mencegah XSS
    $sanitized_users = [];
    foreach ($users as $user) {
        $sanitized_users[] = [
            'id' => $user['id'],
            'nama' => htmlspecialchars($user['nama']),
            'username' => htmlspecialchars($user['username']),
            'email' => htmlspecialchars($user['email']),
            'wh_name' => htmlspecialchars($user['wh_name']),
            'project_name' => htmlspecialchars($user['project_name'] ?? ''),
            'role' => htmlspecialchars($user['role'])
        ];
    }

    // Kembalikan data dalam format yang diharapkan DataTables
    header('Content-Type: application/json');
    echo json_encode([
        'data' => $sanitized_users
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Gagal mengambil data: ' . $e->getMessage()]);
    exit;
}
?>