<?php
// Prevent any output before JSON
ob_start();

session_start();

// Clear any output buffer
ob_end_clean();

// Set headers first
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// Disable error display
ini_set('display_errors', 0);
error_reporting(0);

try {
    // Include config
    require_once '../php/config.php';
    
    // Pastikan user sudah login
    if (!isset($_SESSION['role'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'User tidak terautentikasi.'
        ]);
        exit;
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
            remarks,
            status,
            SUM(qty) AS total_qty
        FROM request_materials
        WHERE status = 'Waiting Approval'
    ";
    
    // Jika role bukan admin, tambahkan filter berdasarkan wh_name
    if ($role !== 'admin') {
        // Pastikan wh_name ada di session
        if (!isset($_SESSION['wh_name']) || empty($_SESSION['wh_name'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Warehouse name tidak ditemukan di session.'
            ]);
            exit;
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
            remarks,
            status
        ORDER BY DATE(request_date) DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    
    // Bind parameter jika role bukan admin
    if ($role !== 'admin') {
        $stmt->bindParam(':wh_name', $_SESSION['wh_name'], PDO::PARAM_STR);
    }
    
    $stmt->execute();
    
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $data,
        'count' => count($data)
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred.',
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

exit;