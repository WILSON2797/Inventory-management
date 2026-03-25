<?php
session_start();

// Cek apakah user sudah login
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['status' => 'unauthorized']);
    exit();
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Return success response
header('Content-Type: application/json');
echo json_encode([
    'status' => 'updated',
    'timestamp' => time()
]);
exit();
?>