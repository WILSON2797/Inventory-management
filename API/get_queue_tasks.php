<?php
session_start();
include '../php/config.php';

// Cek autentikasi
if (!isset($_SESSION['username'])) {
    echo json_encode(['status' => 'error', 'message' => 'User tidak terautentikasi']);
    exit();
}

$username = $_SESSION['username'];

// Cek role user - pastikan kolom 'role' ada di session atau query dari database
$userRole = isset($_SESSION['role']) ? $_SESSION['role'] : null;

// Jika role belum ada di session, ambil dari database
if (!$userRole) {
    $roleQuery = "SELECT role FROM data_username WHERE username = ?";
    $roleStmt = $conn->prepare($roleQuery);
    $roleStmt->bind_param('s', $username);
    $roleStmt->execute();
    $roleResult = $roleStmt->get_result();
    
    if ($roleRow = $roleResult->fetch_assoc()) {
        $userRole = $roleRow['role'];
        $_SESSION['role'] = $userRole; // Simpan ke session untuk request selanjutnya
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Role user tidak ditemukan']);
        exit();
    }
    $roleStmt->close();
}

// Query berdasarkan role
if ($userRole === 'admin' || $userRole === 'superuser') {
    // Admin bisa melihat semua data
    $query = "SELECT id, task_type, file_name, status, success_count, error_message, created_at, report_path, username 
              FROM queue_task ORDER BY created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
} else {
    // User biasa hanya bisa melihat data sendiri
    $query = "SELECT id, task_type, file_name, status, success_count, error_message, created_at, report_path, username 
              FROM queue_task WHERE username = ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $username);
    $stmt->execute();
}

$result = $stmt->get_result();
$data = [];
while ($row = $result->fetch_assoc()) {
    $row['file_name'] = basename($row['file_name']);
    $data[] = $row;
}

echo json_encode($data);
$stmt->close();
$conn->close();
?>