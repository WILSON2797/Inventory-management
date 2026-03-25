<?php
session_start();
include '../php/config.php'; // Sesuaikan path ke file config Anda

// Cek autentikasi user
if (!isset($_SESSION['username'])) {
    die('User tidak terautentikasi');
}

// Cek parameter delivery_order
if (!isset($_GET['order_number'])) {
    die('Delivery Order tidak ditemukan');
}

// Sanitasi input
$order_number = mysqli_real_escape_string($conn, $_GET['order_number']);
$wh_name = $_SESSION['wh_name'] ?? ''; // Ambil wh_id dari session jika ada

// Query untuk data header (outbound_orders), termasuk address, reference, dan customer
$query_header = "SELECT created_date, wh_name, customer, transaction_sequence, project_name FROM allocated WHERE order_number = '$order_number' AND wh_name = '$wh_name' LIMIT 1";
$result_header = $conn->query($query_header);

// Periksa hasil query
if ($result_header === false) {
    ob_end_clean();
    die("Query gagal: " . $conn->error);
}

if ($result_header->num_rows === 0) {
    ob_end_clean();
    die('Order Number tidak ditemukan di tabel allocated');
}
$row_header = $result_header->fetch_assoc();

// Query untuk data detail (allocated) - PERBAIKAN: tidak override $result_header
$query_details = "SELECT transaction_sequence, order_number, customer, item_code, item_description, qty_picking, uom, locator_picking, created_date, wh_name, project_name FROM allocated WHERE order_number = '$order_number' AND wh_name = '$wh_name'";
$result_details = $conn->query($query_details);

// Periksa apakah ada data details
if ($result_details === false) {
    die("Query details gagal: " . $conn->error);
}

if ($result_details->num_rows === 0) {
    die("Tidak ada detail items untuk order: $order_number");
}

// Include TCPDF
require_once('../vendor/tecnickcom/tcpdf/tcpdf.php'); // Sesuaikan path ke TCPDF

// Extend TCPDF untuk menambahkan header dan footer kustom
class MYPDF extends TCPDF {
    protected $order_number;
    protected $header_data;

    public function setDeliveryOrder($order_number) {
        $this->order_number = $order_number;
    }

    // Simpan data header ke properti kelas
    public function setCustomHeaderData($header_data) {
        $this->header_data = $header_data;
    }

    // Override Header untuk menambahkan header di setiap halaman
    public function Header() {
        // Logo
        $this->Image('../assets/img/Fis.jpg', 5, 4, 25);

        // Tulisan perusahaan di bawah logo
        $this->SetTextColor(0, 0, 128);
        $this->SetFont('helvetica', 'BI', 12);
        $this->SetXY(5, 21);
        $this->Cell(0, 10, 'PT.Fan Indonesia Sejahtera', 0, 1, 'L');
        $this->SetTextColor(0, 0, 0);
        $this->SetFont('courier', '', 8);
        $this->SetXY(5, 26);
        $this->Cell(0, 10, 'Rukan Gardenia Blok RF12', 0, 1, 'L');
        $this->SetXY(5, 29);
        $this->Cell(0, 10, 'Pesona Metropolitan Bekasi ,Jawabarat', 0, 1, 'L');

        // Alamat Pengiriman
        // $this->SetXY(140, 32);
        // $this->SetFont('courier', 'BI', 9);
        // $this->Cell(0, 5, 'Delivery Address :', 0, 1, 'L');
        // $this->SetXY(140, 37);
        // $this->SetFont('courier', '', 8.5);
        // $address = $this->header_data['destination'] ?? 'Tidak ada alamat';
        // $this->MultiCell(70, 4, $address, 0, 'L');

        // Tambahkan Tulisan Pick Tikect
        $this->SetFont('courier', 'B', 14);
        $this->SetXY(90, 45);
        $this->Cell(30, 10, 'Pick Ticket', 0, 1, 'C');

        // Field header
        $this->SetFont('courier', 'B', 9);
        $this->SetXY(5, 55);
        $this->SetLineWidth(0.2);
        $this->Line(3, $this->GetY(), 207, $this->GetY());

        $labelWidth = 30;
        $this->Cell($labelWidth, 8, 'Order Number', 0, 0, 'L');
        $this->Cell(5, 8, ':', 0, 0, 'C');
        $this->Cell(0, 8, $this->order_number, 0, 1, 'L');
        $this->Ln(-3);

        $this->SetX(5);
        $this->Cell($labelWidth, 8, 'Reff Number', 0, 0, 'L');
        $this->Cell(5, 8, ':', 0, 0, 'C');
        $this->Cell(0, 8, ($this->header_data['transaction_sequence'] ?? 'N/A'), 0, 1, 'L');
        $this->Ln(-3);

        $this->SetX(5);
        $this->Cell($labelWidth, 8, 'Customer', 0, 0, 'L');
        $this->Cell(5, 8, ':', 0, 0, 'C');
        $this->Cell(0, 8, ($this->header_data['customer'] ?? 'N/A'), 0, 1, 'L');
        $this->Ln(-3);

        $this->SetX(5);
        $this->Cell($labelWidth, 8, 'WH Name', 0, 0, 'L');
        $this->Cell(5, 8, ':', 0, 0, 'C');
        $this->Cell(0, 8, ($this->header_data['wh_name'] ?? 'N/A'), 0, 1, 'L');
        $this->Ln(-3);

        $this->SetX(5);
        $this->Cell($labelWidth, 8, 'Project Name', 0, 0, 'L');
        $this->Cell(5, 8, ':', 0, 0, 'C');
        $this->Cell(0, 8, ($this->header_data['project_name'] ?? 'N/A'), 0, 1, 'L');
        $this->Ln(-3);

        $this->SetX(5);
        $this->Cell($labelWidth, 8, 'Create Date', 0, 0, 'L');
        $this->Cell(5, 8, ':', 0, 0, 'C');
        $this->Cell(0, 8, ($this->header_data['created_date'] ?? 'N/A'), 0, 1, 'L');
        $this->Ln(0);

        $this->SetLineWidth(0.2);
        $this->Line(3, $this->GetY(), 207, $this->GetY());
        // $this->Ln(30);
    }

    // Buat footer dengan tanda tangan
    public function Footer() {
        $this->SetY(-70);
        $this->SetFont('courier', 'B', 9);
        $col1 = 5;
        $col2 = 85;
        $col3 = 165;

        $this->SetX($col1);
        $this->Cell(60, 10, 'Yang Menyerahkan', 0, 0, 'L');
        $this->SetX($col2);
        $this->Cell(60, 10, 'Transporter', 0, 0, 'L');
        $this->SetX($col3);
        $this->Cell(60, 10, 'Receiver', 0, 1, 'L');

        $this->Ln(20);
        $this->SetX($col1);
        $this->Cell(60, 10, '------------------', 0, 0, 'L');
        $this->SetX($col2);
        $this->Cell(60, 10, '------------------', 0, 0, 'L');
        $this->SetX($col3);
        $this->Cell(60, 10, '------------------', 0, 1, 'L');

        $this->Ln(5);
        $this->SetFont('courier', '', 9);
        $this->SetX($col1);
        $this->Cell(60, 1, 'Nama :', 0, 0, 'L');
        $this->SetX($col2);
        $this->Cell(60, 1, 'Nama :', 0, 0, 'L');
        $this->SetX($col3);
        $this->Cell(60, 1, 'Nama :', 0, 1, 'L');

        $this->SetX($col1);
        $this->Cell(60, 1, 'Phone :', 0, 0, 'L');
        $this->SetX($col2);
        $this->Cell(60, 1, 'Phone :', 0, 0, 'L');
        $this->SetX($col3);
        $this->Cell(60, 1, 'Phone :', 0, 1, 'L');

        $this->SetX($col1);
        $this->Cell(60, 1, 'Company :', 0, 0, 'L');
        $this->SetX($col2);
        $this->Cell(60, 1, 'Company :', 0, 0, 'L');
        $this->SetX($col3);
        $this->Cell(60, 1, 'Company :', 0, 1, 'L');

        $this->SetY(-2);
        $this->SetFont('helvetica', 'italic', 8);
        $current_page = $this->getAliasNumPage();
        $total_pages = $this->getAliasNbPages();
        $this->Cell(0, 10, 'Page ' . $current_page . ' of ' . $total_pages, 0, 0, 'R');
    }
}

// Inisialisasi TCPDF dengan class kustom
$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->setDeliveryOrder($order_number);
$pdf->setCustomHeaderData($row_header);
$pdf->SetMargins(5, 90, 5);
$pdf->SetAutoPageBreak(TRUE, 70);
$pdf->AddPage();

$pdf->Ln(10); // Add 15mm space before table

// Fungsi untuk header tabel
function addTableHeader($pdf) {
    $pdf->SetLineStyle(array('dash' => '2,1', 'width' => 0.15));
    $pdf->SetDrawColor(0, 0, 0);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('courier', 'B', 9);
    
    $pdf->SetX(3);
    $pdf->Cell(10, 8, 'No', 'LTRB', 0, 'C', true);
    $pdf->Cell(45, 8, 'Item Code', 'LTRB', 0, 'C', true);
    $pdf->Cell(85, 8, 'Item Description', 'LTRB', 0, 'C', true);
    $pdf->Cell(18, 8, 'Qty', 'LTRB', 0, 'C', true);
    $pdf->Cell(18, 8, 'UoM', 'LTRB', 0, 'C', true);
    $pdf->Cell(29, 8, 'Locator', 'LTRB', 1, 'C', true);
    
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('courier', '', 9);
}

// Tambahkan header tabel
addTableHeader($pdf);

// PERBAIKAN: Ambil semua data ke array terlebih dahulu
$details = [];
while ($row = $result_details->fetch_assoc()) {
    $details[] = $row;
}

// Debug: Tampilkan jumlah data yang ditemukan
error_log("Jumlah data ditemukan: " . count($details));

// Isi tabel
$pdf->SetLineStyle(array('dash' => '2,1', 'width' => 0.15));
$pdf->SetDrawColor(0, 0, 0);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('courier', '', 9);
$no = 1;

foreach ($details as $row) {
    // PERBAIKAN: Gunakan variable yang benar
    $itemCode = $row['item_code'];
    $itemDesc = $row['item_description'];
    
    // Hitung tinggi baris berdasarkan description yang paling panjang
    $rowHeight = max(8, $pdf->getStringHeight(85, $itemDesc));
    
    // Cek apakah perlu pindah halaman
    $currentY = $pdf->GetY();
    $pageBreakTrigger = $pdf->getPageHeight() - 70;
    
    if ($currentY + $rowHeight > $pageBreakTrigger) {
        $pdf->AddPage();
        $pdf->Ln(5);
        addTableHeader($pdf);
    }
    
    // Posisi awal baris
    $y = $pdf->GetY();
    
    // Set background putih
    $pdf->SetFillColor(255, 255, 255);
    
    // Nomor urut
    $pdf->SetXY(3, $y);
    $pdf->Cell(10, $rowHeight, $no++, 'LTRB', 0, 'C', true);
    
    // Item Code
    $pdf->SetXY(13, $y);
    $pdf->Cell(45, $rowHeight, $itemCode, 'LTRB', 0, 'L', true);
    
    // Item Description
    $pdf->SetXY(58, $y);
    $pdf->Cell(85, $rowHeight, $itemDesc, 'LTRB', 0, 'L', true);
    
    // Qty
    $pdf->SetXY(143, $y);
    $pdf->Cell(18, $rowHeight, number_format($row['qty_picking'], 2), 'LTRB', 0, 'C', true);
    
    // UOM
    $pdf->SetXY(161, $y);
    $pdf->Cell(18, $rowHeight, $row['uom'], 'LTRB', 0, 'C', true);
    
    // Locator
    $pdf->SetXY(179, $y);
    $pdf->Cell(29, $rowHeight, $row['locator_picking'], 'LTRB', 0, 'C', true);
    
    // Pindah ke baris berikutnya
    $pdf->SetY($y + $rowHeight);
}

// Jika tidak ada data, tampilkan pesan
if (empty($details)) {
    $pdf->SetXY(3, $pdf->GetY());
    $pdf->Cell(205, 8, 'Tidak ada data untuk ditampilkan', 'LTRB', 1, 'C');
}

// Output PDF
$pdf->Output('delivery_order_' . $order_number . '.pdf', 'I');
$conn->close();
?>