<?php
require_once '../php/config.php';

header('Content-Type: application/json');

try {
    $request_number = $_POST['request_number'] ?? '';

    if (empty($request_number)) {
        throw new Exception('Request number is required.');
    }

    $stmt = $pdo->prepare("
        SELECT item_code, item_description, qty, uom , remarks
        FROM request_materials
        WHERE request_number = ?
    ");
    $stmt->execute([$request_number]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($data);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>