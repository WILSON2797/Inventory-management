<?php
session_start();
include '../php/config.php';

// Cek autentikasi user
if (!isset($_SESSION['username'])) {
    die('User tidak terautentikasi');
}

// Cek parameter transaction_id
if (!isset($_GET['transaction_id'])) {
    die('Transaction ID tidak ditemukan');
}

// Sanitasi input
$transaction_id = $_GET['transaction_id'];
$user_role = $_SESSION['role'] ?? '';
$wh_name = $_SESSION['wh_name'] ?? '';

// Conditional query berdasarkan role
if ($user_role === 'admin' || $user_role === 'superadmin') {
    $query_header = "SELECT order_number, lottable3, created_date, wh_name, customer, transaction_id, project_name FROM outbound WHERE transaction_id = ? LIMIT 1";
    $stmt = $conn->prepare($query_header);
    $stmt->bind_param("s", $transaction_id);
} else {
    $query_header = "SELECT order_number, lottable3, created_date, wh_name, customer, transaction_id, project_name FROM outbound WHERE transaction_id = ? AND wh_name = ? LIMIT 1";
    $stmt = $conn->prepare($query_header);
    $stmt->bind_param("ss", $transaction_id, $wh_name);
}

$stmt->execute();
$result_header = $stmt->get_result();

if ($result_header === false) {
    die("Query gagal: " . $conn->error);
}

if ($result_header->num_rows === 0) {
    die('Transaction ID tidak ditemukan di tabel Outbound');
}
$row_header = $result_header->fetch_assoc();
$order_number = $row_header['transaction_id'];

// Query detail
if ($user_role === 'admin' || $user_role === 'superadmin') {
    $query_details = "SELECT transaction_id, order_number, customer, lottable1, lottable2, lottable3, item_code, item_description, qty, uom, locator, created_date, wh_name, project_name FROM outbound WHERE transaction_id = ?";
    $stmt = $conn->prepare($query_details);
    $stmt->bind_param("s", $transaction_id);
} else {
    $query_details = "SELECT transaction_id, order_number, customer, lottable1, lottable2, lottable3, item_code, item_description, qty, uom, locator, created_date, wh_name, project_name FROM outbound WHERE transaction_id = ? AND wh_name = ?";
    $stmt = $conn->prepare($query_details);
    $stmt->bind_param("ss", $transaction_id, $wh_name);
}

$stmt->execute();
$result_details = $stmt->get_result();

if ($result_details === false) {
    die("Query details gagal: " . $conn->error);
}

if ($result_details->num_rows === 0) {
    die("Tidak ada detail items untuk transaction ID: $transaction_id");
}

// Include TCPDF
require_once('../vendor/tecnickcom/tcpdf/tcpdf.php');

// Custom TCPDF Class dengan desain profesional
class MYPDF extends TCPDF {
    protected $order_number;
    protected $header_data;

    public function setDeliveryOrder($order_number) {
        $this->order_number = $order_number;
    }

    public function setCustomHeaderData($header_data) {
        $this->header_data = $header_data;
    }

    public function Header() {
        // Header Background dengan gradient effect (simulasi dengan rectangle)
        $this->SetFillColor(240, 248, 255);
        $this->Rect(0, 0, 210, 50, 'F');
        
        // Border atas dengan warna brand
        $this->SetFillColor(0, 51, 153);
        $this->Rect(0, 0, 210, 3, 'F');
        
        // Logo dan Info Perusahaan
        $this->Image('../assets/img/Fis.jpg', 10, 8, 28);
        
        // Company Info - Modern Style
        $this->SetTextColor(0, 51, 153);
        $this->SetFont('helvetica', 'B', 16);
        $this->SetXY(42, 8);
        $this->Cell(0, 6, 'PT. FAN INDONESIA SEJAHTERA', 0, 1, 'L');
        
        $this->SetTextColor(80, 80, 80);
        $this->SetFont('helvetica', '', 9);
        $this->SetXY(42, 16);
        $this->Cell(0, 4, 'Rukan Gardenia Blok RF12, Pesona Metropolitan Bekasi', 0, 1, 'L');
        $this->SetXY(42, 20);
        $this->Cell(0, 4, 'Jawa Barat, Indonesia | Phone: (021) XXX-XXXX', 0, 1, 'L');
        
        // Ship To Box - Modern Card Style
        $this->SetFillColor(255, 255, 255);
        $this->SetDrawColor(200, 200, 200);
        $this->SetLineWidth(0.3);
        $this->RoundedRect(135, 8, 65, 26, 2, '1111', 'DF');
        
        $this->SetTextColor(0, 51, 153);
        $this->SetFont('helvetica', 'B', 10);
        $this->SetXY(138, 10);
        $this->Cell(0, 5, 'SHIP TO:', 0, 1, 'L');
        
        $this->SetTextColor(60, 60, 60);
        $this->SetFont('helvetica', '', 8.5);
        $this->SetXY(138, 16);
        $lottable3 = $this->header_data['lottable3'] ?? 'Tidak ada alamat';
        $this->MultiCell(60, 4, $lottable3, 0, 'L');
        
        // Title Section dengan background
        $this->SetFillColor(0, 51, 153);
        $this->Rect(0, 38, 210, 12, 'F');
        
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('helvetica', 'B', 18);
        $titleY = 38 + (12 - 10) / 2; 
        $this->SetXY(10, $titleY);
        $this->Cell(190, 10, 'DELIVERY NOTE', 0, 1, 'C');
        
        // Document Info Section - Single Column
        $this->SetY(55);
        $this->SetTextColor(40, 40, 40);
        $this->SetFont('helvetica', '', 9);
        
        $leftX = 10;
        $currentY = 55;
        $lineHeight = 7;
        
        // Document Number
        $this->SetXY($leftX, $currentY);
        $this->SetFont('helvetica', 'B', 9);
        $this->SetTextColor(0, 51, 153);
        $this->Cell(50, $lineHeight, 'Document Number', 0, 0, 'L');
        $this->SetFont('helvetica', '', 9);
        $this->SetTextColor(40, 40, 40);
        $this->Cell(0, $lineHeight, ': ' . ($this->header_data['transaction_id'] ?? 'N/A'), 0, 1, 'L');
        
        // External Order
        $currentY += $lineHeight;
        $this->SetXY($leftX, $currentY);
        $this->SetFont('helvetica', 'B', 9);
        $this->SetTextColor(0, 51, 153);
        $this->Cell(50, $lineHeight, 'External Order Number', 0, 0, 'L');
        $this->SetFont('helvetica', '', 9);
        $this->SetTextColor(40, 40, 40);
        $this->Cell(0, $lineHeight, ': ' . ($this->header_data['order_number'] ?? 'N/A'), 0, 1, 'L');
        
        // Customer
        $currentY += $lineHeight;
        $this->SetXY($leftX, $currentY);
        $this->SetFont('helvetica', 'B', 9);
        $this->SetTextColor(0, 51, 153);
        $this->Cell(50, $lineHeight, 'Customer', 0, 0, 'L');
        $this->SetFont('helvetica', '', 9);
        $this->SetTextColor(40, 40, 40);
        $this->Cell(0, $lineHeight, ': ' . ($this->header_data['customer'] ?? 'N/A'), 0, 1, 'L');
        
        // Warehouse
        $currentY += $lineHeight;
        $this->SetXY($leftX, $currentY);
        $this->SetFont('helvetica', 'B', 9);
        $this->SetTextColor(0, 51, 153);
        $this->Cell(50, $lineHeight, 'Warehouse', 0, 0, 'L');
        $this->SetFont('helvetica', '', 9);
        $this->SetTextColor(40, 40, 40);
        $this->Cell(0, $lineHeight, ': ' . ($this->header_data['wh_name'] ?? 'N/A'), 0, 1, 'L');
        
        // Reference
        $currentY += $lineHeight;
        $this->SetXY($leftX, $currentY);
        $this->SetFont('helvetica', 'B', 9);
        $this->SetTextColor(0, 51, 153);
        $this->Cell(50, $lineHeight, 'Reference', 0, 0, 'L');
        $this->SetFont('helvetica', '', 9);
        $this->SetTextColor(40, 40, 40);
        $this->Cell(0, $lineHeight, ': ' . ($this->header_data['project_name'] ?? 'N/A'), 0, 1, 'L');
        
        // Date
        $currentY += $lineHeight;
        $this->SetXY($leftX, $currentY);
        $this->SetFont('helvetica', 'B', 9);
        $this->SetTextColor(0, 51, 153);
        $this->Cell(50, $lineHeight, 'Date', 0, 0, 'L');
        $this->SetFont('helvetica', '', 9);
        $this->SetTextColor(40, 40, 40);
        $this->Cell(0, $lineHeight, ': ' . ($this->header_data['created_date'] ?? 'N/A'), 0, 1, 'L');
        
        // Separator line
        $this->SetDrawColor(0, 51, 153);
        $this->SetLineWidth(0.5);
        $this->Line(10, 97, 200, 97);
        
        $this->Ln(15);
    }

    public function Footer() {
        // Signature section dengan box modern
        $this->SetY(-75);
        
        // Background untuk signature area
        $this->SetFillColor(250, 250, 250);
        $this->Rect(0, $this->GetY() - 5, 210, 60, 'F');
        
        $this->SetDrawColor(200, 200, 200);
        $this->SetLineWidth(0.3);
        $this->Line(10, $this->GetY() - 3, 200, $this->GetY() - 3);
        
        $this->SetFont('helvetica', 'B', 10);
        $this->SetTextColor(0, 51, 153);
        
        $col1 = 15;
        $col2 = 80;
        $col3 = 145;
        $boxWidth = 55;
        
        // Draw signature boxes
        $this->SetDrawColor(200, 200, 200);
        $this->RoundedRect($col1, $this->GetY(), $boxWidth, 50, 2, '1111', 'D');
        $this->RoundedRect($col2, $this->GetY(), $boxWidth, 50, 2, '1111', 'D');
        $this->RoundedRect($col3, $this->GetY(), $boxWidth, 50, 2, '1111', 'D');
        
        $startY = $this->GetY();
        
        // Titles
        $this->SetXY($col1 + 5, $startY + 3);
        $this->Cell($boxWidth - 10, 6, 'Warehouse SPV', 0, 0, 'C');
        
        $this->SetXY($col2 + 5, $startY + 3);
        $this->Cell($boxWidth - 10, 6, 'Transporter', 0, 0, 'C');
        
        $this->SetXY($col3 + 5, $startY + 3);
        $this->Cell($boxWidth - 10, 6, 'Receiver', 0, 0, 'C');
        
        // Signature lines
        $this->SetDrawColor(150, 150, 150);
        $this->SetLineWidth(0.2);
        $signY = $startY + 28;
        $this->Line($col1 + 10, $signY, $col1 + $boxWidth - 10, $signY);
        $this->Line($col2 + 10, $signY, $col2 + $boxWidth - 10, $signY);
        $this->Line($col3 + 10, $signY, $col3 + $boxWidth - 10, $signY);
        
        // Fields
        $this->SetFont('helvetica', '', 8);
        $this->SetTextColor(80, 80, 80);
        $fieldY = $startY + 32;
        $fieldHeight = 4;
        
        $fields = ['Name', 'Phone', 'Company'];
        
        foreach ($fields as $idx => $field) {
            $currentFieldY = $fieldY + ($idx * $fieldHeight);
            
            $this->SetXY($col1 + 5, $currentFieldY);
            $this->Cell(15, $fieldHeight, $field, 0, 0, 'L');
            $this->Cell(2, $fieldHeight, ':', 0, 0, 'L');
            
            $this->SetXY($col2 + 5, $currentFieldY);
            $this->Cell(15, $fieldHeight, $field, 0, 0, 'L');
            $this->Cell(2, $fieldHeight, ':', 0, 0, 'L');
            
            $this->SetXY($col3 + 5, $currentFieldY);
            $this->Cell(15, $fieldHeight, $field, 0, 0, 'L');
            $this->Cell(2, $fieldHeight, ':', 0, 0, 'L');
        }
        
        // Page number dengan style modern
        $this->SetY(-8);
        $this->SetFont('helvetica', 'I', 8);
        $this->SetTextColor(120, 120, 120);
        $current_page = $this->getAliasNumPage();
        $total_pages = $this->getAliasNbPages();
        $this->Cell(0, 5, 'Page ' . $current_page . ' of ' . $total_pages, 0, 0, 'C');
        
        // Footer border
        $this->SetDrawColor(0, 51, 153);
        $this->SetLineWidth(0.5);
        $this->Line(0, 287, 210, 287);
    }
}

// Inisialisasi PDF
$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->setDeliveryOrder($order_number);
$pdf->setCustomHeaderData($row_header);
$pdf->SetMargins(10, 105, 10);
$pdf->SetAutoPageBreak(TRUE, 80);
$pdf->AddPage();

// Function untuk table header dengan desain modern
function addTableHeader($pdf) {
    $pdf->SetFillColor(0, 51, 153);
    $pdf->SetDrawColor(0, 51, 153);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetLineWidth(0.3);
    
    $pdf->SetX(10);
    $pdf->Cell(12, 9, 'No', 1, 0, 'C', true);
    $pdf->Cell(50, 9, 'Item Code', 1, 0, 'L', true);
    $pdf->Cell(95, 9, 'Item Description', 1, 0, 'L', true);
    $pdf->Cell(20, 9, 'Qty', 1, 0, 'C', true);
    $pdf->Cell(13, 9, 'UoM', 1, 1, 'C', true);
    
    $pdf->SetTextColor(40, 40, 40);
    $pdf->SetFont('helvetica', '', 9);
}

addTableHeader($pdf);

// Proses data dengan SUM logic
$details_raw = [];
while ($row = $result_details->fetch_assoc()) {
    $details_raw[] = $row;
}

$details = [];
foreach ($details_raw as $row) {
    $key = $row['item_code'] . '|' . $row['item_description'] . '|' . $row['uom'];
    
    if (isset($details[$key])) {
        $details[$key]['qty'] += $row['qty'];
    } else {
        $details[$key] = $row;
    }
}

$details = array_values($details);

// Isi tabel dengan alternating colors
$pdf->SetDrawColor(220, 220, 220);
$pdf->SetLineWidth(0.2);
$no = 1;
$totalQty = 0;

foreach ($details as $row) {
    $itemCode = $row['item_code'];
    $itemDesc = $row['item_description'];
    $qty = $row['qty'];
    $totalQty += $qty;
    
    // Hitung tinggi baris berdasarkan description (untuk auto-wrap)
    $pdf->SetFont('helvetica', '', 9);
    $descHeight = $pdf->getStringHeight(93, $itemDesc);
    $rowHeight = max(7, $descHeight + 1);
    
    $currentY = $pdf->GetY();
    $pageBreakTrigger = $pdf->getPageHeight() - 80;
    
    if ($currentY + $rowHeight > $pageBreakTrigger) {
        $pdf->AddPage();
        addTableHeader($pdf);
    }
    
    // Background putih bersih untuk semua baris
    $pdf->SetFillColor(255, 255, 255);
    
    $startY = $pdf->GetY();
    
    // No
    $pdf->SetXY(10, $startY);
    $pdf->Cell(12, $rowHeight, $no++, 1, 0, 'C', true);
    
    // Item Code
    $pdf->SetXY(22, $startY);
    $pdf->Cell(50, $rowHeight, $itemCode, 1, 0, 'L', true);
    
    // Item Description - PERBAIKAN: Gunakan Cell() seperti kolom lainnya
    $pdf->SetXY(72, $startY);
    $pdf->Cell(95, $rowHeight, '', 1, 0, 'L', true); // Border dan background
    
    // Isi text description dengan padding
    $pdf->SetXY(73, $startY + 0.5);
    $pdf->MultiCell(93, 3.5, $itemDesc, 0, 'L', false, 0);
    
    // Qty
    $pdf->SetXY(167, $startY);
    $pdf->Cell(20, $rowHeight, number_format($qty, 2), 1, 0, 'C', true);
    
    // UoM
    $pdf->SetXY(187, $startY);
    $pdf->Cell(13, $rowHeight, $row['uom'], 1, 0, 'C', true);
    
    // Pindah ke baris berikutnya
    $pdf->SetY($startY + $rowHeight);
}

// Total row
$pdf->SetFillColor(240, 248, 255);
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetTextColor(0, 51, 153);
$pdf->SetX(10);
$pdf->Cell(157, 8, 'TOTAL ITEMS: ' . count($details), 1, 0, 'R', true);
$pdf->Cell(20, 8, number_format($totalQty, 2), 1, 0, 'C', true);
$pdf->Cell(13, 8, '', 1, 1, 'C', true);

if (empty($details)) {
    $pdf->SetX(10);
    $pdf->SetFillColor(255, 248, 248);
    $pdf->Cell(190, 8, 'Tidak ada data untuk ditampilkan', 1, 1, 'C', true);
}

// Output PDF
$pdf->Output('delivery_note_' . $order_number . '.pdf', 'I');
$conn->close();
?>