<?php
session_start();
include '../php/config.php';
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

if (!isset($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'ID tidak validasi']);
    exit();
}

$id = mysqli_real_escape_string($conn, $_GET['id']);
$query = "SELECT file_path, file_name, error_message, report_path FROM queue_task WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$file = $result->fetch_assoc();
$stmt->close();

if (!$file || empty($file['report_path']) || !file_exists($file['report_path'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Laporan file tidak ditemukan']);
    exit();
}

try {
    // Load existing spreadsheet
    $spreadsheet = IOFactory::load($file['report_path']);
    $worksheet = $spreadsheet->getActiveSheet();
    
    // Get the highest row and column
    $highestRow = $worksheet->getHighestRow();
    $highestColumn = $worksheet->getHighestColumn();
    $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
    
    // Style untuk header (baris pertama)
    $headerStyle = [
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => [
                'rgb' => '1F4E79', // Biru tua
            ],
        ],
        'font' => [
            'color' => [
                'rgb' => 'FFFFFF', // Putih
            ],
            'bold' => true,
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
    ];
    
    // Style untuk kolom Error Log (kolom H - kolom ke-8, teks merah bold)
    $errorLogStyle = [
        'font' => [
            'color' => [
                'rgb' => 'FF0000', // Merah
            ],
            'bold' => true,
        ],
    ];
    
    // Style untuk highlight duplicate rows (background kuning muda)
    $duplicateRowStyle = [
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => [
                'rgb' => 'FFFF99', // Kuning muda
            ],
        ],
    ];
    
    // Terapkan style header untuk baris pertama
    $headerRange = 'A1:' . $highestColumn . '1';
    $worksheet->getStyle($headerRange)->applyFromArray($headerStyle);
    
    // Terapkan style untuk kolom Error Log (kolom H) dan highlight duplicate rows
    for ($row = 2; $row <= $highestRow; $row++) {
        // Style untuk kolom Error Log (kolom H)
        if ($highestColumnIndex >= 8) {
            $worksheet->getStyle('H' . $row)->applyFromArray($errorLogStyle);
        }
        
        // Check jika baris mengandung duplicate untuk highlight
        $errorLogValue = $worksheet->getCell('H' . $row)->getValue();
        if (stripos($errorLogValue, 'duplicate') !== false) {
            // Highlight seluruh baris untuk data duplicate
            $rowRange = 'A' . $row . ':' . $highestColumn . $row;
            $worksheet->getStyle($rowRange)->applyFromArray($duplicateRowStyle);
            
            // Tetap beri style merah bold untuk error log
            $worksheet->getStyle('H' . $row)->applyFromArray($errorLogStyle);
        }
    }
    
    // Auto-fit semua kolom
    for ($col = 1; $col <= $highestColumnIndex; $col++) {
        $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
        $worksheet->getColumnDimension($columnLetter)->setAutoSize(true);
    }
    
    // Set tinggi baris header
    $worksheet->getRowDimension(1)->setRowHeight(25);
    
    // Set minimum width untuk kolom Error Log agar tidak terlalu kecil
    if ($highestColumnIndex >= 8) {
        $worksheet->getColumnDimension('H')->setWidth(30);
    }
    
    // Tambahkan border untuk seluruh tabel
    $tableRange = 'A1:' . $highestColumn . $highestRow;
    $borderStyle = [
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['rgb' => '000000'],
            ],
        ],
    ];
    $worksheet->getStyle($tableRange)->applyFromArray($borderStyle);
    
    // Freeze panes untuk header
    $worksheet->freezePane('A2');
    
    // Buat file temporary untuk menyimpan hasil styling
    $tempFile = tempnam(sys_get_temp_dir(), 'styled_excel_');
    $writer = new Xlsx($spreadsheet);
    $writer->save($tempFile);
    
    // Set headers untuk download
    $filename = 'Upload_Result_' . pathinfo($file['file_name'], PATHINFO_FILENAME) . '.xlsx';
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($tempFile));
    header('Cache-Control: max-age=0');
    header('Cache-Control: max-age=1');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    header('Cache-Control: cache, must-revalidate');
    header('Pragma: public');
    
    // Output file dan hapus temporary file
    readfile($tempFile);
    unlink($tempFile);
    
} catch (Exception $e) {
    // Jika terjadi error dalam styling, kirim file original
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="Upload_Result_' . basename($file['file_name']) . '"');
    header('Content-Length: ' . filesize($file['report_path']));
    readfile($file['report_path']);
}

exit();
?>