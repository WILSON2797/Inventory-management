<?php
session_start();
require_once '../php/config.php';

header('Content-Type: application/json');

// Cek login
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized access'
    ]);
    exit;
}

try {
    // Validasi input
    if (!isset($_POST['id']) || !isset($_POST['status'])) {
        throw new Exception('Data tidak lengkap!');
    }

    $id = intval($_POST['id']);
    $status = trim($_POST['status']);

    // Validasi status
    $allowedStatus = ['active', 'inactive'];
    if (!in_array($status, $allowedStatus)) {
        throw new Exception('Status tidak valid!');
    }

    // Cek apakah recipient exists
    $checkSql = "SELECT id, nama, status FROM email_recipients WHERE id = ?";
    $checkStmt = $conn->prepare($checkSql);
    
    if (!$checkStmt) {
        throw new Exception('Prepare statement gagal: ' . $conn->error);
    }
    
    $checkStmt->bind_param("i", $id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Data recipient tidak ditemukan!');
    }

    $recipient = $result->fetch_assoc();
    $oldStatus = $recipient['status'];

    // Cek jika status sudah sama
    if ($oldStatus === $status) {
        throw new Exception('Status sudah ' . ($status === 'active' ? 'aktif' : 'nonaktif') . '!');
    }

    // Update status - PERBAIKAN: hanya 2 parameter (status dan id)
    $updateSql = "UPDATE email_recipients SET 
                  status = ?,
                  updated_at = NOW()
                  WHERE id = ?";
    
    $updateStmt = $conn->prepare($updateSql);
    
    if (!$updateStmt) {
        throw new Exception('Prepare statement gagal: ' . $conn->error);
    }
    
    // PERBAIKAN: bind_param hanya "si" (string, integer) bukan "sii"
    $updateStmt->bind_param("si", $status, $id);

    if (!$updateStmt->execute()) {
        throw new Exception('Gagal mengubah status: ' . $updateStmt->error);
    }

    // Cek apakah ada baris yang ter-update
    if ($updateStmt->affected_rows === 0) {
        throw new Exception('Tidak ada perubahan yang dilakukan!');
    }

    $statusText = $status === 'active' ? 'diaktifkan' : 'dinonaktifkan';
    
    echo json_encode([
        'status' => 'success',
        'message' => "Status '{$recipient['nama']}' berhasil {$statusText}!",
        'data' => [
            'id' => $id,
            'nama' => $recipient['nama'],
            'old_status' => $oldStatus,
            'new_status' => $status
        ]
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($checkStmt)) $checkStmt->close();
    if (isset($updateStmt)) $updateStmt->close();
    if (isset($conn)) $conn->close();
}
?>