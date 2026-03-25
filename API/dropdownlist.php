<?php
header('Content-Type: application/json');
session_start();
include '../php/config.php'; // Sesuaikan path

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$table = $_GET['table'] ?? '';
$column = $_GET['column'] ?? '';
$display = $_GET['display'] ?? '';

error_log("Received parameters in dropdownlist.php: table=$table, column=$column, display=$display");

if (empty($table) || empty($column) || empty($display)) {
    echo json_encode(['status' => 'error', 'message' => 'Parameter table, column, dan display wajib diisi']);
    exit;
}

$allowed_tables = ['warehouses', 'project_name', 'master_locator'];
if (!in_array($table, $allowed_tables)) {
    echo json_encode(['status' => 'error', 'message' => 'Tabel tidak diizinkan']);
    exit;
}

try {
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception("Koneksi database tidak tersedia: " . ($conn->connect_error ?? 'Unknown error'));
    }

    // Query dasar
    $query = "SELECT `$column` AS id, `$display` AS text FROM `$table`";

    // Tambahkan filter wh_name untuk tabel master_locator berdasarkan sesi
    if ($table === 'master_locator' && isset($_SESSION['wh_name']) && !empty($_SESSION['wh_name'])) {
        $wh_name = $conn->real_escape_string($_SESSION['wh_name']);
        $query .= " WHERE wh_name = '$wh_name'";
    }

    error_log("Executing query: $query");

    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        throw new Exception("Gagal menyiapkan query: " . $conn->error);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);

    error_log("Query result: " . json_encode($data));

    echo json_encode(['status' => 'success', 'data' => $data]);
} catch (Exception $e) {
    error_log("Error in dropdownlist.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil data: ' . $e->getMessage()]);
}

if (isset($stmt)) {
    $stmt->close();
}
if (isset($conn)) {
    $conn->close();
}
?>