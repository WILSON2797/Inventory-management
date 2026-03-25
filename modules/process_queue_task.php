<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/home/u272899607/domains/fislogapps.com/public_html/inventory/error.log');

echo "PHP SAPI: " . php_sapi_name() . "\n";
echo "Script dimulai pada: " . date('Y-m-d H:i:s') . "\n";

$logFile = '/home/u272899607/domains/fislogapps.com/public_html/inventory/cron.log';
$debugLog = "=== DEBUG START ===\n";
$debugLog .= "PHP SAPI: " . php_sapi_name() . "\n";
$debugLog .= "Script dimulai pada: " . date('Y-m-d H:i:s') . "\n";
$debugLog .= "Current working directory: " . getcwd() . "\n";
file_put_contents($logFile, $debugLog, FILE_APPEND | LOCK_EX);

echo "Process Queue Tasks dimulai\n";
file_put_contents($logFile, "Script process_queue_task.php mulai berjalan pada " . date('Y-m-d H:i:s') . "\n", FILE_APPEND | LOCK_EX);

// Allow multiple SAPI types for shared hosting flexibility
$allowedSapis = ['cli', 'cgi-fcgi', 'fpm-fcgi'];
if (!in_array(php_sapi_name(), $allowedSapis)) {
    $error = "Access denied: This script can only be run from command line or CGI. Current SAPI: " . php_sapi_name() . "\n";
    echo $error;
    file_put_contents($logFile, $error, FILE_APPEND | LOCK_EX);
    exit(1);
}

echo "Pengecekan SAPI berhasil: " . php_sapi_name() . "\n";
file_put_contents($logFile, "Pengecekan SAPI berhasil: " . php_sapi_name() . "\n", FILE_APPEND | LOCK_EX);

// Set base paths
$baseDir = '/home/u272899607/domains/fislogapps.com/public_html/inventory';
$autoloadPath = $baseDir . '/vendor/autoload.php';
$configPath = $baseDir . '/php/config.php';

// Check if required files exist
if (!file_exists($autoloadPath)) {
    $error = "Autoload file not found: $autoloadPath\n";
    echo $error;
    file_put_contents($logFile, $error, FILE_APPEND | LOCK_EX);
    exit(1);
}

if (!file_exists($configPath)) {
    $error = "Config file not found: $configPath\n";
    echo $error;
    file_put_contents($logFile, $error, FILE_APPEND | LOCK_EX);
    exit(1);
}

// Load required files
require_once $autoloadPath;
require_once $configPath;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Set memory limit dan execution time untuk cronjob
ini_set('memory_limit', '512M');
set_time_limit(0); // Unlimited execution time untuk cronjob

// Create log directory if not exists
$logDir = $baseDir . '/logs';
if (!file_exists($logDir)) {
    mkdir($logDir, 0775, true);
    file_put_contents($logFile, "Created logs directory: $logDir\n", FILE_APPEND | LOCK_EX);
}

// Enhanced logging function
function writeLog($message, $level = 'INFO') {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$level] $message\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    echo $logMessage;
}

// Prevent multiple instances
$lockFile = $baseDir . '/tmp/process_queue.lock';
$tmpDir = dirname($lockFile);
if (!file_exists($tmpDir)) {
    mkdir($tmpDir, 0775, true);
    writeLog("Created tmp directory: $tmpDir");
}

$lockHandle = fopen($lockFile, 'w');
if (!flock($lockHandle, LOCK_EX | LOCK_NB)) {
    writeLog("Another instance is already running. Exiting.", 'WARNING');
    exit(0);
}

// Register shutdown function to remove lock
register_shutdown_function(function() use ($lockHandle, $lockFile) {
    if ($lockHandle) {
        flock($lockHandle, LOCK_UN);
        fclose($lockHandle);
    }
    if (file_exists($lockFile)) {
        unlink($lockFile);
    }
});

function generateTransactionId($conn, $prefix) {
    global $logFile;
    try {
        $date = date('Ymd');
        writeLog("Generating transaction ID with prefix: $prefix");
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
        writeLog("Generated Transaction ID: $transaction_id");
        return $transaction_id;
    } catch (Exception $e) {
        $conn->query("UNLOCK TABLES");
        throw new Exception("Failed to generate transaction ID: " . $e->getMessage());
    }
}

// Fungsi untuk bulk_allocated
function generateErrorReport($data, $errors, $reportDir) {
    global $baseDir;
    $reportDir = $baseDir . '/Uploads/reports';
    if (!file_exists($reportDir)) {
        mkdir($reportDir, 0775, true);
        writeLog("Created reports directory: $reportDir");
    }
    $reportFile = $reportDir . '/error_report_' . time() . '.xlsx';
    writeLog("Generating error report at: $reportFile");

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    $header = ['Order Number', 'Customer', 'Lottable1', 'Lottable2', 'Destination', 'Item Code', 'Qty', 'error_log'];
    $sheet->fromArray($header, NULL, 'A1');

    $rowIndex = 2;
    foreach ($data as $index => $row) {
        $error_msg = isset($errors[$index]) ? $errors[$index] : '';
        $row_data = [
            trim($row['A'] ?? ''), trim($row['B'] ?? ''), trim($row['C'] ?? ''),
            trim($row['D'] ?? ''), trim($row['E'] ?? ''), trim($row['F'] ?? ''),
            (int)($row['G'] ?? 0), $error_msg
        ];
        $sheet->fromArray($row_data, NULL, 'A' . $rowIndex);
        $rowIndex++;
    }

    $writer = new Xlsx($spreadsheet);
    $writer->save($reportFile);

    return '../Uploads/reports/' . basename($reportFile);
}

// Fungsi untuk bulk_inbound
function generateErrorReportAllRows($data, $errors, $reportDir) {
    global $baseDir;
    $reportDir = $baseDir . '/Uploads/reports';
    if (!file_exists($reportDir)) {
        mkdir($reportDir, 0775, true);
        writeLog("Created reports directory: $reportDir");
    }
    $reportFile = $reportDir . '/error_report_' . time() . '.csv';
    writeLog("Generating error report at: $reportFile");
    $file = fopen($reportFile, 'w');
    
    // Header dengan kolom tambahan
    fputcsv($file, ['PO Number', 'Supplier', 'Reference Number', 'Packing List', 'Item Code', 'Qty', 'Locator', 'Error Log']);
    
    // Loop semua baris data
    foreach ($data as $index => $row) {
        $rowData = [
            trim($row['A'] ?? ''),  // PO Number
            trim($row['B'] ?? ''),  // Supplier
            trim($row['C'] ?? ''),  // Reference Number
            trim($row['D'] ?? ''),  // Packing List
            trim($row['E'] ?? ''),  // Item Code
            trim($row['F'] ?? ''),  // Qty
            trim($row['G'] ?? ''),  // Locator
        ];
        
        // Jika ada error untuk baris ini, gunakan error message
        if (isset($errors[$index])) {
            $rowData[] = $errors[$index]; // Error message
        } else {
            $rowData[] = 'Pending'; // Baris yang valid tapi tidak diproses karena ada error di baris lain
        }
        
        fputcsv($file, $rowData);
    }
    
    fclose($file);
    return '../Uploads/reports/' . basename($reportFile);
}

// Fungsi untuk bulk_allocated
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
                    'locator' => $stock_row['locator'] ?? 'POOL',
                    'qty' => $qty_to_allocate,
                    'id' => $stock_row['id'],
                    'packing_list' => $stock_row['packing_list']
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

// Fungsi untuk bulk_allocated
function updateStockBalance($conn, $stock_id, $allocated_qty) {
    try {
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
        
        $verify_sql = "SELECT stock_balance, qty_allocated, stock_on_hand FROM stock WHERE id = ?";
        $verify_stmt = $conn->prepare($verify_sql);
        $verify_stmt->bind_param("i", $stock_id);
        $verify_stmt->execute();
        $result = $verify_stmt->get_result();
        $row = $result->fetch_assoc();
        $verify_stmt->close();
        
        if ($row['stock_balance'] < 0) {
            throw new Exception("Stock balance became negative for stock ID: $stock_id after allocation.");
        }
        
        writeLog("Stock updated - ID: $stock_id, Allocated Qty: $allocated_qty, New Balance: {$row['stock_balance']}, On Hand: {$row['stock_on_hand']}, Total Allocated: {$row['qty_allocated']}");
        
        return true;
        
    } catch (Exception $e) {
        throw new Exception("Update stock balance failed: " . $e->getMessage());
    }
}

// Fungsi untuk bulk_inbound
function checkDuplicates($conn, $data, &$errors) {
    $duplicateFound = false;
    
    // Cek duplicate PO Number
    foreach ($data as $index => $row) {
        $po_number = trim($row['A'] ?? '');
        if (!empty($po_number)) {
            $check_po = $conn->prepare("SELECT COUNT(*) as count FROM inbound WHERE po_number = ?");
            $check_po->bind_param("s", $po_number);
            $check_po->execute();
            $po_result = $check_po->get_result();
            $po_row = $po_result->fetch_assoc();
            
            if ($po_row['count'] > 0) {
                $errors[$index] = 'duplicate PO Number';
                $duplicateFound = true;
                writeLog("Duplicate PO Number found: $po_number on row " . ($index + 2), 'WARNING');
            }
            $check_po->close();
        }
    }
    
    // Cek duplicate Packing List
    foreach ($data as $index => $row) {
        // Skip jika sudah ada error untuk baris ini
        if (isset($errors[$index])) continue;
        
        $packing_list = trim($row['D'] ?? '');
        if (!empty($packing_list)) {
            $check_packing = $conn->prepare("SELECT COUNT(*) as count FROM inbound WHERE packing_list = ?");
            $check_packing->bind_param("s", $packing_list);
            $check_packing->execute();
            $packing_result = $check_packing->get_result();
            $packing_row = $packing_result->fetch_assoc();
            
            if ($packing_row['count'] > 0) {
                $errors[$index] = 'duplicate Packing List';
                $duplicateFound = true;
                writeLog("Duplicate Packing List found: $packing_list on row " . ($index + 2), 'WARNING');
            }
            $check_packing->close();
        }
    }
    
    return $duplicateFound;
}

// Fungsi untuk memproses bulk_allocated
function processBulkAllocated($conn, $task) {
    global $baseDir;
    $task_id = $task['id'];
    $task_type = $task['task_type'];
    $file_name = trim($task['file_name']);
    $file_path = $baseDir . '/Uploads/' . $file_name;
    $username = $task['username'];
    $wh_name = trim($task['wh_name']);
    $project_name = $task['project_name'];

    writeLog("Processing task ID: $task_id, Type: $task_type, File: $file_path");

    if (!file_exists($file_path)) {
        writeLog("File not found: $file_path", 'ERROR');
        throw new Exception("File $file_path tidak ditemukan.");
    }

    $updateStmt = $conn->prepare("UPDATE queue_task SET status = 'processing', processed_at = NOW() WHERE id = ?");
    $updateStmt->bind_param("i", $task_id);
    $updateStmt->execute();
    $updateStmt->close();

    try {
        $conn->begin_transaction();

        $check_wh = $conn->prepare("SELECT wh_name FROM warehouses WHERE wh_name = ?");
        $check_wh->bind_param("s", $wh_name);
        $check_wh->execute();
        $wh_result = $check_wh->get_result();
        if ($wh_result->num_rows == 0) {
            $wh_list_query = $conn->query("SELECT wh_name FROM warehouses");
            $valid_warehouses = [];
            while ($row = $wh_list_query->fetch_assoc()) {
                $valid_warehouses[] = $row['wh_name'];
            }
            $wh_list = implode(", ", $valid_warehouses);
            throw new Exception("WH_Name $wh_name tidak valid. Daftar gudang yang tersedia: $wh_list");
        }
        $wh_row = $wh_result->fetch_assoc();
        $wh_name = $wh_row['wh_name'];
        $check_wh->close();

        $check_project = $conn->prepare("SELECT Project_Name FROM project_name WHERE Project_Name = ?");
        $check_project->bind_param("s", $project_name);
        $check_project->execute();
        $project_result = $check_project->get_result();
        if ($project_result->num_rows == 0) {
            throw new Exception("Project_Name $project_name tidak valid.");
        }
        $check_project->close();

        $spreadsheet = IOFactory::load($file_path);
        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray(null, true, true, true);
        array_shift($data);

        $errors = [];
        $hasAnyError = false;

        $transaction_id = generateTransactionId($conn, 'FIS-ALLOC');
        $existing_orders = [];
        $stmt_check_dup = $conn->prepare("SELECT DISTINCT order_number FROM allocated WHERE wh_name = ? AND project_name = ?");
        $stmt_check_dup->bind_param("ss", $wh_name, $project_name);
        $stmt_check_dup->execute();
        $dup_result = $stmt_check_dup->get_result();
        while ($dup_row = $dup_result->fetch_assoc()) {
            $existing_orders[$dup_row['order_number']] = true;
        }
        $stmt_check_dup->close();

        // STEP 1: VALIDASI SEMUA DATA DAN GROUPING
        writeLog("STEP 1: Starting data validation and grouping");
        $grouped = [];
        foreach ($data as $index => $row) {
            try {
                $order_number = trim($row['A'] ?? '');
                $customer = trim($row['B'] ?? '');
                $lottable1 = trim($row['C'] ?? '');
                $lottable2 = trim($row['D'] ?? '');
                $lottable3 = trim($row['E'] ?? '');
                $item_code = trim($row['F'] ?? '');
                $qty = (int)($row['G'] ?? 0);

                $validationErrors = [];
                if (empty($order_number)) $validationErrors[] = 'Order Number kosong';
                if (empty($customer)) $validationErrors[] = 'Customer kosong';
                if (empty($lottable3)) $validationErrors[] = 'Destination kosong';
                if (empty($item_code)) $validationErrors[] = 'Item Code kosong';
                if ($qty <= 0) $validationErrors[] = 'Qty tidak valid (<= 0)';

                if (!empty($validationErrors)) {
                    $errorMsg = "Data tidak lengkap: " . implode(', ', $validationErrors);
                    $errors[$index] = $errorMsg;
                    $hasAnyError = true;
                    writeLog("Validation error on row " . ($index + 2) . ": $errorMsg", 'WARNING');
                    continue;
                }

                if (isset($existing_orders[$order_number])) {
                    $errors[$index] = "duplicate Order Number";
                    $hasAnyError = true;
                    writeLog("Duplicate order number on row " . ($index + 2) . ": $order_number", 'WARNING');
                    continue;
                }

                $check_stmt = $conn->prepare("SELECT item_description, uom, volume FROM master_sku WHERE item_code = ? AND project = ?");
                $check_stmt->bind_param("ss", $item_code, $project_name);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                if ($check_result->num_rows == 0) {
                    $errorMsg = "Item Code $item_code tidak ditemukan atau tidak sesuai project $project_name";
                    $errors[$index] = $errorMsg;
                    $hasAnyError = true;
                    writeLog("Item code validation error on row " . ($index + 2) . ": $errorMsg", 'WARNING');
                    $check_stmt->close();
                    continue;
                }
                $sku = $check_result->fetch_assoc();
                $item_description = $sku['item_description'];
                $uom = $sku['uom'] ?? 'PCS';
                $volume = (float)($sku['volume'] ?? 0);
                $check_stmt->close();

                $key = implode('|', [$order_number, $customer, $lottable1, $lottable2, $lottable3, $item_code, $uom, $project_name, $wh_name, $transaction_id]);

                if (!isset($grouped[$key])) {
                    $grouped[$key] = [
                        'order_number' => $order_number,
                        'customer' => $customer,
                        'lottable1' => $lottable1,
                        'lottable2' => $lottable2,
                        'lottable3' => $lottable3,
                        'item_code' => $item_code,
                        'item_description' => $item_description,
                        'qty_picking' => 0,
                        'uom' => $uom,
                        'volume' => $volume,
                        'created_by' => $username,
                        'project_name' => $project_name,
                        'wh_name' => $wh_name,
                        'transaction_sequence' => $transaction_id,
                        'rows' => []
                    ];
                }
                $grouped[$key]['qty_picking'] += $qty;
                $grouped[$key]['rows'][] = $index + 2;

            } catch (Exception $e) {
                $errors[$index] = $e->getMessage();
                $hasAnyError = true;
                writeLog("Unexpected error on row " . ($index + 2) . ": " . $e->getMessage(), 'ERROR');
            }
        }

        // STEP 2: CEK STOCK AVAILABILITY UNTUK SEMUA ITEM
        writeLog("STEP 2: Checking stock availability for all items");
        foreach ($grouped as $key => $grouped_data) {
            $item_code = $grouped_data['item_code'];
            $qty_needed = $grouped_data['qty_picking'];
            
            try {
                // Cek available stock tanpa locking dulu
                $check_stock_sql = "SELECT SUM(stock_on_hand - qty_allocated) as available_stock 
                                   FROM stock 
                                   WHERE item_code = ? AND wh_name = ? AND project_name = ? 
                                   AND (stock_on_hand - qty_allocated) > 0
                                   AND status_stock = 'Active'";
                                   
                $check_stock_stmt = $conn->prepare($check_stock_sql);
                $check_stock_stmt->bind_param("sss", $item_code, $wh_name, $project_name);
                $check_stock_stmt->execute();
                $stock_result = $check_stock_stmt->get_result();
                $stock_row = $stock_result->fetch_assoc();
                $check_stock_stmt->close();
                
                $available_stock = (int)($stock_row['available_stock'] ?? 0);
                
                writeLog("Stock check for item $item_code: Available=$available_stock, Required=$qty_needed");
                
                if ($available_stock < $qty_needed) {
                    $errorMsg = "Insufficient stock for Item Code $item_code. Required: $qty_needed, Available: $available_stock";
                    writeLog("STOCK ERROR: $errorMsg", 'ERROR');
                    
                    // Mark semua baris untuk item ini sebagai error
                    foreach ($grouped_data['rows'] as $row_num) {
                        $errors[$row_num - 2] = $errorMsg;
                    }
                    $hasAnyError = true;
                }
                
            } catch (Exception $e) {
                $errorMsg = "Error checking stock for item $item_code: " . $e->getMessage();
                writeLog("STOCK CHECK ERROR: $errorMsg", 'ERROR');
                
                foreach ($grouped_data['rows'] as $row_num) {
                    $errors[$row_num - 2] = $errorMsg;
                }
                $hasAnyError = true;
            }
        }

        // STEP 3: JIKA ADA ERROR, ROLLBACK DAN BUAT REPORT
        if ($hasAnyError) {
            writeLog("STEP 3: Errors found, rolling back transaction. Total errors: " . count($errors), 'WARNING');
            
            // Mark baris yang tidak ada error sebagai "Pending"
            foreach ($data as $index => $row) {
                if (!isset($errors[$index])) {
                    $errors[$index] = "Pending - Transaction cancelled due to errors in other rows";
                }
            }
            
            $conn->rollback();
            $status = 'error';
            $error_message = "Terdapat kesalahan dalam data. Seluruh transaksi dibatalkan. Total errors: " . count(array_filter($errors, function($e) { return $e !== 'Pending - Transaction cancelled due to errors in other rows'; }));
            $report_path = generateErrorReport($data, $errors, $baseDir . '/Uploads/reports');
            writeLog("Error report generated: $report_path");
            
            $updateStmt = $conn->prepare(
                "UPDATE queue_task SET status = ?, error_message = ?, success_count = 0, processed_at = NOW(), report_path = ? WHERE id = ?"
            );
            $updateStmt->bind_param("sssi", $status, $error_message, $report_path, $task_id);
            $updateStmt->execute();
            $updateStmt->close();
            
            writeLog("Task ID $task_id ($task_type) completed with status: $status");
            return;
        }

        // STEP 4: JIKA TIDAK ADA ERROR, LANJUTKAN PROCESSING
        writeLog("STEP 4: No errors found, proceeding with allocation");
        
        $stmt_allocated = $conn->prepare(
            "INSERT INTO allocated (transaction_sequence, order_number, customer, lottable1, lottable2, lottable3, item_code, item_description, qty_picking, uom, volume, created_by, project_name, wh_name, locator_picking, packing_list)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        if (!$stmt_allocated) {
            throw new Exception("Prepare allocated failed: " . $conn->error);
        }

        $success_count = 0;
        foreach ($grouped as $key => $grouped_data) {
            try {
                $allocated_stocks = allocateStockWithLocking($conn, $grouped_data['item_code'], $grouped_data['qty_picking'], $wh_name, $project_name);

                foreach ($allocated_stocks as $allocated) {
                    $locator_picking = $allocated['locator'];
                    $allocated_qty = $allocated['qty'];
                    $stock_id = $allocated['id'];
                    $packing_list = $allocated['packing_list'];

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
                    $total_volume = $grouped_data['volume'] * $allocated_qty;

                    updateStockBalance($conn, $stock_id, $allocated_qty);

                    $stmt_allocated->bind_param(
                        "ssssssssisdsssss",
                        $grouped_data['transaction_sequence'],
                        $grouped_data['order_number'],
                        $grouped_data['customer'],
                        $grouped_data['lottable1'],
                        $grouped_data['lottable2'],
                        $grouped_data['lottable3'],
                        $grouped_data['item_code'],
                        $grouped_data['item_description'],
                        $allocated_qty,
                        $grouped_data['uom'],
                        $total_volume,
                        $grouped_data['created_by'],
                        $grouped_data['project_name'],
                        $grouped_data['wh_name'],
                        $locator_picking,
                        $packing_list
                    );
                    if (!$stmt_allocated->execute()) {
                        throw new Exception("Failed to insert allocation record for item_code: {$grouped_data['item_code']}");
                    }

                    writeLog("SUCCESS: Inserted allocated for item_code: {$grouped_data['item_code']}, qty_picking: $allocated_qty, locator_picking: $locator_picking");
                }
                
                $success_count += count($grouped_data['rows']);
                
            } catch (Exception $e) {
                // Ini tidak seharusnya terjadi karena sudah di-validate di step 2
                writeLog("UNEXPECTED ERROR during allocation for item {$grouped_data['item_code']}: " . $e->getMessage(), 'ERROR');
                throw $e; // Re-throw untuk rollback
            }
        }

        $stmt_allocated->close();

        // STEP 5: CONSISTENCY CHECK
        writeLog("STEP 5: Running consistency check");
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
            writeLog("CRITICAL: Data consistency check failed! Found {$consistency_row['negative_stocks']} records with negative stock balance", 'ERROR');
            throw new Exception("CRITICAL: Data consistency check failed! Found {$consistency_row['negative_stocks']} records with negative stock balance.");
        }

        // COMMIT TRANSACTION
        $conn->commit();
        $status = 'success';
        $error_message = null;
        $report_path = null;

        $updateStmt = $conn->prepare(
            "UPDATE queue_task SET status = ?, error_message = ?, success_count = ?, processed_at = NOW(), report_path = ? WHERE id = ?"
        );
        $updateStmt->bind_param("ssisi", $status, $error_message, $success_count, $report_path, $task_id);
        $updateStmt->execute();
        $updateStmt->close();

        writeLog("SUCCESS: Task ID $task_id completed");

    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Error processing task ID $task_id: " . $e->getMessage();
        writeLog($error_message, 'ERROR');

        // Buat laporan error untuk semua baris
        $report_path = null;
        if (!empty($data)) {
            $report_path = generateErrorReport($data, $errors, '../Uploads/reports');
        }

        $updateStmt = $conn->prepare(
            "UPDATE queue_task SET status = 'error', error_message = ?, processed_at = NOW(), report_path = ? WHERE id = ?"
        );
        $updateStmt->bind_param("ssi", $error_message, $report_path, $task_id);
        $updateStmt->execute();
        $updateStmt->close();
    }
}

// Fungsi untuk memproses bulk_inbound
function processBulkInbound($conn, $task) {
    global $baseDir;
    $task_id = $task['id'];
    $file_name = trim($task['file_name']);
    $file_path = $baseDir . '/Uploads/' . $file_name;
    $username = $task['username'];
    $wh_name = trim($task['wh_name']);
    $project_name = $task['project_name'];

    writeLog("Processing task ID: $task_id, File: $file_path");

    // Cek keberadaan file
    if (!file_exists($file_path)) {
        writeLog("File not found: $file_path", 'ERROR');
        throw new Exception("File $file_path tidak ditemukan.");
    }

    // Update status menjadi 'processing'
    $updateStmt = $conn->prepare("UPDATE queue_task SET status = 'processing', processed_at = NOW() WHERE id = ?");
    $updateStmt->bind_param("i", $task_id);
    $updateStmt->execute();
    $updateStmt->close();

    try {
        $conn->begin_transaction();

        // Validasi wh_name
        $check_wh = $conn->prepare("SELECT wh_name FROM warehouses WHERE wh_name = ?");
        $check_wh->bind_param("s", $wh_name);
        $check_wh->execute();
        $wh_result = $check_wh->get_result();
        if ($wh_result->num_rows == 0) {
            $wh_list_query = $conn->query("SELECT wh_name FROM warehouses");
            $valid_warehouses = [];
            while ($row = $wh_list_query->fetch_assoc()) {
                $valid_warehouses[] = $row['wh_name'];
            }
            $wh_list = implode(", ", $valid_warehouses);
            throw new Exception("WH_Name $wh_name tidak valid. Daftar gudang yang tersedia: $wh_list");
        }
        $wh_row = $wh_result->fetch_assoc();
        $wh_name = $wh_row['wh_name'];
        $check_wh->close();

        // Validasi project_name
        $check_project = $conn->prepare("SELECT Project_Name FROM project_name WHERE Project_Name = ?");
        $check_project->bind_param("s", $project_name);
        $check_project->execute();
        $project_result = $check_project->get_result();
        if ($project_result->num_rows == 0) {
            throw new Exception("Project_Name $project_name tidak valid.");
        }
        $check_project->close();

        // Baca file Excel
        $spreadsheet = IOFactory::load($file_path);
        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray(null, true, true, true);
        array_shift($data); // Hapus header

        $errors = [];
        $hasAnyError = false;
        
        // PENGECEKAN DUPLICATE PO NUMBER DAN PACKING LIST
        writeLog("Checking for duplicates...");
        $duplicateFound = checkDuplicates($conn, $data, $errors);
        
        if ($duplicateFound) {
            $hasAnyError = true;
            writeLog("Duplicates found, will rollback transaction", 'WARNING');
        }

        // VALIDASI SEMUA DATA SEBELUM PROCESSING
        foreach ($data as $index => $row) {
            // Skip jika sudah ada error duplicate untuk baris ini
            if (isset($errors[$index])) continue;
            
            $po_number = trim($row['A'] ?? '');
            $supplier = trim($row['B'] ?? '');
            $reference_number = trim($row['C'] ?? '');
            $packing_list = trim($row['D'] ?? '');
            $item_code = trim($row['E'] ?? '');
            $qty = (int)($row['F'] ?? 0);
            $locator = trim($row['G'] ?? '');
            $stock_type = trim($row['H'] ?? '');

            // Validasi data kosong
            $validationErrors = [];
            if (empty($po_number)) $validationErrors[] = 'PO Number kosong';
            if (empty($supplier)) $validationErrors[] = 'Supplier kosong';
            if (empty($reference_number)) $validationErrors[] = 'Reference Number kosong';
            if (empty($packing_list)) $validationErrors[] = 'Packing List kosong';
            if (empty($item_code)) $validationErrors[] = 'Item Code kosong';
            if ($qty <= 0) $validationErrors[] = 'Qty tidak valid (<= 0)';
            if (empty($locator)) $validationErrors[] = 'Locator kosong';
            if (empty($stock_type)) $validationErrors[] = 'Stock Type kosong';

            if (!empty($validationErrors)) {
                $errors[$index] = "Data tidak lengkap pada baris " . ($index + 2) . ": " . implode(', ', $validationErrors);
                $hasAnyError = true;
                continue;
            }

            // Validasi locator
            $check_locator = $conn->prepare("SELECT COUNT(*) as count FROM master_locator WHERE locator = ? AND wh_name = ?");
            $check_locator->bind_param("ss", $locator, $wh_name);
            $check_locator->execute();
            $locator_result = $check_locator->get_result();
            $locator_row = $locator_result->fetch_assoc();
            if ($locator_row['count'] == 0) {
                $errors[$index] = "Locator $locator tidak valid untuk WH_Name $wh_name pada baris " . ($index + 2);
                $hasAnyError = true;
                $check_locator->close();
                continue;
            }
            $check_locator->close();

            // Validasi item_code
            $check_stmt = $conn->prepare("SELECT item_description, uom FROM master_sku WHERE item_code = ? AND project = ?");
            $check_stmt->bind_param("ss", $item_code, $project_name);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            if ($check_result->num_rows == 0) {
                $errors[$index] = "Item Code $item_code tidak ditemukan atau tidak sesuai project $project_name pada baris " . ($index + 2);
                $hasAnyError = true;
                $check_stmt->close();
                continue;
            }
            $check_stmt->close();
        }

        // JIKA ADA ERROR APAPUN, ROLLBACK DAN BUAT REPORT
        if ($hasAnyError) {
            writeLog("Errors found, rolling back transaction", 'WARNING');
            throw new Exception("Terdapat kesalahan dalam data. Seluruh transaksi dibatalkan.");
        }

        // JIKA TIDAK ADA ERROR, LANJUTKAN PROCESSING SEPERTI BIASA
        $transaction_id = generateTransactionId($conn, 'FIS-IN');
        $success_count = 0;

        // Kelompokkan data berdasarkan kombinasi unik
        $grouped_data = [];
        foreach ($data as $index => $row) {
            $po_number = trim($row['A'] ?? '');
            $supplier = trim($row['B'] ?? '');
            $reference_number = trim($row['C'] ?? '');
            $packing_list = trim($row['D'] ?? '');
            $item_code = trim($row['E'] ?? '');
            $qty = (int)($row['F'] ?? 0);
            $locator = trim($row['G'] ?? '');
            $stock_type = trim($row['H'] ?? '');

            // Get item details (sudah divalidasi di atas)
            $check_stmt = $conn->prepare("SELECT item_description, uom, volume FROM master_sku WHERE item_code = ? AND project = ?");
            $check_stmt->bind_param("ss", $item_code, $project_name);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            $sku = $check_result->fetch_assoc();
            $item_description = $sku['item_description'];
            $uom = $sku['uom'];
            $volume_per_unit = (float)($sku['volume'] ?? 0);
            $check_stmt->close();

            // Buat kunci unik untuk pengelompokan
            $key = implode('|', [$po_number, $supplier, $reference_number, $packing_list, $item_code, $locator, $stock_type, $project_name, $wh_name, $transaction_id]);

            // Kelompokkan data
            if (!isset($grouped_data[$key])) {
                $grouped_data[$key] = [
                    'po_number' => $po_number,
                    'supplier' => $supplier,
                    'reference_number' => $reference_number,
                    'packing_list' => $packing_list,
                    'item_code' => $item_code,
                    'item_description' => $item_description,
                    'qty' => 0,
                    'uom' => $uom,
                    'volume_per_unit' => $volume_per_unit,  // TAMBAHKAN INI
                    'locator' => $locator,
                    'stock_type' => $stock_type,
                    'username' => $username,
                    'project_name' => $project_name,
                    'wh_name' => $wh_name,
                    'transaction_id' => $transaction_id,
                    'rows' => []
                ];
            }
            $grouped_data[$key]['qty'] += $qty;
            $grouped_data[$key]['rows'][] = $index + 2;
        }

        // Persiapkan statement untuk insert ke inbound
        $stmt_inbound = $conn->prepare(
            "INSERT INTO inbound (po_number, supplier, reference_number, packing_list, item_code, item_description, qty, uom, volume, locator, stock_type, created_by, project_name, wh_name, transaction_sequence)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        if (!$stmt_inbound) {
            throw new Exception("Prepare inbound failed: " . $conn->error);
        }

        // Insert data yang telah dikelompokkan
        foreach ($grouped_data as $key => $grouped) {
            // Hitung total volume
            $total_volume = $grouped['volume_per_unit'] * $grouped['qty'];
            
            $stmt_inbound->bind_param(
                "ssssssisdssssss",
                $grouped['po_number'],
                $grouped['supplier'],
                $grouped['reference_number'],
                $grouped['packing_list'],
                $grouped['item_code'],
                $grouped['item_description'],
                $grouped['qty'],
                $grouped['uom'],
                $total_volume, 
                $grouped['locator'],
                $grouped['stock_type'],
                $grouped['username'],
                $grouped['project_name'],
                $grouped['wh_name'],
                $grouped['transaction_id']
            );
            $stmt_inbound->execute();
            $success_count += count($grouped['rows']);
            writeLog("Inserted inbound for item_code: {$grouped['item_code']}, qty: {$grouped['qty']}, locator: {$grouped['locator']}, transaction_sequence: {$grouped['transaction_id']}");
        }

        // Commit transaksi
        $conn->commit();

        // Update status queue_task berhasil
        $updateStmt = $conn->prepare(
            "UPDATE queue_task SET status = 'success', success_count = ?, processed_at = NOW() WHERE id = ?"
        );
        $updateStmt->bind_param("ii", $success_count, $task_id);
        $updateStmt->execute();
        $updateStmt->close();

        writeLog("Task ID $task_id completed successfully with success_count: $success_count");

    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Error processing task ID $task_id: " . $e->getMessage();
        writeLog($error_message, 'ERROR');

        // Buat laporan error untuk semua baris
        $report_path = null;
        if (!empty($data)) {
            $report_path = generateErrorReportAllRows($data, $errors, '../Uploads/reports');
        }

        $updateStmt = $conn->prepare(
            "UPDATE queue_task SET status = 'error', error_message = ?, processed_at = NOW(), report_path = ? WHERE id = ?"
        );
        $updateStmt->bind_param("ssi", $error_message, $report_path, $task_id);
        $updateStmt->execute();
        $updateStmt->close();
    }
}

// MAIN EXECUTION
try {
    writeLog("Starting combined queue task processing for bulk_allocated and bulk_inbound");
    
    // Test database connection
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception("Database connection failed: " . (isset($conn) ? $conn->connect_error : 'Connection object not found'));
    }
    
    writeLog("Database connection successful");
    
    $query = "SELECT * FROM queue_task WHERE status = 'pending' AND task_type IN ('bulk_allocated', 'bulk_inbound') ORDER BY created_at ASC LIMIT 10";
    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }
    
    if ($result->num_rows == 0) {
        writeLog("No pending bulk_allocated or bulk_inbound tasks found in queue_task");
        exit(0);
    }

    writeLog("Found {$result->num_rows} pending tasks to process");

    while ($task = $result->fetch_assoc()) {
        $task_type = $task['task_type'];
        $task_id = $task['id'];
        
        try {
            writeLog("Processing task ID: $task_id, Type: $task_type");
            
            if ($task_type === 'bulk_allocated') {
                processBulkAllocated($conn, $task);
            } elseif ($task_type === 'bulk_inbound') {
                processBulkInbound($conn, $task);
            } else {
                writeLog("Unknown task type: $task_type", 'WARNING');
                continue;
            }
            
            writeLog("Task ID $task_id completed successfully");
            
        } catch (Exception $e) {
            writeLog("Error processing task ID $task_id ($task_type): " . $e->getMessage(), 'ERROR');
            
            // Update task status to error if not already updated
            $error_stmt = $conn->prepare("UPDATE queue_task SET status = 'error', error_message = ?, processed_at = NOW() WHERE id = ? AND status = 'processing'");
            $error_message = $e->getMessage();
            $error_stmt->bind_param("si", $error_message, $task_id);
            $error_stmt->execute();
            $error_stmt->close();
        }
    }

    writeLog("All tasks processed successfully");
    exit(0);

} catch (Exception $e) {
    writeLog("Fatal error: " . $e->getMessage(), 'ERROR');
    exit(1);
} finally {
    if (isset($conn)) {
        $conn->close();
        writeLog("Database connection closed");
    }
    writeLog("Script execution completed at: " . date('Y-m-d H:i:s'));
}
?>