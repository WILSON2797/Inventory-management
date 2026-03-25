<?php
require_once '../vendor/autoload.php'; // Memuat PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Buat spreadsheet baru
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set header
$sheet->setCellValue('A1', 'item_code');
$sheet->setCellValue('B1', 'item_description');
$sheet->setCellValue('C1', 'uom');
$sheet->setCellValue('D1', 'project');

// Set contoh data
$sheet->setCellValue('A2', '474800A');
$sheet->setCellValue('B2', 'Antenna');
$sheet->setCellValue('C2', 'PCS');
$sheet->setCellValue('D2', 'NOKIA');

$sheet->setCellValue('A3', '474801B');
$sheet->setCellValue('B3', 'Cable');
$sheet->setCellValue('C3', 'PCS');
$sheet->setCellValue('D3', 'REGULER');

// Set header untuk file Excel
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="sku_template.xlsx"');
header('Cache-Control: max-age=0');

// Simpan file ke output
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>