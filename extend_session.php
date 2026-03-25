<?php
session_start();

// Cek apakah user sudah login
if (isset($_SESSION['username'])) {
    // Perpanjang session dengan update last_activity
    $_SESSION['last_activity'] = time();
    
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'extended',
        'last_activity' => $_SESSION['last_activity'],
        'message' => 'Session berhasil diperpanjang'
    ]);
} else {
    // User belum login atau session expired
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'User not logged in'
    ]);
}
exit();
?>