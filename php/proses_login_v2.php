<?php
session_start();
include 'config.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    try {
        // HANYA ambil data dasar user (tanpa WH & Project)
        $stmt = $conn->prepare("SELECT id, nama, username, password, role, profile_picture FROM data_username WHERE username = ? AND is_active = 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if (password_verify($password, $row['password'])) {
                
                // SIMPAN USER ID & DATA DASAR KE SESSION
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['nama'] = $row['nama'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['role'] = $row['role'];
                $_SESSION['profile_picture'] = $row['profile_picture'] ?? '';

                // HAPUS SESSION LAMA (jika ada)
                unset($_SESSION['wh_id'], $_SESSION['wh_name'], $_SESSION['project_name']);

                error_log("Login sukses: {$row['username']} (ID: {$row['id']})");

                echo json_encode([
                    'status' => 'success',
                    'message' => 'Login berhasil!',
                    'redirect' => 'select_wh_project.php' // KITA KIRIM REDIRECT
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Password salah!']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Username tidak ditemukan atau tidak aktif!']);
        }
    } catch (Exception $e) {
        error_log("Error login: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan sistem.']);
    }

    $conn->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Metode tidak diizinkan']);
}
?>