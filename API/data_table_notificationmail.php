<?php
session_start();
include '../php/config.php'; // Sesuaikan dengan path file koneksi database Anda

// Set header keamanan
header('Content-Type: application/json');

// Cek apakah user sudah login
if (!isset($_SESSION['username'])) {
    echo json_encode(['status' => 'error', 'message' => 'User tidak terautentikasi'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit();
}

// Siapkan query untuk mengambil semua data SKU tanpa filter
$query = "SELECT * FROM notification_mail";
$stmt = $conn->prepare($query);

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