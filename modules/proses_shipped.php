<?php
session_start();
include '../php/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Anda harus login terlebih dahulu!']);
    exit();
}

if (!isset($_POST['order_number'])) {
    echo json_encode(['success' => false, 'message' => 'Order Number tidak ditemukan!']);
    exit();
}

// Fungsi untuk menghasilkan transaction_id dari transaction_sequence
function generateTransactionId($conn, $prefix) {
    try {
        $date = date('Ymd');
        error_log("Generating transaction ID with prefix: $prefix");
        $conn->query("LOCK TABLES transaction_sequence WRITE");
        
        $checkSql = "SELECT COUNT(*) as count FROM transaction_sequence WHERE prefix = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("s", $prefix);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        $checkRow = $checkResult->fetch_assoc();
        
        if ($checkRow['count'] == 0) {
            $insertSql = "INSERT INTO transaction_sequence (prefix, sequence, last_updated) VALUES (?, 1, NOW())";
            $insertStmt = $conn->prepare($insertSql);
            $insertStmt->bind_param("s", $prefix);
            $insertStmt->execute();
            $sequence = "0001";
        } else {
            $sql = "UPDATE transaction_sequence SET sequence = sequence + 1, last_updated = NOW() WHERE prefix = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $prefix);
            $stmt->execute();
            
            $sql = "SELECT sequence FROM transaction_sequence WHERE prefix = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $prefix);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $sequence = str_pad($row['sequence'], 4, '0', STR_PAD_LEFT);
        }
        
        $conn->query("UNLOCK TABLES");
        $transaction_id = "$prefix-$date-$sequence";
        error_log("Generated Transaction ID: $transaction_id");
        return $transaction_id;
    } catch (Exception $e) {
        $conn->query("UNLOCK TABLES");
        throw new Exception("Failed to generate transaction ID: " . $e->getMessage());
    }
}

$order_number = $_POST['order_number'];
$created_by = $_SESSION['username'];
$wh_name = isset($_SESSION['wh_name']) ? $_SESSION['wh_name'] : '';
$project_name = isset($_SESSION['project_name']) ? $_SESSION['project_name'] : '';

try {
    // Set isolation level untuk menghindari inkonsistensi di multi-user
    $conn->query("SET TRANSACTION ISOLATION LEVEL REPEATABLE READ");
    
    // Mulai transaksi
    $conn->begin_transaction();

    // Ambil data dari tabel allocated berdasarkan order_number
    $sql_select = "SELECT 
        order_number, 
        customer, 
        lottable1, 
        lottable2, 
        lottable3, 
        item_code, 
        item_description, 
        qty_picking AS qty, 
        locator_picking AS locator, 
        uom,
        volume,
        packing_list
    FROM allocated 
    WHERE order_number = ? AND status = 'allocated'";

    $stmt_select = $conn->prepare($sql_select);
    $stmt_select->bind_param("s", $order_number);
    $stmt_select->execute();
    $result = $stmt_select->get_result();

    if ($result->num_rows === 0) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Tidak ada data dengan status allocated untuk Order Number ini!']);
        exit();
    }

    $items = [];
    while ($row = $result->fetch_assoc()) {
        // Jika uom kosong, ambil dari master_sku atau gunakan default 'PCS'
        if (empty($row['uom'])) {
            $sql_uom = "SELECT uom FROM master_sku WHERE item_code = ? AND project = ?";
            $stmt_uom = $conn->prepare($sql_uom);
            $stmt_uom->bind_param("ss", $row['item_code'], $project_name);
            $stmt_uom->execute();
            $uom_result = $stmt_uom->get_result();
            $uom_row = $uom_result->fetch_assoc();
            $row['uom'] = $uom_row['uom'] ?? 'PCS';
            $stmt_uom->close();
        }
        $items[] = $row;
    }
    $stmt_select->close();

    // ========== VALIDASI SEMUA STOCK TERLEBIH DAHULU (SEBELUM INSERT/UPDATE) ==========
    error_log("=== VALIDATING ALL STOCK BEFORE PROCESSING ===");
    foreach ($items as $item) {
        $sql_check_stock = "SELECT stock_on_hand, qty_allocated, volume 
                           FROM stock 
                           WHERE item_code = ? AND wh_name = ? AND project_name = ? AND locator = ? AND packing_list = ?
                           FOR UPDATE"; // FOR UPDATE untuk lock row ini
        
        $stmt_check = $conn->prepare($sql_check_stock);
        $stmt_check->bind_param("sssss", $item['item_code'], $wh_name, $project_name, $item['locator'], $item['packing_list']);
        $stmt_check->execute();
        $stock_result = $stmt_check->get_result();
        $stock_row = $stock_result->fetch_assoc();

        if (!$stock_row) {
            $conn->rollback();
            echo json_encode([
                'success' => false, 
                'message' => "Stock tidak ditemukan untuk item {$item['item_code']} di locator {$item['locator']} dengan packing list {$item['packing_list']}"
            ]);
            exit();
        }

        $volume_to_reduce = (float)($item['volume'] ?? 0);
        
        // Hitung stock setelah dikurangi
        $new_stock_on_hand = $stock_row['stock_on_hand'] - $item['qty'];
        $new_qty_allocated = $stock_row['qty_allocated'] - $item['qty'];
        $new_volume = $stock_row['volume'] - $volume_to_reduce;
        $new_stock_balance = $new_stock_on_hand - $new_qty_allocated;

        // Log untuk debugging
        error_log("Validating item: {$item['item_code']}, Locator: {$item['locator']}, Packing List: {$item['packing_list']}");
        error_log("Current Stock: stock_on_hand={$stock_row['stock_on_hand']}, qty_allocated={$stock_row['qty_allocated']}, volume={$stock_row['volume']}");
        error_log("Requested Qty: {$item['qty']}, Volume: {$volume_to_reduce}");
        error_log("Calculated New Values: stock_on_hand={$new_stock_on_hand}, qty_allocated={$new_qty_allocated}, volume={$new_volume}, stock_balance={$new_stock_balance}");

        // Validasi qty_allocated cukup
        if ($stock_row['qty_allocated'] < $item['qty']) {
            $conn->rollback();
            echo json_encode([
                'success' => false, 
                'message' => "Qty allocated tidak cukup untuk item {$item['item_code']} di locator {$item['locator']} dengan packing list {$item['packing_list']}. Tersedia: {$stock_row['qty_allocated']}, Diminta: {$item['qty']}"
            ]);
            exit();
        }

        // Validasi stock_on_hand cukup
        if ($stock_row['stock_on_hand'] < $item['qty']) {
            $conn->rollback();
            echo json_encode([
                'success' => false, 
                'message' => "Stock on hand tidak cukup untuk item {$item['item_code']} di locator {$item['locator']} dengan packing list {$item['packing_list']}. Tersedia: {$stock_row['stock_on_hand']}, Diminta: {$item['qty']}"
            ]);
            exit();
        }

        // VALIDASI KRITIS: Pastikan hasil perhitungan tidak akan negatif
        if ($new_stock_on_hand < 0 || $new_qty_allocated < 0 || $new_volume < 0 || $new_stock_balance < 0) {
            $conn->rollback();
            error_log("VALIDATION FAILED: Stock akan menjadi negatif untuk item {$item['item_code']}");
            echo json_encode([
                'success' => false, 
                'message' => "Stock akan menjadi negatif untuk item {$item['item_code']} di locator {$item['locator']} dengan packing list {$item['packing_list']}. Transaksi dibatalkan. (New: stock_on_hand={$new_stock_on_hand}, qty_allocated={$new_qty_allocated}, volume={$new_volume}, stock_balance={$new_stock_balance})"
            ]);
            exit();
        }

        $stmt_check->close();
    }
    error_log("=== ALL STOCK VALIDATIONS PASSED ===");
    // ========== AKHIR VALIDASI ==========

    // Generate transaction_id setelah validasi berhasil
    $transaction_id = generateTransactionId($conn, 'FIS-OUT');

    // Prepare statement untuk insert ke outbound
    $sql_insert = "INSERT INTO outbound (
        transaction_id, order_number, customer, lottable1, lottable2, lottable3, 
        item_code, item_description, qty, uom, volume, locator, created_by, wh_name, project_name, packing_list
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt_insert = $conn->prepare($sql_insert);

    // Query untuk update stock
    $sql_update_stock = "UPDATE stock 
                         SET qty_allocated = qty_allocated - ?, 
                             stock_on_hand = stock_on_hand - ?,
                             qty_out = qty_out + ?,
                             volume = volume - ?,
                             stock_balance = stock_on_hand - qty_allocated 
                         WHERE item_code = ? AND wh_name = ? AND project_name = ? AND locator = ? AND packing_list = ?";

    $stmt_update_stock = $conn->prepare($sql_update_stock);

    // Proses insert dan update (sudah pasti aman karena sudah divalidasi)
    foreach ($items as $item) {
        $volume_to_reduce = (float)($item['volume'] ?? 0);

        // Insert data ke outbound
        $stmt_insert->bind_param(
            "ssssssssisdsssss",
            $transaction_id,
            $item['order_number'],
            $item['customer'],
            $item['lottable1'],
            $item['lottable2'],
            $item['lottable3'],
            $item['item_code'],
            $item['item_description'],
            $item['qty'],
            $item['uom'],
            $volume_to_reduce, 
            $item['locator'],
            $created_by,
            $wh_name,
            $project_name,
            $item['packing_list']
        );
        
        if (!$stmt_insert->execute()) {
            $conn->rollback();
            error_log("Failed to insert into outbound: " . $stmt_insert->error);
            echo json_encode(['success' => false, 'message' => 'Gagal insert ke outbound: ' . $stmt_insert->error]);
            exit();
        }

        // Update stock
        $stmt_update_stock->bind_param(
            "iiidsssss",
            $item['qty'],
            $item['qty'],
            $item['qty'],
            $volume_to_reduce,
            $item['item_code'],
            $wh_name,
            $project_name,
            $item['locator'],
            $item['packing_list']
        );
        
        if (!$stmt_update_stock->execute()) {
            $conn->rollback();
            error_log("Failed to update stock: " . $stmt_update_stock->error);
            echo json_encode(['success' => false, 'message' => 'Gagal update stock: ' . $stmt_update_stock->error]);
            exit();
        }

        error_log("Successfully processed item: {$item['item_code']}, qty: {$item['qty']}, volume: {$volume_to_reduce}");
    }

    $stmt_insert->close();
    $stmt_update_stock->close();

    // Update status di tabel allocated
    $sql_update = "UPDATE allocated SET status = 'shipped' WHERE order_number = ? AND status = 'allocated'";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("s", $order_number);
    
    if (!$stmt_update->execute()) {
        $conn->rollback();
        error_log("Failed to update allocated status: " . $stmt_update->error);
        echo json_encode(['success' => false, 'message' => 'Gagal update status allocated: ' . $stmt_update->error]);
        exit();
    }
    
    $stmt_update->close();

    // Commit transaksi jika semua berhasil
    $conn->commit();
    error_log("=== TRANSACTION COMMITTED SUCCESSFULLY ===");

    echo json_encode([
        'success' => true, 
        'message' => 'Data berhasil diproses sebagai Shipped!', 
        'transaction_id' => $transaction_id,
        'items_processed' => count($items)
    ]);

} catch (Exception $e) {
    $conn->rollback();
    error_log("ERROR in proses_shipped.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
}

$conn->close();
?>