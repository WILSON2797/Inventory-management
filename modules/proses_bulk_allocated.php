<?php
session_start();
include '../php/config.php';
require '../vendor/autoload.php'; // Include PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\IOFactory;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['status' => 'error', 'message' => 'Anda harus login terlebih dahulu!']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        if (!isset($_FILES['xlxs']) || $_FILES['xlxs']['error'] != UPLOAD_ERR_OK) {
            throw new Exception("File upload gagal.");
        }

        // Validasi wh_name dan project_name
        $wh_name = trim($_SESSION['wh_name'] ?? '');
        $project_name = trim($_SESSION['project_name'] ?? '');
        if (empty($wh_name) || empty($project_name)) {
            throw new Exception("WH_NAME atau Project tidak ditemukkan di session. Silakan login ulang atau pilih gudang.");
        }

        // Definisikan header yang diharapkan
        $expected_headers = ['Order Number', 'Customer', 'Lottable1', 'Lottable2', 'Destination', 'Item Code', 'Qty'];

        // Baca file Excel dari temporary path
        $temp_file = $_FILES['xlxs']['tmp_name'];
        try {
            $spreadsheet = IOFactory::load($temp_file);
            $sheet = $spreadsheet->getActiveSheet();
            $header_row = $sheet->toArray(null, true, true, true)[1]; // Ambil baris pertama (header)
            $actual_headers = array_map('trim', array_values($header_row)); // Trim dan konversi ke array

            // Validasi header
            if ($actual_headers !== $expected_headers) {
                throw new Exception("Wrong Template");
            }
        } catch (Exception $e) {
            throw new Exception("Gagal memvalidasi header file Excel: " . $e->getMessage());
        }

        // Simpan file ke folder uploads/
        $uploadDir = '../Uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $fileName = time() . '_' . basename($_FILES['xlxs']['name']);
        $filePath = $uploadDir . $fileName;
        if (!move_uploaded_file($_FILES['xlxs']['tmp_name'], $filePath)) {
            throw new Exception("Gagal menyimpan file.");
        }

        // Insert ke queue_task menggunakan nama file yang sudah diubah
        $username = $_SESSION['username'];
        $stmt_queue = $conn->prepare("INSERT INTO queue_task (task_type, file_path, file_name, username, wh_name, project_name) VALUES (?, ?, ?, ?, ?, ?)");
        $task_type = 'bulk_allocated';
        $relativeFilePath = '../Uploads/' . $fileName; // Path relatif untuk konsistensi
        $stmt_queue->bind_param("ssssss", $task_type, $relativeFilePath, $fileName, $username, $wh_name, $project_name);
        $stmt_queue->execute();

        echo json_encode(['status' => 'success', 'message' => 'Upload berhasil. Data akan diproses via antrian secara otomatis.']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Metode request tidak valid']);
}

$conn->close();
?>