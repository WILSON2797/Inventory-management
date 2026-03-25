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
            DATE(request_date) AS request_date,
            request_stock_type,
            request_by,
            wh_name,
            project_name,
            status,
            SUM(qty) AS total_qty
        FROM request_materials
        WHERE status = 'Waiting Approval'
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
        GROUP BY 
            request_number,
            DATE(request_date),
            request_stock_type,
            request_by,
            wh_name,
            project_name,
            status
        ORDER BY DATE(request_date) DESC
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
        'message' => 'Failed to load pending requests: ' . $e->getMessage()
    ]);
}