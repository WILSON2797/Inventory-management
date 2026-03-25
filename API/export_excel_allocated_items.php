<?php
require '../vendor/autoload.php';
require_once '../php/config.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Bersihkan output buffer sebelum memulai
if (ob_get_level()) {
    ob_end_clean();
}

// Ambil data dari database
$data = [];
try {
    // Query untuk mengambil data dari tabel allocated
   $query = "SELECT * FROM allocated WHERE status = 'allocated'";
    
    $params = [];
    $types = "";

    // Tambahkan kondisi WHERE berdasarkan role user
    if (isset($_SESSION['role'])) {
        if ($_SESSION['role'] !== 'admin') {
            // Untuk admin, user, dan customer, filter berdasarkan wh_name
            if (isset($_SESSION['wh_name'])) {
                $query .= " AND wh_name = ?";
                $params[] = $_SESSION['wh_name'];
                $types .= "s";
                error_log("Filtering by wh_name: " . $_SESSION['wh_name']);
            } else {
                error_log("No wh_name in session for role: " . $_SESSION['role']);
                exit("No warehouse name defined for this user.");
            }
        }
        // Superadmin tidak perlu filter tambahan
    } else {
        error_log("No role in session");
        exit("User role not defined.");
    }

    // Selesaikan query dengan pengurutan
    $query .= " ORDER BY allocated_date DESC";

    error_log("Query executed: " . $query);

    // Siapkan dan jalankan query
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    error_log("export_allocated_items: Number of rows returned: " . $result->num_rows);

    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'transaction_sequence' => $row['transaction_sequence'] ?? '-',
            'order_number' => $row['order_number'] ?? '-',
            'customer' => $row['customer'] ?? '-',
            'item_code' => $row['item_code'] ?? '-',
            'item_description' => $row['item_description'] ?? '-',
            'qty_picking' => $row['qty_picking'] ?? 0,
            'uom' => $row['uom'] ?? '-',
            'locator_picking' => $row['locator_picking'] ?? '-',
            'packing_list' => $row['packing_list'] ?? '-',
            'lottable1' => $row['lottable1'] ?? '-',
            'lottable2' => $row['lottable2'] ?? '-',
            'lottable3' => $row['lottable3'] ?? '-',
            'allocated_date' => $row['allocated_date'] ?? null,
            'status' => $row['status'] ?? '-',
            'wh_name' => $row['wh_name'] ?? '-',
        ];
    }

    $stmt->close();
} catch (mysqli_sql_exception $e) {
    error_log("Error in export_allocated_items: " . $e->getMessage() . " | Query: " . $query);
    exit("Terjadi kesalahan saat mengambil data: " . $e->getMessage());
}

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
        'vertical' => Alignment::VERTICAL_CENTER,
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
        ],
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => [
            'argb' => 'FF0066CC', // Warna biru
        ],
    ],
];

// Set header columns
$headers = [
    'A1' => 'No',
    'B1' => 'Transaction Sequence',
    'C1' => 'Order Number',
    'D1' => 'Customer',
    'E1' => 'Item Code',
    'F1' => 'Item Description',
    'G1' => 'Qty Picking',
    'H1' => 'UOM',
    'I1' => 'Locator Picking',
    'J1' => 'Packing List',
    'K1' => 'Lottable1',
    'L1' => 'Lottable2',
    'M1' => 'Destination',
    'N1' => 'Allocated Date',
    'O1' => 'Status',
    'P1' => 'Warehouse Name'
];

// Set header values
foreach ($headers as $cell => $value) {
    $sheet->setCellValue($cell, $value);
}

// Terapkan style untuk header
$sheet->getStyle('A1:P1')->applyFromArray($headerStyle);

// Isi data
$rowNumber = 2; // Mulai dari baris kedua
$no = 1;

foreach ($data as $item) {
    $sheet->setCellValue('A' . $rowNumber, $no);
    $sheet->setCellValue('B' . $rowNumber, $item['transaction_sequence']);
    $sheet->setCellValue('C' . $rowNumber, $item['order_number']);
    $sheet->setCellValue('D' . $rowNumber, $item['customer']);
    $sheet->setCellValue('E' . $rowNumber, $item['item_code']);
    $sheet->setCellValue('F' . $rowNumber, $item['item_description']);
    $sheet->setCellValue('G' . $rowNumber, $item['qty_picking']);
    $sheet->setCellValue('H' . $rowNumber, $item['uom']);
    $sheet->setCellValue('I' . $rowNumber, $item['locator_picking']);
    $sheet->setCellValue('J' . $rowNumber, $item['packing_list']);
    $sheet->setCellValue('K' . $rowNumber, $item['lottable1']);
    $sheet->setCellValue('L' . $rowNumber, $item['lottable2']);
    $sheet->setCellValue('M' . $rowNumber, $item['lottable3']);
    
    // Format tanggal
    if (!empty($item['allocated_date'])) {
        $sheet->setCellValue('N' . $rowNumber, $item['allocated_date']);
        $sheet->getStyle('N' . $rowNumber)
            ->getNumberFormat()
            ->setFormatCode('dd-mm-yyyy hh:mm:ss');
    } else {
        $sheet->setCellValue('N' . $rowNumber, '-');
    }
    
    $sheet->setCellValue('O' . $rowNumber, ucfirst($item['status']));
    $sheet->setCellValue('P' . $rowNumber, $item['wh_name']);

    $rowNumber++;
    $no++;
}

// Styling untuk semua data rows
$lastRow = $rowNumber - 1;
if ($lastRow > 1) {
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
        'alignment' => [
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
    ];
    $sheet->getStyle('A2:P' . $lastRow)->applyFromArray($dataStyle);

    // Style khusus untuk kolom numerik
    $sheet->getStyle('G2:G' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
}

// Freeze pane pada kolom D dan baris 2
$sheet->freezePane('C2');

// Auto-size columns
foreach (range('A', 'P') as $column) {
    $sheet->getColumnDimension($column)->setAutoSize(true);
}

// Set tinggi baris header
$sheet->getRowDimension('1')->setRowHeight(25);

// Set header untuk download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="AllocatedItems_Details_' . date('Ymd_His') . '.xlsx"');
header('Cache-Control: max-age=0');
header('Expires: 0');
header('Pragma: public');

// Buat writer dan save
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();
?>