<?php
session_start();
include '../php/config.php';

// Set header keamanan
header('Content-Type: application/json');

// Cek apakah user sudah login
if (!isset($_SESSION['username'])) {
    echo json_encode(['status' => 'error', 'message' => 'User tidak terautentikasi']);
    exit();
}

// Ambil data dari session
$nama = $_SESSION['nama'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];
$wh_name = $_SESSION['wh_name'] ?? null;

// Validasi wh_name
if (empty($wh_name)) {
    echo json_encode(['status' => 'error', 'message' => 'wh_name tidak ditemukan di session']);
    exit();
}

// Siapkan query berdasarkan role
if ($role == 'admin') {
    $query = "SELECT item_code, item_description, from_locator, to_locator, qty, wh_name, project_name, packing_list, reason, action_type, created_by, created_date 
              FROM stock_movement_history";
    $stmt = $conn->prepare($query);
} else {
    $query = "SELECT item_code, item_description, from_locator, to_locator, qty, wh_name, project_name, packing_list, reason, action_type, created_by, created_date 
              FROM stock_movement_history WHERE wh_name = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $wh_name);
}

// Eksekusi query
$stmt->execute();
$result = $stmt->get_result();

// Siapkan array untuk menyimpan data
$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

// Tutup statement dan koneksi database
$stmt->close();
$conn->close();

// Kirim data sebagai JSON
echo json_encode(['status' => 'success', 'data' => $data], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>