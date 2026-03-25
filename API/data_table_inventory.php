<?php
header('Content-Type: application/json');

include '../php/config.php';
session_start();

try {
    // Query dasar
    $query = "SELECT * FROM inbound";
    $params = [];
    $types = "";

    // Tambahkan kondisi WHERE berdasarkan role dan wh_name
    if (isset($_SESSION['role']) && $_SESSION['role'] !== 'admin') {
        if (isset($_SESSION['wh_name']) && !empty($_SESSION['wh_name'])) {
            $query .= " WHERE wh_name = ?";
            $params[] = $_SESSION['wh_name'];
            $types .= "s";
            error_log("Filtering data_materials by wh_name: " . $_SESSION['wh_name']);
        } else {
            error_log("No wh_name in session for role: " . $_SESSION['role']);
            echo json_encode(['error' => 'No warehouse name defined for this user.']);
            exit;
        }
    }

    // Tambahkan pengurutan
    $query .= " ORDER BY created_date ASC";

    // Siapkan dan jalankan query
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    // Log jumlah data yang diambil
    error_log("data_materials: Number of rows returned: " . $result->num_rows);

    echo json_encode($data);

    $stmt->close();
} catch (Exception $e) {
    error_log("Error in fetch_data_materials: " . $e->getMessage() . " | Query: " . $query);
    echo json_encode(['error' => 'Gagal mengambil data: ' . $e->getMessage()]);
}

$conn->close();
?>