<?php
session_start();
// Gunakan file koneksi yang sama
include '../php/config.php';

header('Content-Type: application/json'); // Pastikan response JSON

if (!isset($_SESSION['username'])) {
    echo json_encode(['status' => 'error', 'message' => 'Anda harus login untuk mengakses halaman ini.']);
    exit();
}

// Respons default
$response = [
    'status' => 'error',
    'message' => 'Terjadi kesalahan saat memproses data'
];

// Ambil dan sanitasi input
$nama = mysqli_real_escape_string($conn, $_POST['nama']);
$email = mysqli_real_escape_string($conn, $_POST['email']);
$username = mysqli_real_escape_string($conn, $_POST['username']);
$password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash password
$wh_name = mysqli_real_escape_string($conn, $_POST['wh_name']);
$project_name = mysqli_real_escape_string($conn, $_POST['project_name']);

// Cek apakah username atau email sudah ada
$query = "SELECT * FROM data_username WHERE username = '$username' OR email = '$email'";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Username atau email sudah digunakan!']);
    exit();
}

// Query untuk mengambil wh_id berdasarkan wh_name
$query_wh = "SELECT wh_id FROM warehouses WHERE wh_name = '$wh_name'";
$result_wh = $conn->query($query_wh);

if ($result_wh->num_rows > 0) {
    $row_wh = $result_wh->fetch_assoc();
    $wh_id = $row_wh['wh_id'];

    // Insert data user baru dengan wh_id dan wh_name
    $insertQuery = "INSERT INTO data_username (nama, email, username, password, wh_id, wh_name, project_name) 
                    VALUES ('$nama', '$email', '$username', '$password', '$wh_id', '$wh_name', '$project_name')";
    if ($conn->query($insertQuery) === TRUE) {
        echo json_encode(['status' => 'success', 'message' => 'Pendaftaran berhasil! Silakan login.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan saat mendaftar: ' . $conn->error]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Warehouse dengan nama tersebut tidak ditemukan!']);
}

$conn->close();
?>