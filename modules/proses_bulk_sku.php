<?php
session_start();
require_once '../php/config.php'; // Sesuaikan dengan path file koneksi database
require_once '../vendor/autoload.php'; // Memuat PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\IOFactory;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['excel_file'])) {
    $file = $_FILES['excel_file'];

    // Validasi file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Gagal mengunggah file!']);
        exit;
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    if (!in_array(strtolower($extension), ['xlsx', 'xls'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'File harus berformat Excel (.xlsx atau .xls)!']);
        exit;
    }

    try {
        // Baca file Excel
        $spreadsheet = IOFactory::load($file['tmp_name']);
        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray();

        // Validasi header
        $header = array_map('trim', array_map('strtolower', $data[0])); // Baris pertama sebagai header
        $expected_header = ['item_code', 'item_description', 'volume', 'uom', 'project'];
        if ($header !== $expected_header) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Format header Excel tidak sesuai! Harus: item_code, item_description, volume, uom, project']);
            exit;
        }

        $success_count = 0;
        $error_messages = [];
        $line = 2; // Mulai dari baris kedua (setelah header)

        $conn->begin_transaction();

        // Proses setiap baris data (skip header)
        for ($i = 1; $i < count($data); $i++) {
            $row = array_map('trim', $data[$i]);

            if (count($row) !== 5) {
                $error_messages[] = "Baris $line: Jumlah kolom tidak sesuai (harus 5 kolom).";
                $line++;
                continue;
            }

            $item_code = $row[0];
            $item_description = $row[1];
            $volume = $row[2];
            $uom = $row[3];
            $project = $row[4];

            // Validasi data kosong
            if (empty($item_code) || empty($item_description) || empty($volume)|| empty($uom) || empty($project)) {
                $error_messages[] = "Baris $line: Data tidak lengkap.";
                $line++;
                continue;
            }

            // Cek duplikat item_code
            $check_stmt = $conn->prepare("SELECT COUNT(*) FROM master_sku WHERE item_code = ?");
            $check_stmt->bind_param("s", $item_code);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            $count = $check_result->fetch_row()[0];
            $check_stmt->close();

            if ($count > 0) {
                $error_messages[] = "Baris $line: Item Code $item_code sudah terdaftar.";
                $line++;
                continue;
            }

            // Insert ke master_sku
            $stmt = $conn->prepare("INSERT INTO master_sku (item_code, item_description, volume, uom, project) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssdss", $item_code, $item_description, $volume, $uom, $project);
            $stmt->execute();
            $success_count++;
            $stmt->close();

            $line++;
        }

        $conn->commit();
        $response = ['status' => 'success', 'message' => "Berhasil menambahkan $success_count SKU."];
        if (!empty($error_messages)) {
            $response['warnings'] = $error_messages;
        }
        echo json_encode($response);
    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Gagal memproses bulk upload: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
}

$conn->close();
?>