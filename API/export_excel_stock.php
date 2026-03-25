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
    $query = "SELECT * FROM stock WHERE 1=1";
    
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
    $query .= " ORDER BY Inbound_date ASC";

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
            'Inbound_date' => $row['Inbound_date'] ?? '-',
            'po_number' => $row['po_number'] ?? '-',
            'supplier' => $row['supplier'] ?? '-',
            'item_code' => $row['item_code'] ?? '-',
            'item_description' => $row['item_description'] ?? '-', // PERBAIKAN: Sesuaikan nama field
            'qty_inbound' => $row['qty_inbound'] ?? 0,
            'qty_allocated' => $row['qty_allocated'] ?? 0,
            'qty_out' => $row['qty_out'] ?? 0,
            'stock_on_hand' => $row['stock_on_hand'] ?? 0, // PERBAIKAN: Konsistensi nama field
            'stock_balance' => $row['stock_balance'] ?? '-',
            'uom' => $row['uom'] ?? '-', // PERBAIKAN: Default string bukan angka
            'locator' => $row['locator'] ?? '-',
            'packing_list' => $row['packing_list'] ?? '-', // PERBAIKAN: Default string bukan null
            'wh_name' => $row['wh_name'] ?? '-',
            'stock_type' => $row['stock_type'] ?? '-',
            'last_updated' => $row['last_updated'] ?? '-',
            'status_stock' => $row['status_stock'] ?? '-'
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
$sheet->setCellValue('C1', 'PO Number');
$sheet->setCellValue('D1', 'Supplier');
$sheet->setCellValue('E1', 'Item Code');
$sheet->setCellValue('F1', 'Description');
$sheet->setCellValue('G1', 'Qty Inbound');
$sheet->setCellValue('H1', 'Qty Allocated');
$sheet->setCellValue('I1', 'Qty Outbound');
$sheet->setCellValue('J1', 'Stock Onhand');
$sheet->setCellValue('K1', 'Stock Balance');
$sheet->setCellValue('L1', 'UOM');
$sheet->setCellValue('M1', 'Locator');
$sheet->setCellValue('N1', 'Packing List');
$sheet->setCellValue('O1', 'Warehouse Name');
$sheet->setCellValue('P1', 'Stock Type');
$sheet->setCellValue('Q1', 'Last Update');
$sheet->setCellValue('R1', 'Status Stock');

// Terapkan style untuk header
$sheet->getStyle('A1:R1')->applyFromArray($headerStyle);

// Isi data
$rowNumber = 2;
foreach ($data as $index => $row) {
    $sheet->setCellValue('A' . $rowNumber, $index + 1);
    $sheet->setCellValue('B' . $rowNumber, $row['Inbound_date']);
    $sheet->setCellValue('C' . $rowNumber, $row['po_number']);
    $sheet->setCellValue('D' . $rowNumber, $row['supplier']);
    $sheet->setCellValue('E' . $rowNumber, $row['item_code']);
    $sheet->setCellValue('F' . $rowNumber, $row['item_description']); // PERBAIKAN: Mapping yang benar
    $sheet->setCellValue('G' . $rowNumber, $row['qty_inbound']);
    $sheet->setCellValue('H' . $rowNumber, $row['qty_allocated']);
    $sheet->setCellValue('I' . $rowNumber, $row['qty_out']);
    $sheet->setCellValue('J' . $rowNumber, $row['stock_on_hand']); // PERBAIKAN: Mapping yang benar
    $sheet->setCellValue('K' . $rowNumber, $row['stock_balance']);
    $sheet->setCellValue('L' . $rowNumber, $row['uom']);
    $sheet->setCellValue('M' . $rowNumber, $row['locator']);
    $sheet->setCellValue('N' . $rowNumber, $row['packing_list']);
    $sheet->setCellValue('O' . $rowNumber, $row['wh_name']);
    $sheet->setCellValue('P' . $rowNumber, $row['stock_type']);
    $sheet->setCellValue('Q' . $rowNumber, $row['last_updated']);
    $sheet->setCellValue('R' . $rowNumber, $row['status_stock']);

    // Format tanggal jika ada
    if (!empty($row['Inbound_date']) && $row['Inbound_date'] !== '-') {
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
    $sheet->getStyle('A2:R' . $lastRow)->applyFromArray($dataStyle);
}

// Set center alignment untuk kolom D sampai Q
$sheet->getStyle('D:Q')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->getStyle('E:F')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

// Freeze column B (kolom A dan B tetap terlihat)
$sheet->freezePane('C2');

// Auto-size columns
foreach (range('A', 'R') as $column) {
    $sheet->getColumnDimension($column)->setAutoSize(true);
}

// Pastikan tidak ada output sebelum header
if (ob_get_length()) {
    ob_clean();
}

// Set header untuk download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Stock_Report_' . date('Ymd_His') . '.xlsx"');
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