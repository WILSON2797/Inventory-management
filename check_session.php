<?php
session_start();
$timeout_duration = 1200; // 20 menit
$response = ['status' => 'active'];

// Cek apakah user sudah login
if (!isset($_SESSION['username'])) {
    $response['status'] = 'expired';
    $response['reason'] = 'not_logged_in';
}
// Cek apakah session sudah expired
elseif (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    session_unset();
    session_destroy();
    $response['status'] = 'expired';
    $response['reason'] = 'timeout';
    $response['last_activity'] = $_SESSION['last_activity'] ?? 'not_set';
    $response['current_time'] = time();
}
else {
    // Session masih aktif, return info untuk debugging
    $response['last_activity'] = $_SESSION['last_activity'] ?? 'not_set';
    $response['time_remaining'] = isset($_SESSION['last_activity']) 
        ? ($timeout_duration - (time() - $_SESSION['last_activity'])) 
        : $timeout_duration;
}

header('Content-Type: application/json');
echo json_encode($response);
exit();
?>