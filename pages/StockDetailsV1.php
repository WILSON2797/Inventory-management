<?php
// CRITICAL: Pastikan session_start() ada di awal
session_start();

// Ambil data dari session
$role = isset($_SESSION['role']) ? $_SESSION['role'] : 'user';
$wh_name = isset($_SESSION['wh_name']) ? $_SESSION['wh_name'] : '';
$wh_id = isset($_SESSION['wh_id']) ? $_SESSION['wh_id'] : '';
$project_name = isset($_SESSION['project_name']) ? $_SESSION['project_name'] : '';

// Set timezone
date_default_timezone_set('Asia/Jakarta');
?>

<main>
    <header class="page-header page-header-compact page-header-light border-bottom bg-white mb-4">
        <div class="container-xl px-4">
            <div class="page-header-content">
                <div class="row align-items-center justify-content-between pt-3">
                    <div class="col-auto mb-3">
                        <h1 class="page-header-title">
                            <div class="page-header-icon"><i data-feather="archive"></i></div>
                            Stock Details
                        </h1>
                    </div>
                    <?php date_default_timezone_set('Asia/Jakarta'); ?>
                    <div class="col-12 col-xl-auto mb-3">
                        <button class="btn btn-sm btn-light text-primary active me-2"><?php echo date('d'); ?></button>
                        <button class="btn btn-sm btn-light text-primary me-2"><?php echo date('F'); ?></button>
                        <button class="btn btn-sm btn-light text-primary"><?php echo date('Y'); ?></button>
                        <button class="btn btn-sm btn-light text-primary"><?php echo date('H:i  T'); ?></button>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main page content-->
    <div class="container-fluid px-4 mt-4">
        <div class="card mb-4">
            <div class="card-header">
                Inbound Data Table
                <div class="float-end">
                    <button class="btn btn-success btn-sm" id="exportExcelStock">
                        <i data-feather="file-text" style="width:14px; height:14px;"></i> Export Excel
                    </button>
                </div>
            </div>
            <div class="card-body" style="overflow-x: auto;">
                <div class="table-responsive" style="overflow-x: auto;">
                    <table class="table table-striped table-hover table-bordered compact-action" id="tabelStock"
                        style="min-width: 100%; white-space: nowrap;">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;"><strong>No</th>
                                <th style="width: 150px;"><strong>PO Number</strong></th>
                                <th style="width: 150px;"><strong>Supplier</strong></th>
                                <th style="width: 150px;"><strong>Packing List</strong></th>
                                <th style="width: 150px;"><strong>Item Code</strong></th>
                                <th style="width: 250px;"><strong>Item Description</th>
                                <th style="width: 80px;"><strong>Qty Inbound</th>
                                <th style="width: 80px;"><strong>Qty Booked</th>
                                <th style="width: 80px;"><strong>Qty Out</th>
                                <th style="width: 80px;"><strong>Qty Onhand</th>
                                <th style="width: 80px;"><strong>Qty Balance</th>
                                <th style="width: 80px;"><strong>UOM</strong></th>
                                <th style="width: 120px;"><strong>Locator</th>
                                <th style="width: 120px;"><strong>Stock Type</th>
                                <th style="width: 120px;"><strong>WH Name</th>
                                <th style="width: 120px;"><strong>Last Update</th>
                                <th style="width: 120px;">Status</th>
                                <th style="width: 120px;">Reason Freeze</th>
                                <th style="width: 100px;"><strong>Action</th>
                            </tr>
                            <tr class="table-search">
                                <th><input type="text" class="form-control form-control-sm"></th>
                                    <th><input type="text" class="form-control form-control-sm"></th>
                                    <th><input type="text" class="form-control form-control-sm"></th>
                                    <th><input type="text" class="form-control form-control-sm"></th>
                                    <th><input type="text" class="form-control form-control-sm"></th>
                                    <th><input type="text" class="form-control form-control-sm"></th>
                                    <th><input type="text" class="form-control form-control-sm"></th>
                                    <th><input type="text" class="form-control form-control-sm"></th>
                                    <th><input type="text" class="form-control form-control-sm"></th>
                                    <th><input type="text" class="form-control form-control-sm"></th>
                                    <th><input type="text" class="form-control form-control-sm"></th>
                                    <th><input type="text" class="form-control form-control-sm"></th>
                                    <th><input type="text" class="form-control form-control-sm"></th>
                                    <th><input type="text" class="form-control form-control-sm"></th>
                                    <th><input type="text" class="form-control form-control-sm"></th>
                                    <th><input type="text" class="form-control form-control-sm"></th>
                                    <th><input type="text" class="form-control form-control-sm"></th>
                                    <th><input type="text" class="form-control form-control-sm"></th>
                                    <th></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
     <!-- Modal Alasan Freeze -->
<div class="modal fade" id="freezeReasonModal" tabindex="-1" aria-labelledby="freezeReasonLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-warning text-dark">
        <h5 class="modal-title" id="freezeReasonLabel">Alasan Freeze Stock</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="freezeReasonForm">
          <input type="hidden" id="freezeStockId">
          <div class="mb-3">
            <label for="freezeReason" class="form-label">Masukkan alasan kenapa stock di-freeze:</label>
            <textarea id="freezeReason" class="form-control" rows="3" placeholder="Contoh: Stock tidak ditemukan di locator"></textarea>
          </div>
          <button type="submit" class="btn btn-warning w-100">Simpan Alasan</button>
        </form>
      </div>
    </div>
  </div>
</div>
</main>


