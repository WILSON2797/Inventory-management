<?php
session_start();
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Akses ditolak']);
    exit();
}

include '../php/config.php';

$user_id = $_GET['id'] ?? 0;
if (!$user_id) {
    echo json_encode(['error' => 'ID tidak valid']);
    exit();
}

$user_id = intval($user_id);

// Ambil user utama
$stmt = $conn->prepare("SELECT id, nama, email, username, role FROM data_username WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['error' => 'User tidak ditemukan']);
    exit();
}

$user = $result->fetch_assoc();

// Ambil WH
$wh_q = $conn->query("SELECT wh_name FROM user_warehouses WHERE user_id = $user_id");
$user['wh_list'] = [];
while ($row = $wh_q->fetch_assoc()) {
    $user['wh_list'][] = $row['wh_name'];
}

// Ambil Project
$proj_q = $conn->query("SELECT project_name FROM user_projects WHERE user_id = $user_id");
$user['proj_list'] = [];
while ($row = $proj_q->fetch_assoc()) {
    $user['proj_list'][] = $row['project_name'];
}

echo json_encode($user);
$conn->close();
?>