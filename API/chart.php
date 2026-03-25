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
$wh_name_session = $_SESSION['wh_name'] ?? null;
$selected_wh = isset($_GET['wh_name']) ? $_GET['wh_name'] : null;

// Tentukan wh_name untuk filter
$filter_wh = ($role == 'admin' && !$selected_wh) ? null : ($selected_wh ?: $wh_name_session);

// Siapkan query berdasarkan role dan filter
if ($role == 'admin' && !$selected_wh) {
    $query = "SELECT SUM(qty_inbound) as total_inbound, SUM(qty_allocated) as total_allocated, 
              SUM(qty_out) as total_out, SUM(stock_on_hand) as total_on_hand, 
              SUM(stock_balance) as total_balance FROM stock";
    $stmt = $conn->prepare($query);
} else {
    $query = "SELECT SUM(qty_inbound) as total_inbound, SUM(qty_allocated) as total_allocated, 
              SUM(qty_out) as total_out, SUM(stock_on_hand) as total_on_hand, 
              SUM(stock_balance) as total_balance FROM stock WHERE wh_name = ?";
    $stmt = $conn->prepare($query);
    if ($filter_wh !== null) {
        $stmt->bind_param("s", $filter_wh); // Hanya bind jika ada filter
    }
}

// Eksekusi query
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

// Siapkan array untuk menyimpan data
$data = $row ? [$row] : [];

// Tutup statement dan koneksi database
$stmt->close();
$conn->close();

// Kirim data sebagai JSON
echo json_encode(['status' => 'success', 'data' => $data], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>