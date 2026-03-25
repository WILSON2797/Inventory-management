<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Akses ditolak']);
    exit();
}

include '../php/config.php';  // Ganti path

// Jika ada ?id= -> return single user
$id = $_GET['id'] ?? null;

$query = "SELECT id, nama, username, email, role 
          FROM data_username 
          WHERE is_active = 1";

if ($id) {
    $query .= " AND id = " . intval($id);
}

$query .= " ORDER BY nama ASC";

$result = $conn->query($query);
$users = [];

while ($u = $result->fetch_assoc()) {
    $uid = $u['id'];

    // Ambil WH
    $wh_q = $conn->query("SELECT wh_name FROM user_warehouses WHERE user_id = $uid");
    $u['wh_names'] = '';  // Untuk tampil di table (string dengan <br>)
    $u['wh_list'] = [];   // Array untuk edit
    while ($w = $wh_q->fetch_assoc()) {
        $u['wh_names'] .= htmlspecialchars($w['wh_name']) . '<br>';
        $u['wh_list'][] = htmlspecialchars($w['wh_name']);
    }

    // Ambil Project
    $proj_q = $conn->query("SELECT project_name FROM user_projects WHERE user_id = $uid");
    $u['project_names'] = '';
    $u['proj_list'] = [];
    while ($p = $proj_q->fetch_assoc()) {
        $u['project_names'] .= htmlspecialchars($p['project_name']) . '<br>';
        $u['proj_list'][] = htmlspecialchars($p['project_name']);
    }

    $users[] = $u;
}

header('Content-Type: application/json');
if ($id) {
    echo json_encode($users[0] ?? null);
} else {
    echo json_encode(['data' => $users]);
}

$conn->close();
?>