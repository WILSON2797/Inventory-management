<?php
include '../php/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$id = $_POST['id'] ?? '';
$status_stock = $_POST['status_stock'] ?? '';
$reason = $_POST['reason'] ?? null; // tambahan: alasan freeze (opsional)

// Validasi data
if (empty($id) || !in_array($status_stock, ['Active', 'Freeze'])) {
    echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
    exit;
}

try {
    // Jika status = freeze dan ada alasan, simpan alasan juga
    if ($status_stock === 'Freeze' && !empty($reason)) {
        $sql = "UPDATE stock 
                SET status_stock = ?, freeze_reason = ?, last_updated = NOW()
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $status_stock, $reason, $id);
    } else {
        // Jika unfreeze (atau tidak ada alasan)
        $sql = "UPDATE stock 
                SET status_stock = ?, freeze_reason = NULL, last_updated = NOW()
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $status_stock, $id);
    }

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Status stock berhasil diperbarui'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Gagal memperbarui status stock'
        ]);
    }

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
