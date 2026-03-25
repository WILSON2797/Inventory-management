<?php
// Hapus semua output sebelumnya dan mulai output buffering yang bersih
ob_clean();
ob_start();

require '../vendor/autoload.php';
include '../php/config.php';
session_start();

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;



error_log("Session role: " . ($_SESSION['role'] ?? 'undefined') . ", wh_name: " . ($_SESSION['wh_name'] ?? 'undefined') . ", session_id: " . session_id());

// Ambil data dari database
$data = [];
try {
    // Query dasar - PERBAIKAN: Tambahkan WHERE 1=1 untuk menghindari masalah AND
    $query = "SELECT * FROM inbound WHERE 1=1";
    
    $params = [];
    $types = "";

    // Tambahkan kondisi WHERE berdasarkan role user
    if (isset($_SESSION['role'])) {
        if ($_SESSION['role'] !== 'admin') {
            // Untuk user dan customer, filter berdasarkan wh_name
            if (isset($_SESSION['wh_name'])) {
                $query .= " AND wh_name = ?";
                $params[] = $_SESSION['wh_name'];
                $types .= "s";
                error_log("Filtering by wh_name: " . $_SESSION['wh_name']);
            } else {
                error_log("No wh_name in session for role: " . $_SESSION['role']);
                ob_end_clean();
                exit("No warehouse name defined for this user.");
            }
        }
        // Admin bisa melihat semua data (tidak perlu filter tambahan)
    } else {
        error_log("No role in session");
        ob_end_clean();
        exit("User role not defined.");
    }

    // Selesaikan query dengan pengurutan
    $query .= " ORDER BY created_date ASC";

    error_log("Query executed: " . $query);

    // Siapkan dan jalankan query
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    error_log("export_excel_stock: Number of rows returned: " . $result->num_rows);

    while ($row = $result->fetch_assoc()) {
        $data[] = [
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

    $stmt->close();
} catch (mysqli_sql_exception $e) {
    error_log("Error in export_excel_stock: " . $e->getMessage() . " | Query: " . $query);
    ob_end_clean();
    exit("Terjadi kesalahan saat mengambil data: " . $e->getMessage());
}

// Bersihkan semua output buffer sebelum membuat file Excel
ob_end_clean();

// Buat spreadsheet baru
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Styling untuk header
$headerStyle = [
    'font' => [
        'bold' => true,
        'color' => ['argb' => 'FFFFFFFF'], // Warna teks putih
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
        ],
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => [
            'argb' => 'FF00008B', // Warna biru
        ],
    ],
];

// Set header
$sheet->setCellValue('A1', 'No');
$sheet->setCellValue('B1', 'Inbound Date');
$sheet->setCellValue('C1', 'Transaction Number');
$sheet->setCellValue('D1', 'PO Number');
$sheet->setCellValue('E1', 'Supplier');
$sheet->setCellValue('F1', 'Refference No');
$sheet->setCellValue('G1', 'Packing List');
$sheet->setCellValue('H1', 'Item Code');
$sheet->setCellValue('I1', 'Description');
$sheet->setCellValue('J1', 'Qty Inbound');
$sheet->setCellValue('K1', 'UOM');
$sheet->setCellValue('L1', 'Locator');
$sheet->setCellValue('M1', 'Warehouse Name');
$sheet->setCellValue('N1', 'Stock Type');
$sheet->setCellValue('O1', 'Submit By');

// Terapkan style untuk header
$sheet->getStyle('A1:O1')->applyFromArray($headerStyle);

// Isi data
$rowNumber = 2;
foreach ($data as $index => $row) {
    $sheet->setCellValue('A' . $rowNumber, $index + 1);
    $sheet->setCellValue('B' . $rowNumber, $row['created_date']);
    $sheet->setCellValue('C' . $rowNumber, $row['transaction_sequence']);
    $sheet->setCellValue('D' . $rowNumber, $row['po_number']);
    $sheet->setCellValue('E' . $rowNumber, $row['supplier']);
    $sheet->setCellValue('F' . $rowNumber, $row['reference_number']);
    $sheet->setCellValue('G' . $rowNumber, $row['packing_list']);
    $sheet->setCellValue('H' . $rowNumber, $row['item_code']);
    $sheet->setCellValue('I' . $rowNumber, $row['item_description']);
    $sheet->setCellValue('J' . $rowNumber, $row['qty']);
    $sheet->setCellValue('K' . $rowNumber, $row['uom']);
    $sheet->setCellValue('L' . $rowNumber, $row['locator']);
    $sheet->setCellValue('M' . $rowNumber, $row['wh_name']);
    $sheet->setCellValue('N' . $rowNumber, $row['stock_type']);
    $sheet->setCellValue('O' . $rowNumber, $row['created_by']);

    // Format tanggal jika ada
    if (!empty($row['created_date']) && $row['created_date'] !== '-') {
        $sheet->getStyle('B' . $rowNumber)
            ->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);
    }
    
    $rowNumber++;
}

// PERBAIKAN: Definisikan $lastRow dengan benar
$lastRow = $rowNumber - 1;

// Styling untuk semua data rows
$dataStyle = [
    'font' => [
        'name' => 'Arial',
        'size' => 9,
        'color' => ['argb' => 'FF000000'], // Hitam
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
        ],
    ],
];

// Hanya apply style jika ada data
if ($lastRow > 1) {
    $sheet->getStyle('A2:O' . $lastRow)->applyFromArray($dataStyle);
}

// Set center alignment untuk kolom D sampai Q
$sheet->getStyle('D:O')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->getStyle('H:I')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

// Freeze column B (kolom A dan B tetap terlihat)
$sheet->freezePane('C2');

// Auto-size columns
foreach (range('A', 'O') as $column) {
    $sheet->getColumnDimension($column)->setAutoSize(true);
}

// Pastikan tidak ada output sebelum header
if (ob_get_length()) {
    ob_clean();
}

// Set header untuk download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Inbound_Transaction_Log_' . date('Ymd_His') . '.xlsx"');
header('Cache-Control: max-age=0');
header('Pragma: public');
header('Expires: 0');

// Simpan file
$writer = new Xlsx($spreadsheet);

try {
    $writer->save('php://output');
} catch (Exception $e) {
    error_log("Error saving Excel file: " . $e->getMessage());
    header('Content-Type: text/plain');
    echo "Error generating Excel file: " . $e->getMessage();
}

$conn->close();
exit();
?>