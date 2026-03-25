<?php
// Bersihkan semua output buffer sebelum memulai
while (ob_get_level()) {
    ob_end_clean();
}

// Mulai output buffering baru
ob_start();

require '../vendor/autoload.php';
require_once '../php/config.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

try {
    // Ambil data dari database
    $data = [];
    $query = "SELECT * FROM master_sku ORDER BY item_code ASC";
    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception("Error executing query: " . $conn->error);
    }
    
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'item_code' => $row['item_code'] ?? '-',
            'item_description' => $row['item_description'] ?? '-',
            'uom' => $row['uom'] ?? '-',
            'project' => $row['project'] ?? '-'
        ];
    }
    
    // Buat spreadsheet baru
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Styling untuk header
    $headerStyle = [
        'font' => [
            'name' => 'Arial',
            'bold' => true,
            'color' => ['argb' => 'FFFFFFFF'], // Warna teks putih
            'size' => 10
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['argb' => 'FF000000'],
            ],
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => [
                'argb' => 'FF0066CC', // Warna biru
            ],
        ],
    ];
    
    // Set header
    $headers = [
        'A1' => 'No',
        'B1' => 'Item Code',
        'C1' => 'Description',
        'D1' => 'uom',
        'E1' => 'project'
    ];
    
    foreach ($headers as $cell => $value) {
        $sheet->setCellValue($cell, $value);
    }
    
    // Terapkan style untuk header
    $sheet->getStyle('A1:E1')->applyFromArray($headerStyle);
    
    // Isi data
    $rowNumber = 2; // Mulai dari baris kedua
    $no = 1;
    
    foreach ($data as $item) {
        $sheet->setCellValue('A' . $rowNumber, $no);
        $sheet->setCellValue('B' . $rowNumber, $item['item_code']);
        $sheet->setCellValue('C' . $rowNumber, $item['item_description']);
        $sheet->setCellValue('D' . $rowNumber, $item['uom']);
        $sheet->setCellValue('E' . $rowNumber, $item['project']);
        $rowNumber++;
        $no++;
    }
    
    // Styling untuk data rows
    $lastRow = $rowNumber - 1;
    if ($lastRow > 1) {
        $dataStyle = [
            'font' => [
                'name' => 'Arial',
                'size' => 9,
                'color' => ['argb' => 'FF000000'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FFCCCCCC'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];
        $sheet->getStyle('A2:E' . $lastRow)->applyFromArray($dataStyle);
        
        // Alignment khusus untuk kolom tertentu
        $sheet->getStyle('A2:A' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B2:B' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('C2:C' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('D2:D' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E2:E' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }
    
    // Auto-size columns
    foreach (range('A', 'E') as $column) {
        $sheet->getColumnDimension($column)->setAutoSize(true);
    }
    
    // Set tinggi baris header
    $sheet->getRowDimension('1')->setRowHeight(25);
    

    
    // Bersihkan output buffer sebelum mengirim file
    ob_end_clean();
    
    // Set header untuk download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="Master_SKU_' . date('Ymd_His') . '.xlsx"');
    header('Cache-Control: max-age=0');
    header('Expires: 0');
    header('Pragma: public');
    
    // Pastikan tidak ada output lain
    if (headers_sent()) {
        throw new Exception("Headers already sent. Cannot export Excel file.");
    }
    
    // Buat writer dan save
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    
} catch (Exception $e) {
    // Jika terjadi error, bersihkan buffer dan tampilkan pesan error
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Log error
    error_log("Export SKU Error: " . $e->getMessage());
    
    // Tampilkan error ke user
    header('Content-Type: text/html; charset=utf-8');
    echo "Error: " . htmlspecialchars($e->getMessage());
}

exit();
?>