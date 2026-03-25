<?php
require '../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set header
$sheet->setCellValue('A1', 'PO Number');
$sheet->setCellValue('B1', 'Supplier');
$sheet->setCellValue('C1', 'Reference Number');
$sheet->setCellValue('D1', 'Packing List');
$sheet->setCellValue('E1', 'Item Code');
$sheet->setCellValue('F1', 'Qty');
$sheet->setCellValue('G1', 'Locator');
$sheet->setCellValue('H1', 'Stock Type');

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
$sheet->getStyle('A1:H1')->applyFromArray($headerStyle);

// Auto-size columns
foreach (range('A', 'H') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Create dropdown for Stock Type column H (dari H2 sampai H1000)
$stockTypeOptions = ['ATK', 'PACKAGING', 'TELCO', 'SPAREPART'];
$validation = $sheet->getCell('H2')->getDataValidation();
$validation->setType(DataValidation::TYPE_LIST);
$validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
$validation->setAllowBlank(true);
$validation->setShowInputMessage(true);
$validation->setShowErrorMessage(true);
$validation->setShowDropDown(true);
$validation->setErrorTitle('Input error');
$validation->setError('Value is not in list.');
$validation->setPromptTitle('Pick from list');
$validation->setPrompt('Please pick a value from the drop-down list.');
$validation->setFormula1('"' . implode(',', $stockTypeOptions) . '"');

// Copy validation ke seluruh kolom H (H2:H1000)
for ($row = 2; $row <= 50; $row++) {
    $sheet->getCell('H' . $row)->setDataValidation(clone $validation);
}

// Tambahkan sample data TANPA background fill
$sampleData = [
    ['PO-2025-001', 'PT SUPPLIER A', 'REF-001', 'PL-001', 'ITM-001', 10, 'A-01-001', 'ATK'],
    ['PO-2025-002', 'PT SUPPLIER B', 'REF-002', 'PL-002', 'ITM-002', 25, 'A-02-001', 'PACKAGING'],
    ['PO-2025-003', 'PT SUPPLIER C', 'REF-003', 'PL-003', 'ITM-003', 15, 'B-01-001', 'TELCO'],
    ['PO-2025-004', 'PT SUPPLIER D', 'REF-004', 'PL-004', 'ITM-004', 50, 'B-02-001', 'SPAREPART'],
];

// Insert sample data
$row = 2;
foreach ($sampleData as $data) {
    $sheet->setCellValue('A' . $row, $data[0]);
    $sheet->setCellValue('B' . $row, $data[1]);
    $sheet->setCellValue('C' . $row, $data[2]);
    $sheet->setCellValue('D' . $row, $data[3]);
    $sheet->setCellValue('E' . $row, $data[4]);
    $sheet->setCellValue('F' . $row, $data[5]);
    $sheet->setCellValue('G' . $row, $data[6]);
    $sheet->setCellValue('H' . $row, $data[7]);
    $row++;
}


$dataStyle = [
    'font' => [
        'color' => ['argb' => 'FF666666'], 
        'italic' => true, 
    ]
];
$sheet->getStyle('A2:H5')->applyFromArray($dataStyle);

$writer = new Xlsx($spreadsheet);
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Inbound_Template.xlsx"');
header('Cache-Control: max-age=0');

$writer->save('php://output');
exit;
?>