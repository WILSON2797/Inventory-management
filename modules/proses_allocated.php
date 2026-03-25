<?php
session_start();
include '../php/config.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Anda harus login terlebih dahulu!']);
    exit();
}

function generateTransactionId($conn, $prefix) {
    try {
        $date = date('Ymd');
        error_log("Generating transaction ID with prefix: $prefix");
        
        $conn->query("SET innodb_lock_wait_timeout = 5");
        $conn->query("LOCK TABLES transaction_sequence WRITE");
        
        $checkSql = "SELECT COUNT(*) as count FROM transaction_sequence WHERE prefix = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("s", $prefix);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        $checkRow = $checkResult->fetch_assoc();
        $checkStmt->close();
        
        if ($checkRow['count'] == 0) {
            $insertSql = "INSERT INTO transaction_sequence (prefix, sequence, last_updated) VALUES (?, 1, NOW())";
            $insertStmt = $conn->prepare($insertSql);
            $insertStmt->bind_param("s", $prefix);
            $insertStmt->execute();
            $insertStmt->close();
            $sequence = "0001";
        } else {
            $sql = "UPDATE transaction_sequence SET sequence = sequence + 1, last_updated = NOW() WHERE prefix = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $prefix);
            $stmt->execute();
            $stmt->close();
            
            $sql = "SELECT sequence FROM transaction_sequence WHERE prefix = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $prefix);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $sequence = str_pad($row['sequence'], 4, '0', STR_PAD_LEFT);
            $stmt->close();
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

function validateInput($data) {
    $errors = [];
    
    if (!is_array($data)) {
        $errors[] = "Invalid input data: Expected an array";
    } else {
        if (empty($data['order_number'])) {
            $errors[] = "Order number is required";
        }
        
        if (empty($data['customer'])) {
            $errors[] = "Customer is required";
        }
        
        if (empty($data['lottable3'])) {
            $errors[] = "Destination is required";
        }
        
        if (!isset($data['items']) || !is_array($data['items']) || empty($data['items'])) {
            $errors[] = "Items data is required and must be a non-empty array";
        } else {
            foreach ($data['items'] as $index => $item) {
                if (!is_array($item)) {
                    $errors[] = "Item #" . ($index + 1) . " must be an array";
                    continue;
                }
                if (empty($item['item_code'])) {
                    $errors[] = "Item code is required for item #" . ($index + 1);
                }
                if (!isset($item['qty_picking']) || !is_numeric($item['qty_picking']) || $item['qty_picking'] <= 0) {
                    $errors[] = "Valid quantity picking is required for item #" . ($index + 1);
                }
            }
        }
    }
    
    return $errors;
}

function checkStockAvailability($conn, $item_code, $qty_needed, $wh_name, $project_name) {
    $sql = "SELECT SUM(stock_on_hand - qty_allocated) as total_available 
            FROM stock 
            WHERE item_code = ? AND wh_name = ? AND project_name = ? 
            AND (stock_on_hand - qty_allocated) > 0
            AND status_stock = 'Active'";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $item_code, $wh_name, $project_name);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    $available = $row['total_available'] ?? 0;
    error_log("Stock check for $item_code: Available=$available, Needed=$qty_needed");
    return $available >= $qty_needed;
}

function allocateStockWithLocking($conn, $item_code, $qty_picking, $wh_name, $project_name) {
    try {
        $lock_sql = "SELECT id, locator, stock_on_hand, qty_allocated, packing_list 
                     FROM stock 
                     WHERE item_code = ? AND wh_name = ? AND project_name = ? 
                     AND (stock_on_hand - qty_allocated) > 0 
                     AND status_stock = 'Active'
                     ORDER BY Inbound_date ASC 
                     FOR UPDATE";
        $lock_stmt = $conn->prepare($lock_sql);
        $lock_stmt->bind_param("sss", $item_code, $wh_name, $project_name);
        $lock_stmt->execute();
        $stock_result = $lock_stmt->get_result();
        
        $remaining_qty = $qty_picking;
        $allocated_stocks = [];
        
        while ($stock_row = $stock_result->fetch_assoc()) {
            if ($remaining_qty <= 0) break;
            
            $available_qty = (int)($stock_row['stock_on_hand'] - $stock_row['qty_allocated']);
            if ($available_qty <= 0) continue;
            
            $qty_to_allocate = min($remaining_qty, $available_qty);
            
            if ($qty_to_allocate > 0) {
                $allocated_stocks[] = [
                    'locator' => $stock_row['locator'],
                    'qty' => $qty_to_allocate,
                    'id' => $stock_row['id'],
                    'packing_list' => $stock_row['packing_list'] ?? ''
                ];
                $remaining_qty -= $qty_to_allocate;
            }
        }
        $lock_stmt->close();
        
        if ($remaining_qty > 0) {
            throw new Exception("Insufficient stock for Item Code $item_code. Required: $qty_picking, Available: " . ($qty_picking - $remaining_qty));
        }
        
        return $allocated_stocks;
        
    } catch (Exception $e) {
        throw new Exception("Stock allocation failed for $item_code: " . $e->getMessage());
    }
}

function updateStockBalance($conn, $stock_id, $allocated_qty) {
    try {
        // Update qty_allocated first
        $update_allocated = "UPDATE stock 
                             SET qty_allocated = qty_allocated + ?, 
                                 last_updated = NOW() 
                             WHERE id = ?";
        $stmt_allocated = $conn->prepare($update_allocated);
        $stmt_allocated->bind_param("ii", $allocated_qty, $stock_id);
        $stmt_allocated->execute();
        
        if ($stmt_allocated->affected_rows === 0) {
            throw new Exception("Failed to update allocated quantity for stock ID: $stock_id");
        }
        $stmt_allocated->close();
        
        // Update stock_balance
        $update_balance = "UPDATE stock 
                           SET stock_balance = stock_on_hand - qty_allocated,
                               last_updated = NOW() 
                           WHERE id = ?";
        $stmt_balance = $conn->prepare($update_balance);
        $stmt_balance->bind_param("i", $stock_id);
        $stmt_balance->execute();
        
        if ($stmt_balance->affected_rows === 0) {
            throw new Exception("Failed to update stock balance for stock ID: $stock_id");
        }
        $stmt_balance->close();
        
        // Verify
        $verify_sql = "SELECT stock_balance, qty_allocated, stock_on_hand FROM stock WHERE id = ?";
        $verify_stmt = $conn->prepare($verify_sql);
        $verify_stmt->bind_param("i", $stock_id);
        $verify_stmt->execute();
        $result = $verify_stmt->get_result();
        $row = $result->fetch_assoc();
        $verify_stmt->close();
        
        if ($row['stock_balance'] < 0) {
            throw new Exception("Stock balance became negative for stock ID: $stock_id after allocation. Transaction rolled back.");
        }
        
        error_log("Stock updated - ID: $stock_id, Allocated Qty: $allocated_qty, New Balance: {$row['stock_balance']}, On Hand: {$row['stock_on_hand']}, Total Allocated: {$row['qty_allocated']}");
        
        return true;
        
    } catch (Exception $e) {
        throw new Exception("Update stock balance failed: " . $e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Check Content-Type header
        $content_type = $_SERVER['CONTENT_TYPE'] ?? '';
        error_log("Content-Type: " . ($content_type ?: 'Not set'));
        
        // Handle JSON input
        if (stripos($content_type, 'application/json') !== false) {
            $json = file_get_contents('php://input');
            error_log("Raw JSON input: " . ($json ?: 'EMPTY'));
            if ($json === false || empty($json)) {
                throw new Exception("Failed to read request body or empty JSON input");
            }
            
            $data = json_decode($json, true);
            if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Invalid JSON format: " . json_last_error_msg());
            }
        }
        // Handle form-urlencoded input
        elseif (stripos($content_type, 'application/x-www-form-urlencoded') !== false || empty($content_type)) {
            $data = $_POST;
            error_log("Form-urlencoded input: " . print_r($data, true));
            
            // Convert items from form data if needed
            if (isset($data['items']) && is_string($data['items'])) {
                $data['items'] = json_decode($data['items'], true);
                if ($data['items'] === null && json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception("Invalid items format in form data: " . json_last_error_msg());
                }
            }
        } else {
            throw new Exception("Unsupported Content-Type: $content_type");
        }
        
        $errors = validateInput($data);
        if (!empty($errors)) {
            throw new Exception(implode(". ", $errors));
        }
        
        $order_number = trim($data['order_number']);
        $customer = trim($data['customer']);
        $lottable1 = trim($data['lottable1'] ?? '');
        $lottable2 = trim($data['lottable2'] ?? '');
        $lottable3 = trim($data['lottable3'] ?? '');
        $items = $data['items'];
        $created_by = $_SESSION['username'];
        $wh_name = $_SESSION['wh_name'] ?? '';
        $project_name = $_SESSION['project_name'] ?? '';
        
        error_log("Form Data - Order: '$order_number', Customer: '$customer', WH: '$wh_name', Project: '$project_name', User: '$created_by'");
        
        if (empty($wh_name) || empty($project_name)) {
            throw new Exception("WH_NAME atau Project tidak ditemukan di session. Silakan login ulang atau pilih gudang.");
        }
        
        $check_wh = $conn->prepare("SELECT wh_name FROM warehouses WHERE wh_name = ?");
        $check_wh->bind_param("s", $wh_name);
        $check_wh->execute();
        $wh_result = $check_wh->get_result();
        if ($wh_result->num_rows == 0) {
            throw new Exception("WH_Name $wh_name tidak valid.");
        }
        $check_wh->close();
        
        $check_existing = $conn->prepare("SELECT COUNT(*) as count FROM allocated WHERE order_number = ? AND wh_name = ? AND project_name = ?");
        $check_existing->bind_param("sss", $order_number, $wh_name, $project_name);
        $check_existing->execute();
        $existing_result = $check_existing->get_result();
        $existing_row = $existing_result->fetch_assoc();
        $check_existing->close();
        
        if ($existing_row['count'] > 0) {
            throw new Exception("Order $order_number sudah pernah diproses untuk warehouse $wh_name. Silakan gunakan order number yang berbeda.");
        }
        
        foreach ($items as $index => $item) {
            $item_code = trim($item['item_code']);
            $qty_picking = (int)$item['qty_picking'];
            
            if (!checkStockAvailability($conn, $item_code, $qty_picking, $wh_name, $project_name)) {
                throw new Exception("Insufficient stock for item $item_code (item #" . ($index + 1) . "). Required: $qty_picking. Please check available stock.");
            }
        }
        
        $conn->query("SET TRANSACTION ISOLATION LEVEL SERIALIZABLE");
        $conn->begin_transaction();
        
        $total_allocated_records = 0;
        
        try {
            $transaction_id = generateTransactionId($conn, 'FIS-ALLOC');
            
            $stmt_allocated = $conn->prepare(
                "INSERT INTO allocated (
                    order_number, customer, item_code, item_description, qty_picking, locator_picking, uom, volume, 
                    lottable1, lottable2, lottable3, packing_list, created_by, project_name, wh_name, transaction_sequence
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            if (!$stmt_allocated) {
                throw new Exception("Prepare allocated failed: " . $conn->error);
            }

            // Tambahkan grouping di sini
            $grouped_items = [];
            foreach ($items as $item) {
                $item_code = trim($item['item_code']);
                $locator = trim($item['locator'] ?? ''); // Sesuaikan jika locator ada di input
                $packing_list = trim($item['packing_list'] ?? '');
                $key = implode('|', [$item_code, $locator, $packing_list, $wh_name, $project_name]);
                
                if (!isset($grouped_items[$key])) {
                    $grouped_items[$key] = [
                        'item_code' => $item_code,
                        'locator' => $locator,
                        'packing_list' => $packing_list,
                        'qty_picking' => 0
                    ];
                }
                $grouped_items[$key]['qty_picking'] += (int)$item['qty_picking'];
            }
            
            // Gunakan grouped_items untuk loop
            foreach ($grouped_items as $grouped) {
                $item_code = $grouped['item_code'];
                $qty_picking = $grouped['qty_picking'];
                $input_packing_list = $grouped['packing_list'];
                
                $check_sku = $conn->prepare("SELECT item_description, uom, volume FROM master_sku WHERE item_code = ? AND project = ?");
                $check_sku->bind_param("ss", $item_code, $project_name);
                $check_sku->execute();
                $sku_result = $check_sku->get_result();
                if ($sku_result->num_rows == 0) {
                    throw new Exception("Item Code $item_code tidak ditemukan atau tidak sesuai project $project_name");
                }
                $sku = $sku_result->fetch_assoc();
                $item_description = $sku['item_description'];
                $uom = $sku['uom'] ?? 'PCS';
                $volume = (float)($sku['volume'] ?? 0);
                $check_sku->close();
                
                $allocated_stocks = allocateStockWithLocking($conn, $item_code, $qty_picking, $wh_name, $project_name);
                
                foreach ($allocated_stocks as $allocated) {
                    $locator_picking = $allocated['locator'];
                    $allocated_qty = $allocated['qty'];
                    $stock_id = $allocated['id'];
                    $packing_list = !empty($allocated['packing_list']) ? $allocated['packing_list'] : $input_packing_list;
                    
                    $check_locator = $conn->prepare("SELECT COUNT(*) as count FROM master_locator WHERE locator = ? AND wh_name = ?");
                    $check_locator->bind_param("ss", $locator_picking, $wh_name);
                    $check_locator->execute();
                    $locator_result = $check_locator->get_result();
                    $locator_row = $locator_result->fetch_assoc();
                    if ($locator_row['count'] == 0) {
                        throw new Exception("Locator $locator_picking tidak valid untuk WH_Name $wh_name");
                    }
                    $check_locator->close();
                    
                    // Hitung total volume
                    $total_volume = $volume * $allocated_qty; // Fitur untuk volume
                    
                    // Update stock FIRST
                    updateStockBalance($conn, $stock_id, $allocated_qty);
                    
                    // Then insert to allocated
                    $stmt_allocated->bind_param(
                        "ssssissdssssssss",
                        $order_number,
                        $customer,
                        $item_code,
                        $item_description,
                        $allocated_qty,
                        $locator_picking,
                        $uom,
                        $total_volume,
                        $lottable1,
                        $lottable2,
                        $lottable3,
                        $packing_list,
                        $created_by,
                        $project_name,
                        $wh_name,
                        $transaction_id
                    );
                    
                    if (!$stmt_allocated->execute()) {
                        throw new Exception("Failed to insert allocation record: " . $stmt_allocated->error);
                    }
                    
                    error_log("Allocated inserted for $item_code - Locator: $locator_picking, Qty: $allocated_qty, Packing List: $packing_list");
                    
                    $total_allocated_records++;
                }
            }
            
            $stmt_allocated->close();
            
            // Final consistency check before commit
            $consistency_sql = "SELECT COUNT(*) as negative_stocks 
                                FROM stock 
                                WHERE stock_balance < 0 
                                AND wh_name = ? AND project_name = ?";
            $consistency_stmt = $conn->prepare($consistency_sql);
            $consistency_stmt->bind_param("ss", $wh_name, $project_name);
            $consistency_stmt->execute();
            $consistency_result = $consistency_stmt->get_result();
            $consistency_row = $consistency_result->fetch_assoc();
            $consistency_stmt->close();
            
            if ($consistency_row['negative_stocks'] > 0) {
                throw new Exception("CRITICAL: Data consistency check failed! Found {$consistency_row['negative_stocks']} records with negative stock balance. Transaction rolled back.");
            }
            
            // Commit
            $conn->commit();
            error_log("Transaction committed successfully for Transaction ID: $transaction_id");
            
            echo json_encode([
                'success' => true, 
                'message' => 'Data berhasil disimpan', 
                'transaction_id' => $transaction_id,
                'items_processed' => $total_allocated_records,
                'processed_by' => $created_by,
                'processed_at' => date('Y-m-d H:i:s')
            ]);
            
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
        
    } catch (Exception $e) {
        if (isset($conn) && $conn->connect_error === null) {
            $conn->rollback();
        }
        $detailed_error = "Error: " . $e->getMessage() . " di baris " . $e->getLine() . " file " . $e->getFile();
        error_log($detailed_error);
        echo json_encode([
            'success' => false, 
            'message' => $e->getMessage(),
            'error_details' => $detailed_error,
            'user' => $_SESSION['username'] ?? 'unknown'
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Metode request tidak valid']);
}

if (isset($conn)) {
    $conn->close();
}
?>