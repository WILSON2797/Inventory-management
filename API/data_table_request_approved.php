<?php
session_start();
require_once '../php/config.php';
header('Content-Type: application/json');

try {
    // Pastikan user sudah login
    if (!isset($_SESSION['role'])) {
        throw new Exception('User tidak terautentikasi.');
    }

    $role = $_SESSION['role'];
    
    // Query dasar
    $sql = "
        SELECT 
            request_number,
            request_date,
            approved_date,
            approved_by,
            request_stock_type,
            wh_name,
            project_name,
            status,
            approved_note,
            SUM(qty) AS total_qty
        FROM request_materials
        WHERE status = 'Approved'
    ";
    
    // Jika role bukan admin, tambahkan filter berdasarkan wh_name
    if ($role !== 'admin') {
        // Pastikan wh_name ada di session
        if (!isset($_SESSION['wh_name']) || empty($_SESSION['wh_name'])) {
            throw new Exception('Warehouse name tidak ditemukan di session.');
        }
        $sql .= " AND wh_name = :wh_name";
    }
    
    $sql .= "
        GROUP BY request_number, request_date, approved_date, approved_by, request_stock_type, wh_name, project_name, status, approved_note
        ORDER BY approved_date ASC
    ";
    
    $stmt = $pdo->prepare($sql);
    
    // Bind parameter jika role bukan admin
    if ($role !== 'admin') {
        $stmt->bindParam(':wh_name', $_SESSION['wh_name'], PDO::PARAM_STR);
    }
    
    $stmt->execute();

    echo json_encode([
        'success' => true,
        'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load approved requests: ' . $e->getMessage()
    ]);
}