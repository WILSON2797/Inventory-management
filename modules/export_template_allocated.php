<?php
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set header
$sheet->setCellValue('A1', 'Order Number');
$sheet->setCellValue('B1', 'Customer');
$sheet->setCellValue('C1', 'Lottable1');
$sheet->setCellValue('D1', 'Lottable2');
$sheet->setCellValue('E1', 'Destination');
$sheet->setCellValue('F1', 'Item Code');
$sheet->setCellValue('G1', 'Qty');

// Style header
$headerStyle = [
    'font' => [
        'bold' => true,
        'color' => ['argb' => 'FFFFFFFF'],
    ],
    'fill' => [
        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
        'startColor' => ['argb' => 'FF4472C4'],
    ]
];
$sheet->getStyle('A1:G1')->applyFromArray($headerStyle);

// Sample data yang diperbaiki
$sampleData = [
    ['SDR-001', 'NOKIA', 'SDR-001', 'SDR-001', 'Site', 'KRT-604020', 100],
    ['SDR-002', 'NOKIA', 'SDR-002', 'SDR-002', 'DOP Palembang', 'KRT-604050', 50],
    ['OUTBOUND-20250827_PONTIANAK', 'FIS', '', '', 'DOP Pontianak', 'KRT-604020', 20],
];

// Tambahkan sample data ke sheet mulai dari baris ke-2
$row = 2;
foreach ($sampleData as $data) {
    $col = 'A';
    foreach ($data as $value) {
        $sheet->setCellValue($col . $row, $value);
        $col++;
    }
    $row++;
}

$dataStyle = [
    'font' => [
        'color' => ['argb' => 'FF666666'], 
        'italic' => true, 
    ]
];
$sheet->getStyle('C2:D4')->applyFromArray($dataStyle);

// Auto-size columns
foreach (range('A', 'G') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Style untuk data rows (optional: memberikan border)
$dataStyle = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
        ],
    ],
];
$lastRow = 1 + count($sampleData);
$sheet->getStyle('A1:G' . $lastRow)->applyFromArray($dataStyle);

$writer = new Xlsx($spreadsheet);
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Allocated_Template.xlsx"');
header('Cache-Control: max-age=0');
$writer->save('php://output');
exit;
?>