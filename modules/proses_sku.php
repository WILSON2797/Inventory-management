<?php
include '../php/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $item_code = $_POST['item_code'];
    $item_description = $_POST['item_description'];
    $volume = $_POST['volume'];
    $uom = $_POST['uom'];
    $project = $_POST['project'];

    try {
        // Cek apakah item_code sudah ada
        $check_stmt = $conn->prepare("SELECT COUNT(*) FROM master_sku WHERE item_code = ? AND project = ?");
        $check_stmt->bind_param("ss", $item_code, $project);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $count = $check_result->fetch_row()[0];
        $check_stmt->close();

        if ($count > 0) {
            throw new Exception("Item Code $item_code sudah terdaftar!");
        }

        // Insert ke master_sku
        $sql = "INSERT INTO master_sku (item_code, item_description, volume, uom, project) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $item_code, $item_description, $volume, $uom, $project);
        $stmt->execute();

        echo json_encode([
            'status' => 'success',
            'message' => 'SKU berhasil ditambahkan!'
        ]);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method Not Allowed'
    ]);
}

$conn->close();
?>