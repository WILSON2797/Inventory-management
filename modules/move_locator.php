<?php
// modules/move_locator.php
session_start();
require_once '../php/config.php'; // Atau '../config/db.php' jika beda

header('Content-Type: application/json');

// Pastikan user sudah login
if (!isset($_SESSION['username'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit;
}

// Validasi metode request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Ambil data dari JSON input
$data = json_decode(file_get_contents('php://input'), true);
$stockId = isset($data['id']) ? intval($data['id']) : 0;
$newLocator = isset($data['new_locator']) ? trim($data['new_locator']) : '';
$reason = isset($data['reason']) ? trim($data['reason']) : '';
$qtyToMove = isset($data['qty_to_move']) ? intval($data['qty_to_move']) : 0;
$username = $_SESSION['username'];

try {
    if (empty($stockId) || empty($newLocator) || empty($reason) || $qtyToMove <= 0) {
        throw new Exception('Data tidak lengkap atau quantity tidak valid');
    }

    // Mulai transaction
    $pdo->beginTransaction();

    // Ambil data stock yang akan dipindahkan
    $stmt = $pdo->prepare("
        SELECT * FROM stock 
        WHERE id = :id
    ");
    $stmt->execute([':id' => $stockId]);
    $stockData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$stockData) {
        throw new Exception('Data stock tidak ditemukan');
    }

    // Validasi qty_allocated tidak bisa move ketika ada allocated
    if ($stockData['qty_allocated'] > 0) {
        throw new Exception('Item tidak bisa dipindah karena ada qty_allocated yang active');
    }

    // Validasi qty <= stock_balance
    if ($qtyToMove > $stockData['stock_balance']) {
        throw new Exception('Quantity melebihi stock balance yang tersedia (' . $stockData['stock_balance'] . ')');
    }

    // Validasi status stock bukan freeze
    if ($stockData['status_stock'] === 'Freeze') {
        throw new Exception('Stock sedang freeze, tidak bisa dipindah');
    }

    // Validasi locator baru ada dan di warehouse yang sama
    $stmt = $pdo->prepare("
        SELECT * FROM master_locator 
        WHERE locator = :locator 
        AND wh_name = :wh_name
    ");
    $stmt->execute([
        ':locator' => $newLocator,
        ':wh_name' => $stockData['wh_name']
    ]);
    $locatorData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$locatorData) {
        throw new Exception('Locator tujuan tidak valid atau tidak ada di warehouse yang sama');
    }

    // Cek apakah sudah ada stock dengan kombinasi yang sama di locator baru
    $stmt = $pdo->prepare("
        SELECT id, qty_inbound, qty_allocated, qty_out, stock_on_hand, stock_balance 
        FROM stock 
        WHERE item_code = :item_code 
        AND wh_name = :wh_name 
        AND project_name = :project_name 
        AND locator = :locator 
        AND packing_list = :packing_list
    ");
    $stmt->execute([
        ':item_code' => $stockData['item_code'],
        ':wh_name' => $stockData['wh_name'],
        ':project_name' => $stockData['project_name'],
        ':locator' => $newLocator,
        ':packing_list' => $stockData['packing_list']
    ]);
    $existingStock = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($qtyToMove == $stockData['stock_balance']) {
        // Full move: update locator jika tidak merge, atau merge full
        if ($existingStock) {
            // Merge full
            $stmt = $pdo->prepare("
                UPDATE stock SET
                    qty_inbound = qty_inbound + :qty_inbound,
                    qty_allocated = qty_allocated + :qty_allocated,
                    qty_out = qty_out + :qty_out,
                    stock_on_hand = stock_on_hand + :stock_on_hand,
                    stock_balance = stock_balance + :stock_balance,
                    last_updated = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            $stmt->execute([
                ':qty_inbound' => $stockData['qty_inbound'],
                ':qty_allocated' => $stockData['qty_allocated'],
                ':qty_out' => $stockData['qty_out'],
                ':stock_on_hand' => $stockData['stock_on_hand'],
                ':stock_balance' => $stockData['stock_balance'],
                ':id' => $existingStock['id']
            ]);

            // Hapus stock lama
            $stmt = $pdo->prepare("DELETE FROM stock WHERE id = :id");
            $stmt->execute([':id' => $stockId]);

            $finalStockId = $existingStock['id'];
            $actionType = 'MERGE';
        } else {
            // Update locator saja
            $stmt = $pdo->prepare("
                UPDATE stock SET
                    locator = :locator,
                    last_updated = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            $stmt->execute([
                ':locator' => $newLocator,
                ':id' => $stockId
            ]);

            $finalStockId = $stockId;
            $actionType = 'MOVE';
        }
    } else {
    // Partial move: kurangi dari original
    $newInbound = $stockData['qty_inbound'] - $qtyToMove; // Kurangi qty_inbound asli
    $newBalance = $stockData['stock_balance'] - $qtyToMove;
    $newOnHand = $stockData['stock_on_hand'] - $qtyToMove;
    $stmt = $pdo->prepare("
        UPDATE stock SET 
            qty_inbound = :new_inbound,
            stock_balance = :new_balance,
            stock_on_hand = :new_on_hand,
            last_updated = CURRENT_TIMESTAMP 
        WHERE id = :id
    ");
    $stmt->execute([
        ':new_inbound' => $newInbound,
        ':new_balance' => $newBalance,
        ':new_on_hand' => $newOnHand,
        ':id' => $stockId
    ]);

    if ($existingStock) {
        // Merge partial
        $mergedBalance = $existingStock['stock_balance'] + $qtyToMove;
        $mergedOnHand = $existingStock['stock_on_hand'] + $qtyToMove;
        $stmt = $pdo->prepare("
            UPDATE stock SET 
                stock_balance = :merged_balance,
                stock_on_hand = :merged_on_hand,
                last_updated = CURRENT_TIMESTAMP 
            WHERE id = :id
        ");
        $stmt->execute([
            ':merged_balance' => $mergedBalance,
            ':merged_on_hand' => $mergedOnHand,
            ':id' => $existingStock['id']
        ]);
        $finalStockId = $existingStock['id'];
        $actionType = 'MERGE';
    } else {
        // Insert new record partial
        $stmt = $pdo->prepare("
            INSERT INTO stock (
                item_code, item_description, qty_inbound, qty_allocated, qty_out,
                stock_balance, stock_on_hand, uom, volume, locator,
                packing_list, po_number, stock_type, supplier, Inbound_date,
                wh_name, project_name, status_stock, freeze_reason
            ) VALUES (
                :item_code, :item_description, :qty_inbound, 0, 0,
                :stock_balance, :stock_on_hand, :uom, :volume, :locator,
                :packing_list, :po_number, :stock_type, :supplier, CURRENT_TIMESTAMP,
                :wh_name, :project_name, 'Active', NULL
            )
        ");
        $stmt->execute([
            ':item_code' => $stockData['item_code'],
            ':item_description' => $stockData['item_description'],
            ':qty_inbound' => $qtyToMove, // Tetap set sesuai qty_to_move
            ':stock_balance' => $qtyToMove,
            ':stock_on_hand' => $qtyToMove,
            ':uom' => $stockData['uom'],
            ':volume' => $stockData['volume'],
            ':locator' => $newLocator,
            ':packing_list' => $stockData['packing_list'],
            ':po_number' => $stockData['po_number'],
            ':stock_type' => $stockData['stock_type'],
            ':supplier' => $stockData['supplier'],
            ':wh_name' => $stockData['wh_name'],
            ':project_name' => $stockData['project_name']
        ]);
        $finalStockId = $pdo->lastInsertId();
        $actionType = 'MOVE';
    }
}

    // Insert ke stock_movement_history untuk audit trail
    $stmt = $pdo->prepare("
        INSERT INTO stock_movement_history (
            stock_id, item_code, item_description, from_locator, to_locator,
            qty, wh_name, project_name, packing_list, reason, action_type, created_by
        ) VALUES (
            :stock_id, :item_code, :item_description, :from_locator, :to_locator,
            :qty, :wh_name, :project_name, :packing_list, :reason, :action_type, :created_by
        )
    ");
    $stmt->execute([
        ':stock_id' => $stockId,
        ':item_code' => $stockData['item_code'],
        ':item_description' => $stockData['item_description'],
        ':from_locator' => $stockData['locator'],
        ':to_locator' => $newLocator,
        ':qty' => $qtyToMove,
        ':wh_name' => $stockData['wh_name'],
        ':project_name' => $stockData['project_name'],
        ':packing_list' => $stockData['packing_list'],
        ':reason' => $reason,
        ':action_type' => $actionType,
        ':created_by' => $username
    ]);

    // Commit transaction
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Item berhasil dipindahkan ke locator baru',
        'data' => [
            'new_locator' => $newLocator,
            'stock_id' => $finalStockId,
            'move_type' => $actionType === 'MERGE' ? 'merged' : ($qtyToMove == $stockData['stock_balance'] ? 'full_move' : 'split')
        ]
    ]);

} catch (Exception $e) {
    // Rollback jika ada error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>