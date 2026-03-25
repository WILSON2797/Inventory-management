<?php
include '../php/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['id'])) {
    $id = $_GET['id'];

    try {
        $sql = "SELECT id, item_code, item_description, volume, uom, project FROM master_sku WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $data = $result->fetch_assoc();
            echo json_encode([
                'status' => 'success',
                'data' => $data
            ]);
        } else {
            throw new Exception("SKU tidak ditemukan!");
        }
        
        $stmt->close();
    } catch (Exception $e) {
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
} else {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid Request'
    ]);
}

$conn->close();
?>