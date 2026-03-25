<?php
require_once __DIR__ . '/../php/config.php'; // Koneksi database
require_once __DIR__ . '/../php/send_email.php'; // Fungsi sendEmailNotification
require_once __DIR__ . '/../vendor/autoload.php'; // PHPMailer

session_start();
date_default_timezone_set('Asia/Jakarta');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

// Periksa apakah pengguna sudah login
if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Periksa apakah sesi masih aktif
$timeout_duration = 1200; // 20 menit
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    session_unset();
    session_destroy();
    echo json_encode(['success' => false, 'message' => 'Session expired']);
    exit();
}

// Perbarui waktu aktivitas terakhir
$_SESSION['last_activity'] = time();

try {
    // ============================
    // 1️⃣ Ambil data dari POST
    // ============================
    $request_number = $_POST['request_number'] ?? '';
    $reject_reason = $_POST['reject_reason'] ?? '';
    $rejected_by = $_SESSION['username'] ?? '';
    $rejected_by_name = $_SESSION['nama'] ?? $rejected_by; // Gunakan nama lengkap jika ada

    if (empty($request_number) || empty($reject_reason)) {
        throw new Exception('Request number and reject reason are required.');
    }

    // ============================
    // 2️⃣ Ambil data permintaan dari database
    // ============================
    $stmt = $pdo->prepare("
        SELECT request_number, request_stock_type, request_by, wh_name, project_name,
               item_code, item_description, qty, uom, remarks
        FROM request_materials
        WHERE request_number = ?
        LIMIT 1
    ");
    $stmt->execute([$request_number]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        throw new Exception('Request not found.');
    }

    // Ambil semua item untuk request_number yang sama
    $stmt_items = $pdo->prepare("
        SELECT item_code, item_description, qty, uom, remarks
        FROM request_materials
        WHERE request_number = ?
    ");
    $stmt_items->execute([$request_number]);
    $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    // ============================
    // 3️⃣ Update status permintaan
    // ============================
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("
        UPDATE request_materials 
        SET 
            status = 'Rejected',
            rejected_by = ?,
            rejected_date = NOW(),
            reject_reason = ?,
            approved_by = NULL,
            approved_date = NULL
        WHERE request_number = ?
    ");
    $stmt->execute([$rejected_by_name, $reject_reason, $request_number]);

    if ($stmt->rowCount() === 0) {
        throw new Exception('Failed to update request status.');
    }

    $pdo->commit();

    // ============================
    // 4️⃣ Kirim Email Notifikasi
    // ============================
    $emailBody = buildMaterialRequestEmail(
        $request['request_number'],
        $request['request_by'],
        $request['wh_name'],
        $request['project_name'],
        $request['request_stock_type'],
        $items,
        'Rejected',
        $rejected_by_name,
        $reject_reason
    );
    
    $status = '[Rejected]';

    $emailSent = sendEmailNotification(
        "{$status} - {$request['request_number']} - {$request['request_stock_type']}",
        $emailBody
    );

    if (!$emailSent) {
        error_log("Failed to send email notification for rejected request: {$request['request_number']}");
    }

    // ============================
    // 5️⃣ Return Response
    // ============================
    echo json_encode([
        'success' => true,
        'message' => 'Request rejected successfully',
        'email_sent' => $emailSent
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Reject Request Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Build HTML email body untuk material request - Simple Clean Design
 */
function buildMaterialRequestEmail($requestNumber, $requestBy, $whName, $projectName, $stockType, $items, $status = 'Waiting Approval', $actionBy = '', $rejectReason = '')
{
    $statusColor = $status === 'Approved' ? '#32CD32' : ($status === 'Rejected' ? '#dc3545' : '#FF6B00');
    $actionByText = $status === 'Approved' ? "<tr><td style='padding: 6px 10px; border: 1px solid #ddd; font-weight: bold;'>Approved By</td><td style='padding: 6px 10px; border: 1px solid #ddd;'>{$actionBy}</td></tr>" : '';
    if ($status === 'Rejected') {
        $actionByText = "<tr><td style='padding: 6px 10px; border: 1px solid #ddd; font-weight: bold;'>Rejected By</td><td style='padding: 6px 10px; border: 1px solid #ddd;'>{$actionBy}</td></tr>";
        $actionByText .= "<tr style='background-color: #f2f2f2;'><td style='padding: 6px 10px; border: 1px solid #ddd; font-weight: bold;'>Reject Reason</td><td style='padding: 6px 10px; border: 1px solid #ddd;'>{$rejectReason}</td></tr>";
    }

    $html = "
    <style>
        .items-table th { background: #4472C4; color: white; padding: 6px 8px; text-align: left; font-weight: normal; border: 1px solid #4472C4; font-size: 12px; }
        .items-table td { padding: 4px 8px; border: 1px solid #ddd; font-size: 12px; }
        .col-no { width: 30px; text-align: center; }
        .col-code { width: 100px; }
        .col-desc { width: 150px; }
        .col-qty { width: 50px; text-align: center; }
        .col-uom { width: 50px; text-align: center; }
        .col-remark { width: 200px; text-align: left; }
    </style>
    <p style='font-size: 13px; margin: 5px 0;'><strong>Dear All</strong></p>
    <p style='font-size: 13px; margin: 5px 0;'>{$actionBy} <strong style='color: {$statusColor};'>{$status}</strong> your Request <strong>{$requestNumber}</strong> Below</p>
    <table style='border-collapse: collapse; width: 100%; max-width: 500px; font-size: 13px;'>
        <tr style='background-color: #f2f2f2;'>
            <td style='padding: 6px 10px; border: 1px solid #ddd; font-weight: bold; width: 30%;'>Request Number</td>
            <td style='padding: 6px 10px; border: 1px solid #ddd;'>{$requestNumber}</td>
        </tr>
        <tr>
            <td style='padding: 6px 10px; border: 1px solid #ddd; font-weight: bold;'>Request By</td>
            <td style='padding: 6px 10px; border: 1px solid #ddd;'>{$requestBy}</td>
        </tr>
        <tr style='background-color: #f2f2f2;'>
            <td style='padding: 6px 10px; border: 1px solid #ddd; font-weight: bold;'>Warehouse</td>
            <td style='padding: 6px 10px; border: 1px solid #ddd;'>{$whName}</td>
        </tr>
        
        <tr style='background-color: #f2f2f2;'>
            <td style='padding: 6px 10px; border: 1px solid #ddd; font-weight: bold;'>Stock Type</td>
            <td style='padding: 6px 10px; border: 1px solid #ddd;'>{$stockType}</td>
        </tr>
        <tr>
            <td style='padding: 6px 10px; border: 1px solid #ddd; font-weight: bold;'>Status</td>
            <td style='padding: 6px 10px; border: 1px solid #ddd;'><strong style='color: {$statusColor};'>{$status}</strong></td>
        </tr>
        {$actionByText}
    </table>
    <h3 style='font-size: 16px; margin: 15px 0 10px;'>Requested Items</h3>
    <table class='items-table' style='border-collapse: collapse; width: 100%;'>
        <tr>
            <th class='col-no'>No</th>
            <th class='col-code'>Item Code</th>
            <th class='col-desc'>Description</th>
            <th class='col-qty'>Qty</th>
            <th class='col-uom'>UOM</th>
            
        </tr>";
    $no = 1;
    foreach ($items as $item) {
        $remarks = !empty($item['remarks']) ? htmlspecialchars($item['remarks']) : '';
        $html .= "
        <tr>
            <td class='col-no'>{$no}</td>
            <td class='col-code'>" . htmlspecialchars($item['item_code']) . "</td>
            <td class='col-desc'>" . htmlspecialchars($item['item_description']) . "</td>
            <td class='col-qty'>" . htmlspecialchars($item['qty']) . "</td>
            <td class='col-uom'>" . htmlspecialchars($item['uom']) . "</td>
           
        </tr>";
        $no++;
    }
    $html .= "
    </table>
    <p style='font-size: 12px; color: red; margin-top: 15px; font-style: italic;'>
        Please do not reply to this email as it was generated automatically.
    </p>";
    return $html;
}
?>