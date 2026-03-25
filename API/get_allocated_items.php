<?php
include '../php/config.php'; // Sesuaikan dengan koneksi DB Anda

if (isset($_POST['order_number'])) {
    $order_number = $_POST['order_number'];
    $sql = "SELECT 
                item_code, 
                item_description, 
                qty_picking, 
                locator_picking, 
                packing_list, 
                uom 
            FROM allocated 
            WHERE order_number = ?
            AND status = 'allocated'";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $order_number);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode($data);
    $stmt->close();
} else {
    echo json_encode([]);
}
$conn->close();
?>