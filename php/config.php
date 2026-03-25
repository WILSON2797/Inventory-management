<?php
// config.php
$host = "localhost"; // Host database
$username = ""; // Username database
$password = ""; // Password Database
$database = ""; // Nama database

// Buat koneksi mysqli (mempertahankan backward compatibility)
$conn = new mysqli($host, $username, $password, $database);

// Cek koneksi mysqli
if ($conn->connect_error) {
    die("Koneksi mysqli gagal: " . $conn->connect_error);
}

// Set charset ke utf8 untuk mysqli
$conn->set_charset("utf8");

// Set timezone ke WIB (UTC+7) untuk MySQLi
$conn->query("SET time_zone = '+07:00'");

// Tambahkan koneksi PDO
try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8", $username, $password, [
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '+07:00'"
    ]);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Alternatif jika opsi di atas tidak bekerja
    // $pdo->exec("SET time_zone = '+07:00'");
    
} catch (PDOException $e) {
    die("Koneksi PDO gagal: " . $e->getMessage());
}

// Set timezone default PHP ke Asia/Jakarta
date_default_timezone_set('Asia/Jakarta');
?>