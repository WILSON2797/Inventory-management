<?php
// daily_stock_email.php - Versi untuk cronjob tanpa filter role-based, dengan tiga sheet: Inbound_Details, Stock_Details, Outbound_Details

// Debug awal sebelum apapun, biar pasti ada output
echo "Script mulai dijalankan: " . date('Y-m-d H:i:s') . "\n";
file_put_contents(__DIR__ . '/logs/debug_start.log', "Debug awal: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

// Set error reporting penuh
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/error.log');

// Cek dan buat folder logs/temp kalau belum ada
$log_dir = __DIR__ . '/logs';
$temp_dir = __DIR__ . '/temp';
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
    echo "Folder logs dibuat.\n";
}
if (!is_dir($temp_dir)) {
    mkdir($temp_dir, 0755, true);
    echo "Folder temp dibuat.\n";
}

// Mulai output buffering
ob_start();

// Require dependensi
require __DIR__ . '/../vendor/autoload.php';
include __DIR__ . '/../php/config.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Load config email
$email_config = include __DIR__ . '/email_config.php';
if (!$email_config || !is_array($email_config)) {
    $error = "Config email ga valid!\n";
    echo $error;
    file_put_contents(__DIR__ . '/logs/daily_stock_email.log', $error, FILE_APPEND);
    exit(1);
}

// Set timezone
date_default_timezone_set($email_config['content']['timezone'] ?? 'Asia/Jakarta');

// Log function
function writeLog($message) {
    $log_file = __DIR__ . '/logs/daily_stock_email.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] $message\n";
    file_put_contents($log_file, $log_message, FILE_APPEND | LOCK_EX);
    echo $log_message;
}

try {
    writeLog("Mulai proses daily stock report");

    // Buat spreadsheet baru
    $spreadsheet = new Spreadsheet();

    // Header style untuk semua sheet
    $headerStyle = [
        'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF00008B']],
    ];

    // Data style untuk semua sheet
    $dataStyle = [
        'font' => ['name' => 'Arial', 'size' => 9, 'color' => ['argb' => 'FF000000']],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    ];

    // 1. Sheet Stock_Details
    writeLog("Generate Stock_Details sheet...");
    $sheetStock = $spreadsheet->getActiveSheet()->setTitle('Stock_Details');
    $dataStock = [];
    $sort_by = $email_config['report']['sort_by'] ?? 'Inbound_date';
    $sort_order = $email_config['report']['sort_order'] ?? 'ASC';
    $queryStock = "SELECT * FROM stock ORDER BY $sort_by $sort_order";
    $resultStock = $conn->query($queryStock);
    if (!$resultStock) {
        throw new Exception("Query DB stock gagal: " . $conn->error);
    }

    writeLog("Ditemukan " . $resultStock->num_rows . " record stock");

    while ($row = $resultStock->fetch_assoc()) {
        $dataStock[] = [
            'Inbound_date' => $row['Inbound_date'] ?? '-',
            'po_number' => $row['po_number'] ?? '-',
            'supplier' => $row['supplier'] ?? '-',
            'item_code' => $row['item_code'] ?? '-',
            'item_description' => $row['item_description'] ?? '-',
            'qty_inbound' => (int)($row['qty_inbound'] ?? 0),
            'qty_allocated' => (int)($row['qty_allocated'] ?? 0),
            'qty_out' => (int)($row['qty_out'] ?? 0),
            'stock_onhand' => (int)($row['stock_on_hand'] ?? 0),
            'stock_balance' => $row['stock_balance'] ?? '-',
            'uom' => $row['uom'] ?? '-',
            'locator' => $row['locator'] ?? '-',
            'packing_list' => $row['packing_list'] ?? '-',
            'wh_name' => $row['wh_name'] ?? '-',
            'stock_type' => $row['stock_type'] ?? '-',
            'last_updated' => $row['last_updated'] ?? '-'
        ];
    }

    // Set header untuk Stock_Details
    $headersStock = [
        'A1' => 'No', 'B1' => 'Inbound Date', 'C1' => 'PO Number', 'D1' => 'Supplier',
        'E1' => 'Item Code', 'F1' => 'Description', 'G1' => 'Qty Inbound', 'H1' => 'Qty Allocated',
        'I1' => 'Qty Outbound', 'J1' => 'Stock Onhand', 'K1' => 'Stock Balance', 'L1' => 'UOM',
        'M1' => 'Locator', 'N1' => 'Packing List', 'O1' => 'Warehouse Name', 'P1' => 'Stock Type',
        'Q1' => 'Last Update'
    ];
    foreach ($headersStock as $cell => $value) {
        $sheetStock->setCellValue($cell, $value);
    }
    $sheetStock->getStyle('A1:Q1')->applyFromArray($headerStyle);

    // Isi data Stock_Details
    $rowNumber = 2;
    foreach ($dataStock as $index => $row) {
        $sheetStock->setCellValue('A' . $rowNumber, $index + 1);
        $sheetStock->setCellValue('B' . $rowNumber, $row['Inbound_date']);
        $sheetStock->setCellValue('C' . $rowNumber, $row['po_number']);
        $sheetStock->setCellValue('D' . $rowNumber, $row['supplier']);
        $sheetStock->setCellValue('E' . $rowNumber, $row['item_code']);
        $sheetStock->setCellValue('F' . $rowNumber, $row['item_description']);
        $sheetStock->setCellValue('G' . $rowNumber, $row['qty_inbound']);
        $sheetStock->setCellValue('H' . $rowNumber, $row['qty_allocated']);
        $sheetStock->setCellValue('I' . $rowNumber, $row['qty_out']);
        $sheetStock->setCellValue('J' . $rowNumber, $row['stock_onhand']);
        $sheetStock->setCellValue('K' . $rowNumber, $row['stock_balance']);
        $sheetStock->setCellValue('L' . $rowNumber, $row['uom']);
        $sheetStock->setCellValue('M' . $rowNumber, $row['locator']);
        $sheetStock->setCellValue('N' . $rowNumber, $row['packing_list']);
        $sheetStock->setCellValue('O' . $rowNumber, $row['wh_name']);
        $sheetStock->setCellValue('P' . $rowNumber, $row['stock_type']);
        $sheetStock->setCellValue('Q' . $rowNumber, $row['last_updated']);

        if (!empty($row['Inbound_date']) && $row['Inbound_date'] !== '-') {
            $sheetStock->getStyle('B' . $rowNumber)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);
        }
        $rowNumber++;
    }

    $lastRowStock = $rowNumber - 1;
    if ($lastRowStock > 1) {
        $sheetStock->getStyle('A2:Q' . $lastRowStock)->applyFromArray($dataStyle);
    }
    $sheetStock->getStyle('D:Q')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $sheetStock->getStyle('E:F')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

    $sheetStock->freezePane('C2');
    foreach (range('A', 'Q') as $column) {
        $sheetStock->getColumnDimension($column)->setAutoSize(true);
    }

    // 2. Sheet Inbound_Details
    writeLog("Generate Inbound_Details sheet...");
    $sheetInbound = $spreadsheet->createSheet()->setTitle('Inbound_Details');
    $dataInbound = [];
    $queryInbound = "SELECT * FROM inbound ORDER BY created_date ASC";
    $resultInbound = $conn->query($queryInbound);
    if (!$resultInbound) {
        throw new Exception("Query DB inbound gagal: " . $conn->error);
    }

    writeLog("Ditemukan " . $resultInbound->num_rows . " record inbound");

    while ($row = $resultInbound->fetch_assoc()) {
        $dataInbound[] = [
            'created_date' => $row['created_date'] ?? '-',
            'transaction_sequence' => $row['transaction_sequence'] ?? '-',
            'po_number' => $row['po_number'] ?? '-',
            'supplier' => $row['supplier'] ?? '-',
            'reference_number' => $row['reference_number'] ?? '-',
            'packing_list' => $row['packing_list'] ?? '-',
            'item_code' => $row['item_code'] ?? '-',
            'item_description' => $row['item_description'] ?? '-',
            'qty' => $row['qty'] ?? 0,
            'uom' => $row['uom'] ?? '-',
            'locator' => $row['locator'] ?? '-',
            'created_by' => $row['created_by'] ?? '-',
            'wh_name' => $row['wh_name'] ?? '-',
            'stock_type' => $row['stock_type'] ?? '-',
        ];
    }

    // Set header untuk Inbound_Details
    $headersInbound = [
        'A1' => 'No', 'B1' => 'Inbound Date', 'C1' => 'Transaction Number', 'D1' => 'PO Number',
        'E1' => 'Supplier', 'F1' => 'Reference No', 'G1' => 'Packing List', 'H1' => 'Item Code',
        'I1' => 'Description', 'J1' => 'Qty Inbound', 'K1' => 'UOM', 'L1' => 'Locator',
        'M1' => 'Warehouse Name', 'N1' => 'Stock Type', 'O1' => 'Submit By'
    ];
    foreach ($headersInbound as $cell => $value) {
        $sheetInbound->setCellValue($cell, $value);
    }
    $sheetInbound->getStyle('A1:O1')->applyFromArray($headerStyle);

    // Isi data Inbound_Details
    $rowNumber = 2;
    foreach ($dataInbound as $index => $row) {
        $sheetInbound->setCellValue('A' . $rowNumber, $index + 1);
        $sheetInbound->setCellValue('B' . $rowNumber, $row['created_date']);
        $sheetInbound->setCellValue('C' . $rowNumber, $row['transaction_sequence']);
        $sheetInbound->setCellValue('D' . $rowNumber, $row['po_number']);
        $sheetInbound->setCellValue('E' . $rowNumber, $row['supplier']);
        $sheetInbound->setCellValue('F' . $rowNumber, $row['reference_number']);
        $sheetInbound->setCellValue('G' . $rowNumber, $row['packing_list']);
        $sheetInbound->setCellValue('H' . $rowNumber, $row['item_code']);
        $sheetInbound->setCellValue('I' . $rowNumber, $row['item_description']);
        $sheetInbound->setCellValue('J' . $rowNumber, $row['qty']);
        $sheetInbound->setCellValue('K' . $rowNumber, $row['uom']);
        $sheetInbound->setCellValue('L' . $rowNumber, $row['locator']);
        $sheetInbound->setCellValue('M' . $rowNumber, $row['wh_name']);
        $sheetInbound->setCellValue('N' . $rowNumber, $row['stock_type']);
        $sheetInbound->setCellValue('O' . $rowNumber, $row['created_by']);

        if (!empty($row['created_date']) && $row['created_date'] !== '-') {
            $sheetInbound->getStyle('B' . $rowNumber)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);
        }
        $rowNumber++;
    }

    $lastRowInbound = $rowNumber - 1;
    if ($lastRowInbound > 1) {
        $sheetInbound->getStyle('A2:O' . $lastRowInbound)->applyFromArray($dataStyle);
    }
    $sheetInbound->getStyle('D:O')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $sheetInbound->getStyle('H:I')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);


    $sheetInbound->freezePane('C2');
    foreach (range('A', 'O') as $column) {
        $sheetInbound->getColumnDimension($column)->setAutoSize(true);
    }

    // 3. Sheet Outbound_Details
    writeLog("Generate Outbound_Details sheet...");
    $sheetOutbound = $spreadsheet->createSheet()->setTitle('Outbound_Details');
    $dataOutbound = [];
    $queryOutbound = "SELECT * FROM outbound ORDER BY created_date ASC";
    $resultOutbound = $conn->query($queryOutbound);
    if (!$resultOutbound) {
        throw new Exception("Query DB outbound gagal: " . $conn->error);
    }

    writeLog("Ditemukan " . $resultOutbound->num_rows . " record outbound");

    while ($row = $resultOutbound->fetch_assoc()) {
        $dataOutbound[] = [
            'transaction_id' => $row['transaction_id'] ?? '-',
            'order_number' => $row['order_number'] ?? '-',
            'customer' => $row['customer'] ?? '-',
            'lottable1' => $row['lottable1'] ?? '-',
            'lottable2' => $row['lottable2'] ?? '-',
            'lottable3' => $row['lottable3'] ?? '-',
            'supplier' => $row['supplier'] ?? '-',
            'item_code' => $row['item_code'] ?? '-',
            'item_description' => $row['item_description'] ?? '-',
            'qty' => $row['qty'] ?? 0,
            'uom' => $row['uom'] ?? '-',
            'locator' => $row['locator'] ?? '-',
            'packing_list' => $row['packing_list'] ?? '-',
            'created_date' => $row['created_date'] ?? '-',
            'created_by' => $row['created_by'] ?? '-',
            'wh_name' => $row['wh_name'] ?? '-',
        ];
    }

    // Set header untuk Outbound_Details
    $headersOutbound = [
        'A1' => 'No', 'B1' => 'Outbound Date', 'C1' => 'Transaction Number', 'D1' => 'Order Number',
        'E1' => 'Customer', 'F1' => 'Destination', 'G1' => 'Item Code', 'H1' => 'Description',
        'I1' => 'Qty Outbound', 'J1' => 'UOM', 'K1' => 'Locator', 'L1' => 'Packing List',
        'M1' => 'Warehouse Name', 'N1' => 'Lottable1', 'O1' => 'Lottable2', 'P1' => 'Submit By'
    ];
    foreach ($headersOutbound as $cell => $value) {
        $sheetOutbound->setCellValue($cell, $value);
    }
    $sheetOutbound->getStyle('A1:P1')->applyFromArray($headerStyle);

    // Isi data Outbound_Details
    $rowNumber = 2;
    foreach ($dataOutbound as $index => $row) {
        $sheetOutbound->setCellValue('A' . $rowNumber, $index + 1);
        $sheetOutbound->setCellValue('B' . $rowNumber, $row['created_date']);
        $sheetOutbound->setCellValue('C' . $rowNumber, $row['transaction_id']);
        $sheetOutbound->setCellValue('D' . $rowNumber, $row['order_number']);
        $sheetOutbound->setCellValue('E' . $rowNumber, $row['customer']);
        $sheetOutbound->setCellValue('F' . $rowNumber, $row['lottable3']);
        $sheetOutbound->setCellValue('G' . $rowNumber, $row['item_code']);
        $sheetOutbound->setCellValue('H' . $rowNumber, $row['item_description']);
        $sheetOutbound->setCellValue('I' . $rowNumber, $row['qty']);
        $sheetOutbound->setCellValue('J' . $rowNumber, $row['uom']);
        $sheetOutbound->setCellValue('K' . $rowNumber, $row['locator']);
        $sheetOutbound->setCellValue('L' . $rowNumber, $row['packing_list']);
        $sheetOutbound->setCellValue('M' . $rowNumber, $row['wh_name']);
        $sheetOutbound->setCellValue('N' . $rowNumber, $row['lottable1']);
        $sheetOutbound->setCellValue('O' . $rowNumber, $row['lottable2']);
        $sheetOutbound->setCellValue('P' . $rowNumber, $row['created_by']);
        if (!empty($row['created_date']) && $row['created_date'] !== '-') {
            $sheetOutbound->getStyle('B' . $rowNumber)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);
        }
        $rowNumber++;
    }

    $lastRowOutbound = $rowNumber - 1;
    if ($lastRowOutbound > 1) {
        $sheetOutbound->getStyle('A2:P' . $lastRowOutbound)->applyFromArray($dataStyle);
    }
    $sheetOutbound->getStyle('D:P')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $sheetOutbound->getStyle('F:H')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

    $sheetOutbound->freezePane('C2');
    foreach (range('A', 'P') as $column) {
        $sheetOutbound->getColumnDimension($column)->setAutoSize(true);
    }

    // Save Excel
    $filename = 'Daily_Stock_Report_' . date('Ymd_His') . '.xlsx';
    $temp_file = $temp_dir . '/' . $filename;
    $writer = new Xlsx($spreadsheet);
    $writer->save($temp_file);

    // Cek size file
    $file_size_mb = filesize($temp_file) / (1024 * 1024);
    if ($file_size_mb > $email_config['files']['max_file_size_mb']) {
        throw new Exception("File Excel terlalu besar ($file_size_mb MB)");
    }
    writeLog("Excel dibuat: $filename ($file_size_mb MB)");

    // 4. Kirim Email
    writeLog("Kirim email...");
    $mail = new PHPMailer(true);
    if ($email_config['advanced']['debug_mode']) {
        $mail->SMTPDebug = SMTP::DEBUG_SERVER;
    }
    $mail->isSMTP();
    $mail->Host = $email_config['smtp']['host'];
    $mail->SMTPAuth = true;
    $mail->Username = $email_config['smtp']['username'];
    $mail->Password = $email_config['smtp']['password'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = $email_config['smtp']['port'];
    $mail->Timeout = $email_config['smtp']['timeout'] ?? 60;

    $mail->setFrom($email_config['sender']['email'], $email_config['sender']['name']);

    // Ambil email penerima dari database
    try {
        $email_query = "SELECT nama, email FROM email_recipients WHERE status = 'active' ORDER BY nama";
        $email_result = $conn->query($email_query);

        if (!$email_result) {
            throw new Exception("Query email recipients gagal: " . $conn->error);
        }

        $recipient_count = 0;
        while ($recipient_row = $email_result->fetch_assoc()) {
            $recipient_email = trim($recipient_row['email']);
            $recipient_name = trim($recipient_row['nama']);

            if (!filter_var($recipient_email, FILTER_VALIDATE_EMAIL)) {
                writeLog("Email tidak valid diabaikan: $recipient_email");
                continue;
            }

            $mail->addBCC($recipient_email, $recipient_name);
            $recipient_count++;
            writeLog("Email ditambahkan: $recipient_name ($recipient_email)");
        }

        if ($recipient_count == 0) {
            throw new Exception("Tidak ada email penerima yang valid ditemukan di database");
        }

        writeLog("Total $recipient_count penerima email dari database");

    } catch (Exception $e) {
        writeLog("Error mengambil email dari database: " . $e->getMessage());
        writeLog("Menggunakan fallback email dari config file");
        foreach ($email_config['recipients']['bcc'] as $email => $name) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $mail->addBCC($email, $name);
                writeLog("Fallback email ditambahkan: $name ($email)");
            }
        }
    }

    $mail->addAttachment($temp_file, $filename);
    // Tambahkan logo sebagai embedded image
        $logo_path = __DIR__ . '/../assets/img/LogoFIS.png';
        if (file_exists($logo_path)) {
            $mail->addEmbeddedImage($logo_path, 'logo_fis', 'LogoFIS.png');
            writeLog("Logo ditambahkan: $logo_path");
        } else {
            writeLog("WARNING: Logo tidak ditemukan di: $logo_path");
        }

    $mail->isHTML(true);
    $mail->Subject = $email_config['content']['subject_prefix'] . ' - ' . date($email_config['content']['date_format']);

    $mail->Body = "
    <html>
    <head>
    <style>
    body, p { 
        font-family: Arial, sans-serif; 
        font-size: 10pt; 
        margin: 0;
        padding: 0;
    }
    .footer {
    margin-top: 15px;
    padding-top: 5px;
    }
    .footer p {
    margin: 2px 0;
    color: #666;
    text-align: left;
    }
    .logo {
        max-width: 60px !important;
        height: auto !important;
    }
</style>
</head>
<body>
    <p>Hi...</p>
    <p>Here The Report Inventory Carton, ATK, Sparepart " . date('d F Y') . "</p>
    <br>
    <div class='footer'>
        <p><strong>Please Do Not Reply</strong></p>
        <p>Powered By: <strong>Fislogapps.com</strong></p>
        <img src='cid:logo_fis' alt='FIS Logo'
            width='60'
            style='width:60px;height:auto;display:block;margin-top:5px;'
            class='logo'>
    </div>
</body>
</html>";


    $mail->AltBody = "Hi...\nHere The Report Inventory Carton, ATK, Sparepart " . date('d F Y') . "\nPlease Do Not Reply\nPowered By: Fislogapps.com";

    // Retry kirim email
    $attempts = $email_config['advanced']['retry_attempts'] ?? 3;
    $success = false;
    for ($i = 0; $i < $attempts; $i++) {
        try {
            $mail->send();
            $success = true;
            writeLog("Email dikirim sukses (attempt " . ($i + 1) . ")");
            break;
        } catch (Exception $e) {
            writeLog("Gagal kirim (attempt " . ($i + 1) . "): " . $e->getMessage());
            sleep(5);
        }
    }
    if (!$success) {
        throw new Exception("Gagal kirim email setelah $attempts coba");
    }

    // Cleanup
    if (file_exists($temp_file)) {
        unlink($temp_file);
        writeLog("File temp dihapus: $filename");
    }

    writeLog("Proses selesai sukses");
    echo "Report dikirim sukses!\n";

} catch (Exception $e) {
    $error_msg = "Error: " . $e->getMessage();
    writeLog($error_msg);
    echo $error_msg . "\n";
} finally {
    ob_end_clean();
    $conn->close();
}
?>