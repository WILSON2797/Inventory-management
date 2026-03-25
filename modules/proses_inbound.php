<?php
session_start();
include '../php/config.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['status' => 'error', 'message' => 'Anda harus login terlebih dahulu!']);
    exit();
}

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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        error_log("Starting inbound process");
        $conn->begin_transaction();
        
        $transaction_id = generateTransactionId($conn, 'FIS-IN');
        $po_number = $_POST['po_number'] ?? '';
        
        // PERBAIKAN: Definisi variabel supplier yang benar
        $supplier = $_POST['supplier'] ?? '';
        
        $reference_number = $_POST['reference_number'] ?? '';
        $packing_list = $_POST['packing_list'] ?? '';
        $stock_type = $_POST['stock_type'] ?? '';
        $wh_name = trim($_SESSION['wh_name'] ?? ''); // Hilangkan spasi
        $project_name = $_SESSION['project_name'] ?? '';
        $created_by = $_SESSION['username'] ?? '';
        
        error_log("Form Data - WH_NAME: '$wh_name', PO: $po_number, Supplier: $supplier, Ref: $reference_number, PL: $packing_list, ST: $stock_type, Project: $project_name, Created By: $created_by");
        
        // Periksa apakah wh_name dan project_name ada di session
        if (empty($wh_name) || empty($project_name)) {
            throw new Exception("WH_NAME atau Project tidak ditemukan di session. Silakan login ulang atau pilih gudang.");
        }
        
        // PERBAIKAN: Validasi supplier - jika kosong, berikan nilai default
        if (empty($supplier)) {
            $supplier = 'N/A'; // atau bisa juga throw exception jika supplier wajib diisi
            // throw new Exception("Supplier harus diisi");
        }
        
        // VALIDASI PO NUMBER - TAMBAHAN BARU: Cek duplikasi PO Number
        if (!empty($po_number)) {
            // Cek berdasarkan transaction_sequence yang berbeda
            // Karena dalam satu transaction boleh ada multiple items dengan PO number sama
            $check_po = $conn->prepare("
                SELECT COUNT(DISTINCT transaction_sequence) as count 
                FROM inbound 
                WHERE po_number = ?
            ");
            $check_po->bind_param("s", $po_number);
            $check_po->execute();
            $po_result = $check_po->get_result();
            $po_row = $po_result->fetch_assoc();
            
            if ($po_row['count'] > 0) {
                throw new Exception("PO Number Already Exist!");
            }
            $check_po->close();
        } else {
            throw new Exception("PO Number tidak boleh kosong");
        }
        
        // VALIDASI PACKING LIST - TAMBAHAN BARU: Cek duplikasi packing list
        if (!empty($packing_list)) {
            // Cek berdasarkan transaction_sequence yang berbeda
            // Karena dalam satu transaction boleh ada multiple items dengan packing list sama
            $check_packing = $conn->prepare("
                SELECT COUNT(DISTINCT transaction_sequence) as count 
                FROM inbound 
                WHERE packing_list = ?
            ");
            $check_packing->bind_param("s", $packing_list);
            $check_packing->execute();
            $packing_result = $check_packing->get_result();
            $packing_row = $packing_result->fetch_assoc();
            
            if ($packing_row['count'] > 0) {
                throw new Exception("Packing List Already Exist!");
            }
            $check_packing->close();
        } else {
            throw new Exception("Packing List tidak boleh kosong");
        }
        
        // Validasi wh_name
        error_log("Memeriksa WH_NAME: '$wh_name'");
        $check_wh = $conn->prepare("SELECT wh_name FROM warehouses WHERE wh_name = ?");
        $check_wh->bind_param("s", $wh_name);
        $check_wh->execute();
        $wh_result = $check_wh->get_result();
        if ($wh_result->num_rows == 0) {
            // Ambil daftar gudang yang valid
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
        
        if (!isset($_POST['items']) || !is_array($_POST['items'])) {
            throw new Exception("Data item tidak valid: " . json_encode($_POST));
        }
        
        $items = $_POST['items'];
        error_log("Items received: " . json_encode($items));
        
        // Pengelompokan item yang sama (item_code, locator, dll.) untuk digabungkan qty-nya
        $grouped_items = [];
        foreach ($items as $item) {
            if (!isset($item['item_code'], $item['qty'], $item['locator'])) {
                throw new Exception("Data item tidak lengkap: " . json_encode($item));
            }
            
            $key = implode('|', [$item['item_code'], $item['locator'], $packing_list, $wh_name, $project_name]);
            
            if (!isset($grouped_items[$key])) {
                $grouped_items[$key] = [
                    'item_code' => $item['item_code'],
                    'locator' => $item['locator'],
                    'qty' => 0
                ];
            }
            $grouped_items[$key]['qty'] += (int)$item['qty'];
        }
        
        // Ubah query INSERT untuk menyertakan transaction_sequence
        $stmt_inbound = $conn->prepare(
            "INSERT INTO inbound (po_number, supplier, reference_number, packing_list, stock_type, item_code, item_description, qty, uom, volume, locator, created_by, project_name, wh_name, transaction_sequence)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        if (!$stmt_inbound) {
            throw new Exception("Prepare inbound failed: " . $conn->error);
        }
        
        // Loop grouped items untuk insert
        foreach ($grouped_items as $key => $grouped) {
            // Validasi locator terhadap master_locator
            $check_locator = $conn->prepare("SELECT COUNT(*) as count FROM master_locator WHERE locator = ? AND wh_name = ?");
            $check_locator->bind_param("ss", $grouped['locator'], $wh_name);
            $check_locator->execute();
            $locator_result = $check_locator->get_result();
            $locator_row = $locator_result->fetch_assoc();
            if ($locator_row['count'] == 0) {
                throw new Exception("Locator {$grouped['locator']} tidak valid untuk WH_Name $wh_name");
            }
            $check_locator->close();
            
            // Validasi item_code dengan project
            $check_stmt = $conn->prepare("SELECT item_description, uom, volume FROM master_sku WHERE item_code = ? AND project = ?");
            $check_stmt->bind_param("ss", $grouped['item_code'], $project_name);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            if ($check_result->num_rows == 0) {
                throw new Exception("Item Code {$grouped['item_code']} tidak ditemukan atau tidak sesuai project $project_name");
            }
            $sku = $check_result->fetch_assoc();
            $item_description = $sku['item_description'];
            $uom = $sku['uom'];
            $volume_per_unit = (float)$sku['volume']; // volume dari master_sku
            $check_stmt->close();
            
            $item_code = $grouped['item_code'];
            $qty = $grouped['qty'];
            $locator = $grouped['locator'];
            
            // Hitung total volume
            $total_volume = $volume_per_unit * $qty;
            
            if ($qty <= 0) {
                throw new Exception("Qty harus lebih dari 0 untuk item $item_code");
            }
            
            error_log("Processing grouped item - Code: $item_code, Qty: $qty, UOM: $uom, Locator: $locator, Desc: $item_description, Transaction Sequence: $transaction_id");
            
            // Bind 13 parameters - PERBAIKAN URUTAN PARAMETER
            // Urutan: po_number, supplier, reference_number, packing_list, item_code, item_description, qty, uom, volume, locator, created_by, project_name, wh_name, transaction_sequence
            $stmt_inbound->bind_param("sssssssisdsssss", $po_number, $supplier, $reference_number, $packing_list, $stock_type, $item_code, $item_description, $qty, $uom, $total_volume, $locator, $created_by, $project_name, $wh_name, $transaction_id);
            $stmt_inbound->execute();
            error_log("Inbound inserted for $item_code with Locator: $locator, Transaction Sequence: $transaction_id");
        }
        
        $conn->commit();
        error_log("Transaction committed");
        
        echo json_encode(['status' => 'success', 'message' => 'Data berhasil disimpan', 'transaction_id' => $transaction_id]);
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = $e->getMessage();
        error_log("Error: " . $e->getMessage() . " di baris " . $e->getLine() . " file " . $e->getFile());
        echo json_encode(['status' => 'error', 'message' => $error_message]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Metode request tidak valid']);
}

$conn->close();
?>