<?php
session_start();
require_once '../php/config.php';

// Cek apakah request dari AJAX atau form biasa
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Function untuk mengirim response
function sendResponse($success, $message, $data = [], $isAjax = true) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(array_merge([
            'success' => $success,
            'message' => $message
        ], $data));
    } else {
        if ($success) {
            header("Location: ../index.php?page=profile&upload=success");
        } else {
            die($message);
        }
    }
    exit;
}

// Cek apakah user sudah login
if (!isset($_SESSION['username'])) {
    sendResponse(false, 'Access denied!', [], $isAjax);
}

$user = $_SESSION['username'];
$uploadDir = '../assets/Uploads/profile/';

// Buat folder jika belum ada
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Validasi file
if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] !== 0) {
    sendResponse(false, 'Tidak ada file yang diupload!', [], $isAjax);
}

$fileTmp = $_FILES['profile_image']['tmp_name'];
$fileName = time() . "_" . basename($_FILES['profile_image']['name']);
$filePath = $uploadDir . $fileName;

$allowedExt = ['jpg', 'jpeg', 'png'];
$fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

if (!in_array($fileExt, $allowedExt)) {
    sendResponse(false, 'Format file tidak didukung!', [], $isAjax);
}

if ($_FILES['profile_image']['size'] > 5 * 1024 * 1024) {
    sendResponse(false, 'Ukuran file terlalu besar!', [], $isAjax);
}

// Hapus foto lama jika ada
if (!empty($_SESSION['profile_picture'])) {
    $oldFile = $uploadDir . $_SESSION['profile_picture'];
    if (file_exists($oldFile)) {
        @unlink($oldFile);
    }
}

// Pindahkan file
if (move_uploaded_file($fileTmp, $filePath)) {
    
    // Update DB
    $stmt = $conn->prepare("UPDATE data_username SET profile_picture = ? WHERE username = ?");
    $stmt->execute([$fileName, $user]);
    
    // Update session
    $_SESSION['profile_picture'] = $fileName;
    
    // Response berdasarkan jenis request
    sendResponse(true, 'Upload foto berhasil!', [
        'imagePath' => 'assets/Uploads/profile/' . $fileName,
        'fileName' => $fileName
    ], $isAjax);
    
} else {
    sendResponse(false, 'Gagal upload file!', [], $isAjax);
}