<?php
session_start(); // Mulai session untuk mengakses data login
include '../php/config.php'; // Sesuaikan dengan koneksi DB Anda

// Periksa apakah user sudah login
if (!isset($_SESSION['username']) || !isset($_SESSION['role'])) {
    echo json_encode(['error' => 'User tidak terautentikasi']);
    exit();
}

// Ambil informasi user dari session
$user_role = $_SESSION['role'];
$user_wh_name = isset($_SESSION['wh_name']) ? $_SESSION['wh_name'] : '';

// Build query berdasarkan role user
$sql = "SELECT 
            MIN(id) AS id, 
            order_number, 
            customer, 
            lottable1, 
            lottable2, 
            lottable3, 
            status, 
            MAX(allocated_date) AS allocated_date,  
            created_by, 
            wh_name
        FROM allocated 
        WHERE status = 'allocated'";

// Jika bukan admin, tambahkan filter berdasarkan wh_name
if ($user_role !== 'admin' && !empty($user_wh_name)) {
    $sql .= " AND wh_name = '" . $conn->real_escape_string($user_wh_name) . "'";
}

$sql .= " GROUP BY order_number, customer, lottable1, lottable2, lottable3, status, created_by, wh_name
          ORDER BY allocated_date DESC";

$result = $conn->query($sql);

if (!$result) {
    echo json_encode(['error' => 'Query error: ' . $conn->error]);
    exit();
}

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
$conn->close();
?>