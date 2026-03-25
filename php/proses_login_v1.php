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
        $stmt = $conn->prepare("SELECT nama, username, password, role, wh_name, project_name, wh_id, profile_picture FROM data_username WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if (password_verify($password, $row['password'])) {
                // Simpan data user ke sesi
                $_SESSION['nama'] = $row['nama'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['role'] = $row['role'];
                $_SESSION['wh_id'] = $row['wh_id'];
                $_SESSION['wh_name'] = $row['wh_name'];
                $_SESSION['project_name'] = $row['project_name'];
                $_SESSION['profile_picture'] = $row['profile_picture'] ?? '';

                error_log("Login berhasil - Username: {$row['username']}, Role: {$row['role']}, WHID: {$row['wh_id']}, WH_NAME: {$row['wh_name']}");

                echo json_encode([
                    'status' => 'success',
                    'message' => 'Login berhasil!',
                    'role' => $row['role'],
                    'wh_id' => $row['wh_id'],
                    'wh_name' => $row['wh_name'],
                    'project_name' => $row['project_name'],
                    'profile_picture' => $row['profile_picture'] ?? ''
                ]);
            } else {
                error_log("Login gagal - Password salah untuk username: $username");
                echo json_encode(['status' => 'error', 'message' => 'Password salah!']);
            }
        } else {
            error_log("Login gagal - Username tidak ditemukan: $username");
            echo json_encode(['status' => 'error', 'message' => 'Username tidak ditemukan!']);
        }
    } catch (Exception $e) {
        error_log("Error di proses_login.php: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan server: ' . $e->getMessage()]);
    }

    $conn->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Metode tidak diizinkan']);
}
?>