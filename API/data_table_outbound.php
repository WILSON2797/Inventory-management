<?php
session_start();
include '../php/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['status' => 'error', 'message' => 'User tidak terautentikasi']);
    exit();
}

$nama = $_SESSION['nama'] ?? null;
$username = $_SESSION['username'] ?? null;
$role = $_SESSION['role'] ?? null;
$wh_name = $_SESSION['wh_name'] ?? null;

if ($role !== 'admin' && empty($wh_name)) {
    echo json_encode(['status' => 'error', 'message' => 'wh_name tidak ditemukan di session']);
    exit();
}

try {
    if ($role === 'admin') {
        $query = "SELECT 
                    transaction_id AS transaction_id,
                    created_date,
                    order_number,
                    customer,
                    lottable3,
                    wh_name,
                    SUM(qty) AS total_items,
                    MIN(uom) AS uom,
                    MIN(locator) AS locator,
                    MIN(packing_list) AS packing_list
                  FROM outbound 
                  GROUP BY transaction_id, order_number, customer
                  ORDER BY transaction_id ASC";
        $stmt = $conn->prepare($query);
    } else {
        $query = "SELECT 
                    transaction_id AS transaction_id,
                    created_date,
                    order_number,
                    customer,
                    lottable3,
                    wh_name,
                    SUM(qty) AS total_items,
                    MIN(uom) AS uom,
                    MIN(locator) AS locator,
                    MIN(packing_list) AS packing_list
                  FROM outbound
                  WHERE wh_name = ?
                  GROUP BY transaction_id, order_number, customer
                  ORDER BY transaction_id ASC";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $wh_name);
    }

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];

    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    $stmt->close();
    $conn->close();

    echo json_encode(['status' => 'success', 'data' => $data], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    error_log("Error in data_table_outbound.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil data: ' . $e->getMessage()]);
    $conn->close();
}
?>