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
$wh_name = $_SESSION['wh_name'] ?? null; // Gunakan null coalescing untuk menghindari error jika wh_id tidak ada

// Validasi wh_id
if (empty($wh_name)) {
    echo json_encode(['status' => 'error', 'message' => 'wh_id tidak ditemukan di session']);
    exit();
}

// Siapkan query berdasarkan role
if ($role == 'admin') {
    $query = "SELECT * FROM stock"; // Admin bisa melihat semua data
    $stmt = $conn->prepare($query);
} else {
    // Karena wh_id adalah string, gunakan tanda kutip
    $query = "SELECT * FROM stock WHERE wh_name = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $wh_name); // "s" karena wh_id adalah string

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
echo json_encode(['status' => 'success', 'data' => $data],
JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
