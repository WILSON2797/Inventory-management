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
    $approved_note = $_POST['approved_note'] ?? '';
    $approved_by = $_SESSION['username'] ?? '';
    $approved_by_name = $_SESSION['nama'] ?? $approved_by; // Gunakan nama lengkap jika ada

    if (empty($request_number)) {
        throw new Exception('Request number is required.');
    }
    
    if (empty($approved_note)) {
    throw new Exception('Approval note is required.');
    }

    // ============================
    // 2️⃣ Ambil data permintaan dari database
    // ============================
    $stmt = $pdo->prepare("
        SELECT request_number, request_stock_type, request_by, wh_name, project_name,
               item_code, item_description, qty, uom, remarks, status
        FROM request_materials
        WHERE request_number = ?
        LIMIT 1
    ");
    $stmt->execute([$request_number]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        error_log("Approve Request - Request number $request_number not found in database");
        throw new Exception('Request not found.');
    }

    // Validasi status
    if ($request['status'] !== 'Waiting Approval') {
        error_log("Approve Request - Request $request_number has invalid status: {$request['status']}");
        throw new Exception('Request cannot be approved because it is already ' . $request['status'] . '.');
    }

    // Ambil semua item untuk request_number yang sama
    $stmt_items = $pdo->prepare("
        SELECT item_code, item_description, qty, uom, remarks
        FROM request_materials
        WHERE request_number = ?
    ");
    $stmt_items->execute([$request_number]);
    $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    if (empty($items)) {
        error_log("Approve Request - No items found for request_number: $request_number");
        throw new Exception('No items found for this request.');
    }

    // ============================
    // 3️⃣ Update status permintaan
    // ============================
    $pdo->beginTransaction();
    
    $stmt_update = $pdo->prepare("
        UPDATE request_materials 
        SET 
            status = 'Approved',
            approved_by = ?,
            approved_date = NOW(),
            approved_note = ?,
            rejected_by = NULL,
            rejected_date = NULL,
            reject_reason = NULL
        WHERE request_number = ?
    ");
    $stmt_update->execute([$approved_by_name, $approved_note, $request_number]);

    if ($stmt_update->rowCount() === 0) {
        error_log("Approve Request - Failed to update request_number: $request_number");
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
        'Approved',
        $approved_by_name,
         '', 
        $approved_note
    );
    
    $status = '[Approved]';

    $emailSent = sendEmailNotification(
        "{$status} - {$request['request_number']} - {$request['request_stock_type']}",
        $emailBody
    );

    if (!$emailSent) {
        error_log("Failed to send email notification for approved request: {$request['request_number']}");
    }

    // ============================
    // 5️⃣ Return Response
    // ============================
    echo json_encode([
        'success' => true,
        'message' => 'Request approved success',
        'email_sent' => $emailSent
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Approve Request Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Build HTML email body untuk material request - Simple Clean Design
 */
function buildMaterialRequestEmail($requestNumber, $requestBy, $whName, $projectName, $stockType, $items, $status = 'Waiting Approval', $actionBy = '', $rejectReason = '',$approvedNote = '')
{
    $statusColor = $status === 'Approved' ? '#32CD32' : ($status === 'Rejected' ? '#dc3545' : '#FF6B00');
    $actionByText = '';
    
    if ($status === 'Approved') {
        $actionByText = "<tr><td style='padding: 6px 10px; border: 1px solid #ddd; font-weight: bold;'>Approved By</td><td style='padding: 6px 10px; border: 1px solid #ddd;'>{$actionBy}</td></tr>";
        if (!empty($approvedNote)) { // Tampilkan approved_note jika ada
            $actionByText .= "<tr style='background-color: #f2f2f2;'><td style='padding: 6px 10px; border: 1px solid #ddd; font-weight: bold;'>Approval Note</td><td style='padding: 6px 10px; border: 1px solid #ddd;'>{$approvedNote}</td></tr>";
        }
    } elseif ($status === 'Rejected') {
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
    <p style='font-size: 13px; margin: 5px 0;'><strong>Dear All.</strong></p>
    <p style='font-size: 13px; margin: 5px 0;'>{$actionBy} has <strong style='color: {$statusColor};'>{$status}</strong> the following <strong>{$requestNumber}</strong> as detailed below.</p>
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