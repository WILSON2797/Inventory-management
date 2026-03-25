<?php
session_start();
include '../php/config.php';

// Enable error logging
ini_set('display_errors', 0); // Don't display errors to user
ini_set('log_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

// ============================================================================
// AUTHENTICATION CHECK
// ============================================================================
if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Anda harus login terlebih dahulu!']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metode request tidak valid']);
    exit();
}

// ============================================================================
// INPUT VALIDATION
// ============================================================================
$order_number = trim($_POST['order_number'] ?? '');

if (empty($order_number)) {
    echo json_encode(['success' => false, 'message' => 'Nomor order tidak ditemukan']);
    exit();
}

$wh_name = $_SESSION['wh_name'] ?? '';
$project_name = $_SESSION['project_name'] ?? '';
$username = $_SESSION['username'] ?? 'unknown';

if (empty($wh_name) || empty($project_name)) {
    echo json_encode(['success' => false, 'message' => 'WH atau Project tidak ditemukan di session.']);
    exit();
}

// ============================================================================
// MAIN PROCESS
// ============================================================================
try {
    // Set transaction isolation level
    $conn->query("SET TRANSACTION ISOLATION LEVEL SERIALIZABLE");
    $conn->query("SET innodb_lock_wait_timeout = 10");
    
    // Begin transaction
    $conn->begin_transaction();
    
    error_log("=== CANCEL ALLOCATION START === Order: $order_number, User: $username, WH: $wh_name, Project: $project_name");

    // ========================================================================
    // STEP 1: Fetch and Lock All Allocated Records
    // ========================================================================
    $sql_alloc = "SELECT id, item_code, item_description, qty_picking, locator_picking, packing_list
                  FROM allocated
                  WHERE order_number = ?
                    AND wh_name = ?
                    AND project_name = ?
                    AND status = 'allocated'
                  FOR UPDATE";
    
    $stmt_alloc = $conn->prepare($sql_alloc);
    if (!$stmt_alloc) {
        throw new Exception("Prepare failed (allocated): " . $conn->error);
    }
    
    $stmt_alloc->bind_param("sss", $order_number, $wh_name, $project_name);
    $stmt_alloc->execute();
    $allocRes = $stmt_alloc->get_result();

    if ($allocRes->num_rows === 0) {
        $conn->rollback();
        echo json_encode([
            'success' => false, 
            'message' => "Tidak ada allocated aktif untuk order {$order_number}"
        ]);
        exit();
    }

    $allocated_records = [];
    while ($row = $allocRes->fetch_assoc()) {
        $allocated_records[] = $row;
    }
    $stmt_alloc->close();
    
    error_log("Found " . count($allocated_records) . " allocated records to cancel");

    // ========================================================================
    // STEP 2: Process Each Allocated Record
    // ========================================================================
    $cancelled_items = [];
    $total_cancelled_qty = 0;

    foreach ($allocated_records as $record) {
        $allocated_id = (int)$record['id'];
        $item_code = $record['item_code'];
        $item_description = $record['item_description'];
        $qty_picking = (int)$record['qty_picking'];
        $locator = $record['locator_picking'];
        $packing_list = $record['packing_list'] ?? '';

        error_log("Processing cancel for Allocated ID: $allocated_id, Item: $item_code, Qty: $qty_picking, Locator: $locator, Packing: $packing_list");

        // --------------------------------------------------------------------
        // Find and Lock Corresponding Stock Record
        // --------------------------------------------------------------------
        $sql_stock = "SELECT id, qty_allocated, stock_on_hand, stock_balance
                     FROM stock
                     WHERE item_code = ?
                       AND locator = ?
                       AND wh_name = ?
                       AND project_name = ?
                       AND (
                           (packing_list = ? AND ? != '') OR
                           (packing_list = '' AND ? = '')
                       )
                     LIMIT 1
                     FOR UPDATE";
        
        $stmt_stock = $conn->prepare($sql_stock);
        if (!$stmt_stock) {
            throw new Exception("Prepare failed (stock lookup): " . $conn->error);
        }
        
        $stmt_stock->bind_param(
            "sssssss", 
            $item_code, 
            $locator, 
            $wh_name, 
            $project_name, 
            $packing_list,
            $packing_list,
            $packing_list
        );
        $stmt_stock->execute();
        $stockRes = $stmt_stock->get_result();

        if ($stockRes->num_rows === 0) {
            $stmt_stock->close();
            throw new Exception(
                "CRITICAL: Stock record tidak ditemukan untuk pembatalan! " .
                "Item: {$item_code}, Locator: {$locator}, Packing List: '{$packing_list}'. " .
                "Data mungkin sudah berubah atau tidak sinkron."
            );
        }

        $stockRow = $stockRes->fetch_assoc();
        $stock_id = (int)$stockRow['id'];
        $current_qty_allocated = (int)$stockRow['qty_allocated'];
        $stock_on_hand = (int)$stockRow['stock_on_hand'];
        $current_stock_balance = (int)$stockRow['stock_balance'];
        $stmt_stock->close();

        // --------------------------------------------------------------------
        // Validation: Check if qty_allocated is sufficient
        // --------------------------------------------------------------------
        if ($current_qty_allocated < $qty_picking) {
            throw new Exception(
                "CRITICAL: qty_allocated tidak cukup untuk dibatalkan! " .
                "Item: {$item_code}, Stock ID: {$stock_id}, " .
                "Current Allocated: {$current_qty_allocated}, Cancel Request: {$qty_picking}"
            );
        }

        // --------------------------------------------------------------------
        // Update Stock: Reduce qty_allocated
        // --------------------------------------------------------------------
        $sql_update_allocated = "UPDATE stock 
                                SET qty_allocated = qty_allocated - ?,
                                    last_updated = NOW() 
                                WHERE id = ?";
        
        $stmt_update_alloc = $conn->prepare($sql_update_allocated);
        if (!$stmt_update_alloc) {
            throw new Exception("Prepare failed (update qty_allocated): " . $conn->error);
        }
        
        $stmt_update_alloc->bind_param("ii", $qty_picking, $stock_id);
        $stmt_update_alloc->execute();
        
        if ($stmt_update_alloc->affected_rows === 0 && $stmt_update_alloc->errno) {
            throw new Exception("Gagal update qty_allocated untuk stock ID {$stock_id}: " . $stmt_update_alloc->error);
        }
        $stmt_update_alloc->close();

        // --------------------------------------------------------------------
        // Update Stock: Recalculate stock_balance (CONSISTENT with proses_allocated)
        // --------------------------------------------------------------------
        $sql_update_balance = "UPDATE stock 
                              SET stock_balance = stock_on_hand - qty_allocated,
                                  last_updated = NOW() 
                              WHERE id = ?";
        
        $stmt_update_balance = $conn->prepare($sql_update_balance);
        if (!$stmt_update_balance) {
            throw new Exception("Prepare failed (update stock_balance): " . $conn->error);
        }
        
        $stmt_update_balance->bind_param("i", $stock_id);
        $stmt_update_balance->execute();
        
        if ($stmt_update_balance->affected_rows === 0 && $stmt_update_balance->errno) {
            throw new Exception("Gagal update stock_balance untuk stock ID {$stock_id}: " . $stmt_update_balance->error);
        }
        $stmt_update_balance->close();

        // --------------------------------------------------------------------
        // Verify Stock Update
        // --------------------------------------------------------------------
        $sql_verify = "SELECT qty_allocated, stock_balance, stock_on_hand FROM stock WHERE id = ?";
        $stmt_verify = $conn->prepare($sql_verify);
        $stmt_verify->bind_param("i", $stock_id);
        $stmt_verify->execute();
        $verifyRes = $stmt_verify->get_result();
        $verifyRow = $verifyRes->fetch_assoc();
        $stmt_verify->close();

        $new_qty_allocated = (int)$verifyRow['qty_allocated'];
        $new_stock_balance = (int)$verifyRow['stock_balance'];
        $verified_on_hand = (int)$verifyRow['stock_on_hand'];

        // Sanity check
        if ($new_stock_balance !== ($verified_on_hand - $new_qty_allocated)) {
            throw new Exception(
                "CRITICAL: Stock balance calculation mismatch! " .
                "Stock ID: {$stock_id}, Balance: {$new_stock_balance}, " .
                "Expected: " . ($verified_on_hand - $new_qty_allocated)
            );
        }

        error_log(
            "Stock updated successfully - ID: {$stock_id}, " .
            "Old Allocated: {$current_qty_allocated}, New Allocated: {$new_qty_allocated}, " .
            "Old Balance: {$current_stock_balance}, New Balance: {$new_stock_balance}"
        );

        // --------------------------------------------------------------------
        // Update Allocated Record Status to 'canceled'
        // --------------------------------------------------------------------
        $sql_cancel_alloc = "UPDATE allocated
                            SET status = 'canceled', 
                                canceled_by = ?, 
                                canceled_at = NOW(), 
                                updated_at = NOW()
                            WHERE id = ? AND status = 'allocated'";
        
        $stmt_cancel = $conn->prepare($sql_cancel_alloc);
        if (!$stmt_cancel) {
            throw new Exception("Prepare failed (cancel allocated): " . $conn->error);
        }
        
        $stmt_cancel->bind_param("si", $username, $allocated_id);
        $stmt_cancel->execute();
        
        if ($stmt_cancel->affected_rows === 0 && $stmt_cancel->errno) {
            throw new Exception("Gagal update status allocated ID {$allocated_id}: " . $stmt_cancel->error);
        }
        $stmt_cancel->close();

        // Track cancelled items
        $cancelled_items[] = [
            'item_code' => $item_code,
            'item_description' => $item_description,
            'qty' => $qty_picking,
            'locator' => $locator
        ];
        $total_cancelled_qty += $qty_picking;

        error_log("Allocated ID {$allocated_id} cancelled successfully");
    }

    // ========================================================================
    // STEP 3: Final Consistency Check (CRITICAL)
    // ========================================================================
    $sql_consistency = "SELECT 
                            COUNT(*) as negative_count,
                            GROUP_CONCAT(CONCAT('ID:', id, ' Item:', item_code) SEPARATOR ', ') as negative_items
                        FROM stock 
                        WHERE (stock_balance < 0 OR qty_allocated < 0)
                          AND wh_name = ? 
                          AND project_name = ?";
    
    $stmt_consistency = $conn->prepare($sql_consistency);
    $stmt_consistency->bind_param("ss", $wh_name, $project_name);
    $stmt_consistency->execute();
    $consistencyRes = $stmt_consistency->get_result();
    $consistencyRow = $consistencyRes->fetch_assoc();
    $stmt_consistency->close();

    if ($consistencyRow['negative_count'] > 0) {
        throw new Exception(
            "CRITICAL: Data consistency check FAILED! " .
            "Found {$consistencyRow['negative_count']} records with negative values: " .
            $consistencyRow['negative_items'] . ". Transaction rolled back."
        );
    }

    // ========================================================================
    // STEP 4: Commit Transaction
    // ========================================================================
    $conn->commit();
    
    $cancel_time = date('Y-m-d H:i:s');
    error_log("=== CANCEL ALLOCATION SUCCESS === Order: $order_number, Items: " . count($cancelled_items) . ", Total Qty: $total_cancelled_qty");

    echo json_encode([
        'success' => true,
        'message' => "Order {$order_number} berhasil dibatalkan dan stok dikembalikan.",
        'order_number' => $order_number,
        'cancelled_by' => $username,
        'cancelled_at' => $cancel_time,
        'items_cancelled' => count($cancelled_items),
        'total_qty_returned' => $total_cancelled_qty,
        'items' => $cancelled_items
    ]);
    exit();

} catch (Exception $e) {
    // Rollback on any error
    if (isset($conn) && $conn->connect_error === null) {
        $conn->rollback();
    }
    
    $error_detail = sprintf(
        "Cancel allocation failed - Order: %s, Error: %s, File: %s, Line: %d",
        $order_number ?? 'unknown',
        $e->getMessage(),
        $e->getFile(),
        $e->getLine()
    );
    
    error_log($error_detail);
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'order_number' => $order_number ?? 'unknown',
        'cancelled_by' => $username,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit();
}

// Close connection
if (isset($conn)) {
    $conn->close();
}
?>