<?php
include '../php/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $item_description = trim($_POST['item_description']);
    $volume = $_POST['volume'];
    $uom = trim($_POST['uom']);
    $project = trim($_POST['project']);

    try {
        // Validasi input
        if (empty($item_description) || empty($uom) || empty($project) || !is_numeric($volume)) {
            throw new Exception("Semua field harus diisi dengan benar!");
        }

        if ($volume < 0) {
            throw new Exception("Volume tidak boleh negatif!");
        }

        // Ambil item_code untuk pengecekan duplikasi
        $check_code = $conn->prepare("SELECT item_code FROM master_sku WHERE id = ?");
        $check_code->bind_param("i", $id);
        $check_code->execute();
        $result = $check_code->get_result();
        
        if ($result->num_rows == 0) {
            throw new Exception("SKU tidak ditemukan!");
        }
        
        $item_code = $result->fetch_assoc()['item_code'];
        $check_code->close();

        // Cek apakah kombinasi item_code + project sudah ada (selain ID yang sedang diedit)
        $check_duplicate = $conn->prepare("SELECT COUNT(*) as count FROM master_sku WHERE item_code = ? AND project = ? AND id != ?");
        $check_duplicate->bind_param("ssi", $item_code, $project, $id);
        $check_duplicate->execute();
        $dup_result = $check_duplicate->get_result();
        $dup_count = $dup_result->fetch_assoc()['count'];
        $check_duplicate->close();

        if ($dup_count > 0) {
            throw new Exception("Kombinasi Item Code '$item_code' dan Project '$project' sudah terdaftar!");
        }

        // Mulai transaction untuk memastikan semua update berhasil atau gagal semua
        $conn->begin_transaction();

        try {
            // 1. Update master_sku
            $sql_master = "UPDATE master_sku SET 
                          item_description = ?, 
                          volume = ?, 
                          uom = ?,
                          project = ?,
                          updated_at = NOW()
                          WHERE id = ?";
            
            $stmt_master = $conn->prepare($sql_master);
            $stmt_master->bind_param("sdssi", $item_description, $volume, $uom, $project, $id);
            
            if (!$stmt_master->execute()) {
                throw new Exception("Gagal mengupdate master SKU: " . $stmt_master->error);
            }
            $stmt_master->close();

            // 2. Update tabel inbound (volume dikalikan qty)
            $sql_inbound = "UPDATE inbound SET 
                           item_description = ?, 
                           volume = ? * qty, 
                           uom = ?
                           WHERE item_code = ?";
            
            $stmt_inbound = $conn->prepare($sql_inbound);
            $stmt_inbound->bind_param("sdss", $item_description, $volume, $uom, $item_code);
            $stmt_inbound->execute();
            $inbound_affected = $stmt_inbound->affected_rows;
            $stmt_inbound->close();

            // 3. Update tabel allocated (volume dikalikan qty_picking)
            $sql_allocated = "UPDATE allocated SET 
                             item_description = ?, 
                             volume = ? * qty_picking, 
                             uom = ?
                             WHERE item_code = ?";
            
            $stmt_allocated = $conn->prepare($sql_allocated);
            $stmt_allocated->bind_param("sdss", $item_description, $volume, $uom, $item_code);
            $stmt_allocated->execute();
            $allocated_affected = $stmt_allocated->affected_rows;
            $stmt_allocated->close();

            // 4. Update tabel outbound (volume dikalikan stock_on_hand)
            $sql_outbound = "UPDATE outbound SET 
                            item_description = ?, 
                            volume = ? * qty, 
                            uom = ?
                            WHERE item_code = ?";
            
            $stmt_outbound = $conn->prepare($sql_outbound);
            $stmt_outbound->bind_param("sdss", $item_description, $volume, $uom, $item_code);
            $stmt_outbound->execute();
            $outbound_affected = $stmt_outbound->affected_rows;
            $stmt_outbound->close();

            // 5. Update tabel stock (volume dikalikan qty)
            // Sesuaikan dengan kolom qty di tabel stock Anda (misal: qty, quantity, stock_qty, dll)
            $sql_stock = "UPDATE stock SET 
                         item_description = ?, 
                         volume = ? * stock_on_hand, 
                         uom = ?
                         WHERE item_code = ?";
            
            $stmt_stock = $conn->prepare($sql_stock);
            $stmt_stock->bind_param("sdss", $item_description, $volume, $uom, $item_code);
            $stmt_stock->execute();
            $stock_affected = $stmt_stock->affected_rows;
            $stmt_stock->close();

            // Commit transaction jika semua berhasil
            $conn->commit();

            echo json_encode([
                'status' => 'success',
                'message' => 'SKU dan semua data terkait berhasil diupdate!',
                'details' => [
                    'item_code' => $item_code,
                    'inbound_updated' => $inbound_affected,
                    'allocated_updated' => $allocated_affected,
                    'outbound_updated' => $outbound_affected,
                    'stock_updated' => $stock_affected
                ]
            ]);

        } catch (Exception $e) {
            // Rollback jika ada error
            $conn->rollback();
            throw $e;
        }

    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method Not Allowed'
    ]);
}

$conn->close();
?>