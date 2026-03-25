<?php
include '../php/config.php';

try {
    // Query pakai prepared statement (meski tanpa input, tetap best practice)
    $stmt = $conn->prepare("SELECT stock_type FROM stock_type ORDER BY stock_type ASC");
    $stmt->execute();
    $result = $stmt->get_result();

    // Loop hasil query
    while ($row = $result->fetch_assoc()) {
        $type = htmlspecialchars($row['stock_type'], ENT_QUOTES, 'UTF-8');
        echo "<option value='{$type}'>{$type}</option>";
    }

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    http_response_code(500);
    echo "<option disabled>Error load data</option>";
    error_log("Error get_stock_type.php: " . $e->getMessage());
}
