<?php
// File: modules/generate_order_number.php
session_start();
header('Content-Type: application/json');

// Include koneksi database
include '../php/config.php';

try {
    // Ambil wh_id dari session
    $wh_id = isset($_SESSION['wh_id']) ? $_SESSION['wh_id'] : '';
    
    if (empty($wh_id)) {
        throw new Exception('WH ID tidak ditemukan dalam session');
    }
    
    // Format tanggal hari ini (YYYYMMDD)
    $today = date('Ymd');
    
    // Ambil sequence terakhir tanpa mengunci atau mengupdate
    $prefix = 'FIS-IN';
    $query = "SELECT sequence FROM transaction_sequence 
              WHERE prefix = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$prefix]);
    $sequenceRow = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Tentukan sequence berikutnya untuk preview
    $nextSequence = $sequenceRow ? $sequenceRow['sequence'] + 1 : 1;
    
    // Format nomor urut menjadi 4 digit
    $sequenceNumber = str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
    
    // Gabungkan menjadi order number lengkap
    $newPoNumber = "PO-INT/{$wh_id}/{$today}/{$sequenceNumber}";
    
    // Response sukses
    echo json_encode([
        'success' => true,
        'po_number' => $newPoNumber,
        'sequence' => $nextSequence
    ]);
    
} catch (Exception $e) {
    // Response error
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>