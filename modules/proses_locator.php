<?php
session_start();
require_once '../php/config.php'; // Sesuaikan dengan path file koneksi database Anda

// Cek apakah pengguna sudah login dan memiliki wh_name di session
if (!isset($_SESSION['wh_name'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['status' => 'error', 'message' => 'Akses tidak diizinkan']);
    exit;
}

// Tangani request POST untuk menambah locator baru
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $locator = trim($_POST['locator'] ?? '');
    $locator_description = trim($_POST['locator_description'] ?? '');
    $wh_name = $_SESSION['wh_name'];

    // Validasi input
    if (empty($locator) || empty($locator_description)) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['status' => 'error', 'message' => 'Semua kolom wajib diisi']);
        exit;
    }

    try {
        // Cek apakah locator sudah ada
        $check_query = "SELECT COUNT(*) FROM master_locator WHERE locator = ? AND wh_name = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("ss", $locator, $wh_name);
        $check_stmt->execute();
        $check_stmt->bind_result($count);
        $check_stmt->fetch();
        $check_stmt->close();

        if ($count > 0) {
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['status' => 'error', 'message' => 'Locator sudah ada']);
            exit;
        }

        // Simpan data locator baru
        $insert_query = "INSERT INTO master_locator (locator, locator_description, wh_name) VALUES (?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("sss", $locator, $locator_description, $wh_name);
        
        if ($insert_stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Locator berhasil ditambahkan']);
        } else {
            header('HTTP/1.1 500 Internal Server Error');
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan data']);
        }
        $insert_stmt->close();
    } catch (Exception $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['status' => 'error', 'message' => 'Kesalahan server: ' . $e->getMessage()]);
    }
    exit;
}

// Tangani request GET untuk mengambil data locator
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $wh_name = $_SESSION['wh_name'];
    
    try {
        $query = "SELECT locator, locator_description, wh_name FROM master_locator WHERE wh_name = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $wh_name);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $locators = [];
        while ($row = $result->fetch_assoc()) {
            $locators[] = $row;
        }
        
        echo json_encode(['status' => 'success', 'data' => $locators]);
        $stmt->close();
    } catch (Exception $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['status' => 'error', 'message' => 'Kesalahan server: ' . $e->getMessage()]);
    }
    exit;
}

$conn->close();
?>