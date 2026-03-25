<?php
session_start();
require_once '../php/config.php';

header('Content-Type: application/json');

try {
    // Pastikan session wh_id ada
    if (!isset($_SESSION['wh_id']) || empty($_SESSION['wh_id'])) {
        throw new Exception('Warehouse ID tidak ditemukan di session.');
    }

    // Ambil WH ID dari session
    $wh_id = strtoupper($_SESSION['wh_id']); // contoh: HO01

    // Ambil tanggal hari ini dalam format YYYYMMDD
    $date = date('Ymd');

    // Prefix untuk pencarian (tanpa tanggal)
    $prefixSearch = "FIS-$wh_id-";
    
    // Prefix lengkap untuk nomor request baru
    $prefixFull = "FIS-$wh_id-$date";

    // Cari nomor request terakhir untuk WH ini (tidak peduli tanggal)
    $stmt = $pdo->prepare("
        SELECT request_number 
        FROM request_materials 
        WHERE request_number LIKE ? 
        ORDER BY request_number DESC 
        LIMIT 1
    ");
    $stmt->execute(["$prefixSearch%"]);
    $lastRequest = $stmt->fetch(PDO::FETCH_ASSOC);

    // Tentukan urutan baru
    $sequence = 1;
    if ($lastRequest) {
        // Ambil 3 digit terakhir
        $lastSequence = (int)substr($lastRequest['request_number'], -3);
        $sequence = $lastSequence + 1;
    }

    // Buat nomor request baru dengan format: FIS-WH_ID-YYYYMMDD-XXX
    $requestNumber = sprintf("%s-%03d", $prefixFull, $sequence);

    // Keluarkan hasil JSON
    echo json_encode([
        'success' => true,
        'request_number' => $requestNumber
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to generate request number: ' . $e->getMessage()
    ]);
}
?>