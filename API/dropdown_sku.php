<?php
session_start();
include '../php/config.php';

// cek user apakah sudah login
if (!isset($_SESSION['username'])) {
    echo json_encode(['status' => 'error', 'message' => 'User tidak terautentikasi']);
    exit();
}

// ambil data dari session
$nama = $_SESSION['nama'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];
$wh_id = $_SESSION['wh_name'] ?? null;
$project = $_SESSION['project_name'] ?? null;

// ambil parameter pencarian dari AJAX
$search = isset($_GET['search']) ? $_GET['search'] : '';

// buat query dengan filter pencarian
$sql = "SELECT item_code, item_description, uom FROM master_sku WHERE project = ?";

// tambahkan kondisi pencarian jika ada input dari user
if (!empty($search)) {
    $sql .= " AND (item_code LIKE ? OR item_description LIKE ?)";
}

$sql .= " ORDER BY item_code LIMIT 100"; // batasi hasil untuk performa

// gunakan prepared statement untuk keamanan
$stmt = $conn->prepare($sql);

if (!empty($search)) {
    $searchParam = "%{$search}%";
    $stmt->bind_param("sss", $project, $searchParam, $searchParam);
} else {
    $stmt->bind_param("s", $project);
}

$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);

$stmt->close();
$conn->close();
?>