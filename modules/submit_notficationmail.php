<?php
session_start();
header('Content-Type: application/json');

// Koneksi database (sesuaikan dengan konfigurasi Anda)
require_once '../php/config.php'; // atau include file koneksi database Anda

// Fungsi untuk sanitasi input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Fungsi untuk validasi email
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

try {
    // Cek apakah request method adalah POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Ambil dan sanitasi data dari form
    $nama = isset($_POST['nama']) ? sanitize_input($_POST['nama']) : '';
    $email = isset($_POST['email']) ? sanitize_input($_POST['email']) : '';

    // Validasi input
    if (empty($nama)) {
        throw new Exception('Nama tidak boleh kosong');
    }

    if (empty($email)) {
        throw new Exception('Email tidak boleh kosong');
    }

    if (!validate_email($email)) {
        throw new Exception('Format email tidak valid');
    }

    // Cek apakah email sudah ada di database
    $checkStmt = $conn->prepare("SELECT id FROM notification_mail WHERE email = ?");
    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        throw new Exception('Email sudah terdaftar');
    }
    $checkStmt->close();

    // Insert data recipient baru
    $stmt = $conn->prepare("INSERT INTO notification_mail (nama, email, status, created_at, updated_at) VALUES (?, ?, 'active', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
    $stmt->bind_param("ss", $nama, $email);
    
    if ($stmt->execute()) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Recipient berhasil ditambahkan'
        ]);
    } else {
        throw new Exception('Gagal menambahkan recipient: ' . $stmt->error);
    }
    $stmt->close();

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} finally {
    // Tutup koneksi database
    if (isset($conn)) {
        $conn->close();
    }
}
?>