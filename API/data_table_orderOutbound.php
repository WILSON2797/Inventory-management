<?php
session_start();
include 'config.php'; // Sesuaikan path dengan struktur folder Anda

// Set header keamanan
header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Anda harus login terlebih dahulu!']);
    exit();
}

$wh_id = $_SESSION['wh_id'] ?? '';
if (empty($wh_id)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'WH_ID tidak ditemukan di session']);
    exit();
}

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Koneksi gagal: ' . $conn->connect_error]);
    exit;
}

// Endpoint untuk data utama DataTables
if ($_SERVER['REQUEST_METHOD'] == 'GET' && !isset($_GET['action'])) {
    $query = "
        SELECT 
            date_time,
            wh_id,
            delivery_order,
            reference,
            customer,
            recipient,
            address,
            total_qty
        FROM outbound_orders
        WHERE wh_id = ?
        ORDER BY date_time DESC
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $wh_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode($data);
    $stmt->close();
    $conn->close();
    exit();
}

// Endpoint untuk detail item berdasarkan delivery_order
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['action']) && $_GET['action'] == 'get_details') {
    $delivery_order = $_GET['delivery_order'] ?? '';
    if (empty($delivery_order)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Delivery Order tidak diberikan']);
        exit();
    }

    $query = "
        SELECT 
            item_code,
            item_description,
            qty,
            uom,
            location
        FROM outbound
        WHERE wh_id = ? AND delivery_order = ?
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $wh_id, $delivery_order);
    $stmt->execute();
    $result = $stmt->get_result();

    $details = [];
    while ($row = $result->fetch_assoc()) {
        $details[] = $row;
    }

    // Tambahkan delivery_order ke dalam respon
    $response = [
        'delivery_order' => $delivery_order,
        'details' => $details
    ];

    echo json_encode($response);
    $stmt->close();
    $conn->close();
    exit();
}

http_response_code(405);
echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
$conn->close();
?>