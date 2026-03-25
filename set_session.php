<?php
session_start();
include 'php/config.php';

// === CEK APAKAH REQUEST DARI AJAX ===
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if (!isset($_SESSION['user_id'])) {
    if ($is_ajax) {
        echo json_encode(['status'=>'error','message'=>'Unauthorized']);
    } else {
        header('Location: login.php');
    }
    exit();
}

$user_id = $_SESSION['user_id'];
$wh_name = $_POST['wh_name'] ?? '';
$project_name = $_POST['project_name'] ?? '';

if (empty($wh_name) || empty($project_name)) {
    if ($is_ajax) {
        echo json_encode(['status'=>'error','message'=>'Pilih WH dan Project']);
    } else {
        header('Location: select_wh_project.php?error=required');
    }
    exit();
}

$wh_name = mysqli_real_escape_string($conn, $wh_name);
$project_name = mysqli_real_escape_string($conn, $project_name);

$check_wh = $conn->query("SELECT 1 FROM user_warehouses WHERE user_id = $user_id AND wh_name = '$wh_name'");
$check_proj = $conn->query("SELECT 1 FROM user_projects WHERE user_id = $user_id AND project_name = '$project_name'");

if ($check_wh->num_rows === 0 || $check_proj->num_rows === 0) {
    if ($is_ajax) {
        echo json_encode(['status'=>'error','message'=>'Akses ditolak!']);
    } else {
        header('Location: select_wh_project.php?error=invalid');
    }
    exit();
}

$wh_id_res = $conn->query("SELECT wh_id FROM warehouses WHERE wh_name = '$wh_name'");
$wh_id = $wh_id_res->num_rows > 0 ? $wh_id_res->fetch_assoc()['wh_id'] : 0;

$_SESSION['context_wh_name'] = $wh_name;
$_SESSION['context_wh_id'] = $wh_id;
$_SESSION['context_project_name'] = $project_name;
$_SESSION['wh_name'] = $wh_name;
$_SESSION['wh_id'] = $wh_id;
$_SESSION['project_name'] = $project_name;

// === JIKA BUKAN AJAX → REDIRECT KE DASHBOARD ===
if (!$is_ajax) {
    header('Location: index.php?page=dashboard');
    exit();
}

// === JIKA AJAX → RETURN JSON ===
echo json_encode(['status'=>'success','message'=>'Konteks berhasil diubah']);
$conn->close();
?>