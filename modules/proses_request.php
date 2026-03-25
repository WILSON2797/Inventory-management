<?php
require_once __DIR__ . '/../php/config.php';
require_once __DIR__ . '/../php/send_email.php'; // Import fungsi email yang sudah ada
require_once __DIR__ . '/../vendor/autoload.php';

session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

try {
    // ============================
    // 1️⃣ Ambil data dari form
    // ============================
    $request_number = $_POST['request_number'] ?? '';
    $request_stock_type = $_POST['stock_type'] ?? '';
    $request_by = $_SESSION['nama'] ?? '';
    $wh_name = $_SESSION['wh_name'] ?? '';
    $project_name = $_SESSION['project_name'] ?? '';
    $items = $_POST['items'] ?? [];

    if (empty($request_number) || empty($request_stock_type) || empty($request_by) || empty($wh_name) || empty($items)) {
        throw new Exception('All fields are required.');
    }

    // ============================
    // 2️⃣ Validasi item
    // ============================
    foreach ($items as $item) {
        if (empty($item['item_code']) || empty($item['qty']) || $item['qty'] <= 0) {
            throw new Exception('Invalid item data.');
        }
    }

    // ============================
    // 3️⃣ Insert ke database
    // ============================
    $pdo->beginTransaction();

    foreach ($items as $item) {
        $stmt = $pdo->prepare("
            INSERT INTO request_materials (
                request_number, request_stock_type, request_by, wh_name, project_name,
                item_code, item_description, qty, uom, remarks, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Waiting Approval')
        ");
        $stmt->execute([
            $request_number,
            $request_stock_type,
            $request_by,
            $wh_name,
            $project_name,
            $item['item_code'],
            $item['item_description'],
            $item['qty'],
            $item['uom'],
            $item['remarks']
        ]);
    }

    $pdo->commit();

    // ============================
    // 4️⃣ Kirim Email Notifikasi
    // ============================
    
    // Build email body
    $emailBody = buildMaterialRequestEmail($request_number, $request_by, $wh_name, $project_name, $request_stock_type, $items);
    
    // Kirim email menggunakan fungsi yang sudah ada
    $emailSent = sendEmailNotification(
        "[Request Material] - {$request_number} - {$request_stock_type}",
        $emailBody
    );

    // Log jika email gagal (tapi tetap success untuk user)
    if (!$emailSent) {
        error_log("Failed to send email notification for request: {$request_number}");
    }

    // ============================
    // 5️⃣ Return Response
    // ============================
    echo json_encode([
        'success' => true, 
        'request_number' => $request_number,
        'email_sent' => $emailSent // Optional: informasikan status email
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Material Request Error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}

/**
 * Build HTML email body untuk material request - Simple Clean Design
 */
function buildMaterialRequestEmail($requestNumber, $requestBy, $whName, $projectName, $stockType, $items)
{
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
    <p style='font-size: 13px; margin: 5px 0;'><strong>{$requestBy}</strong> has submitted the request <strong>{$requestNumber}</strong> The details are provided below.</p>
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
        <tr>
            <td style='padding: 6px 10px; border: 1px solid #ddd; font-weight: bold;'>Project</td>
            <td style='padding: 6px 10px; border: 1px solid #ddd;'>{$projectName}</td>
        </tr>
        <tr style='background-color: #f2f2f2;'>
            <td style='padding: 6px 10px; border: 1px solid #ddd; font-weight: bold;'>Stock Type</td>
            <td style='padding: 6px 10px; border: 1px solid #ddd;'>{$stockType}</td>
        </tr>
        <tr>
            <td style='padding: 6px 10px; border: 1px solid #ddd; font-weight: bold;'>Status</td>
            <td style='padding: 6px 10px; border: 1px solid #ddd;'><strong style='color: #FF6B00;'>Waiting Approval</strong></td>
        </tr>
    </table>
    <h3 style='font-size: 16px; margin: 15px 0 10px;'>Requested Items</h3>
    <table class='items-table' style='border-collapse: collapse; width: 100%;'>
        <tr>
            <th class='col-no'>No</th>
            <th class='col-code'>Item Code</th>
            <th class='col-desc'>Description</th>
            <th class='col-qty'>Qty</th>
            <th class='col-uom'>UOM</th>
            <th class='col-remark'>Remarks</th>
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
            <td class='col-remark'>{$remarks}</td>
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