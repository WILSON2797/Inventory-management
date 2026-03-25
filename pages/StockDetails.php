<?php
// CRITICAL: Pastikan session_start() ada di awal
session_start();

if (!isset($_SESSION['username'])) {
    http_response_code(401);
    exit('Unauthorized');
}

// Refresh last_activity
$_SESSION['last_activity'] = time();

// Ambil data dari session
$role = isset($_SESSION['role']) ? $_SESSION['role'] : 'user';
$wh_name = isset($_SESSION['wh_name']) ? $_SESSION['wh_name'] : '';
$wh_id = isset($_SESSION['wh_id']) ? $_SESSION['wh_id'] : '';
$project_name = isset($_SESSION['project_name']) ? $_SESSION['project_name'] : '';

// Set timezone
date_default_timezone_set('Asia/Jakarta');
?>

<main>
    <div class="container-fluid px-4 mt-4">
      <ul class="nav nav-tabs mb-3" id="stockTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="stock-data-tab" data-bs-toggle="tab" data-bs-target="#stock-data" 
                        type="button" role="tab" aria-controls="stock-data" aria-selected="true">
                    <i data-feather="archive" style="width: 14px; height: 14px;"></i> Stock Details
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="movement-log-tab" data-bs-toggle="tab" data-bs-target="#movement-log" 
                        type="button" role="tab" aria-controls="movement-log" aria-selected="false">
                    <i class="fa-solid fa-clock-rotate-left"></i> History Movement Locator Log
                </button>
            </li>
        </ul>
        <div class="tab-content" id="stockTabsContent">
            <!-- Stock Details Tab -->
            <div class="tab-pane fade show active" id="stock-data" role="tabpanel" aria-labelledby="stock-data-tab">
                <div class="card mb-4">
                    <div class="card-header">
                        Stock Details Data Table
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
                                        <th style="width: 120px;"><strong>Status</th>
                                        <th style="width: 120px;"><strong>Reason Freeze</th>
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

            <!-- Movement Locator Log Tab -->
            <div class="tab-pane fade" id="movement-log" role="tabpanel" aria-labelledby="movement-log-tab">
                <div class="card mb-4">
                    <div class="card-header">
                        Movement Locator Log Data Table
                        <div class="float-end">
                            <button class="btn btn-success btn-sm" id="exportExcelMovementLog">
                                <i data-feather="file-text" style="width:14px; height:14px;"></i> Export Excel
                            </button>
                        </div>
                    </div>
                    <div class="card-body" style="overflow-x: auto;">
                        <div class="table-responsive" style="overflow-x: auto;">
                            <table class="table table-striped table-hover table-bordered compact-action" id="tabelMovementLocatorLog"
                                style="min-width: 100%; white-space: nowrap;">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 50px;"><strong>No</th>
                                        <th style="width: 250px;"><strong>Item Code</strong></th>
                                        <th style="width: 250px;"><strong>Item Description</th>
                                        <th style="width: 120px;"><strong>From Locator</th>
                                        <th style="width: 250px;"><strong>To Locator</th>
                                        <th style="width: 80px;"><strong>Qty Movement</th>
                                        <th style="width: 250px;"><strong>WH Name</th>
                                        <th style="width: 250px;"><strong>Project Name</th>
                                        <th style="width: 250px;"><strong>Packing List</th>
                                        <th style="width: 250px;"><strong>Reason</th>
                                        <th style="width: 250px;"><strong>Move By</th>
                                        <th style="width: 250px;"><strong>Move Date</th>
                                        <th style="width: 250px;"><strong>Action Type</th>
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
                                        
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
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
                                <textarea id="freezeReason" class="form-control" rows="3"
                                    placeholder="Contoh: Stock tidak ditemukan di locator"></textarea>
                            </div>
                            <button type="submit" class="btn btn-warning w-100">Simpan Alasan</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Move Locator -->
        <div class="modal fade" id="moveLocatorModal" tabindex="-1" aria-labelledby="moveLocatorModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content shadow-lg">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title fs-5" id="moveLocatorModalLabel">
                            <i class="fa-solid fa-map-marker-alt me-2"></i>Move Locator
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <form id="moveLocatorForm">
                            <input type="hidden" id="moveStockId">
                            <div class="card bg-light border-0 shadow-sm mb-4">
                                <div class="card-body p-4">
                                    <h6 class="card-title mb-3 text-primary">
                                        <i class="fa-solid fa-info-circle me-2"></i>Informasi Item
                                    </h6>
                                    <table class="table table-sm table-borderless mb-0">
                                        <tbody>
                                            <tr>
                                                <td width="40%" class="text-muted"><strong>Item Code:</strong></td>
                                                <td id="moveItemCode" class="fw-semibold"></td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted"><strong>Description:</strong></td>
                                                <td id="moveItemDesc"></td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted"><strong>Current Locator:</strong></td>
                                                <td><span class="badge bg-secondary fs-6 px-3 py-2" id="moveCurrentLocator"></span></td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted"><strong>Balance Quantity:</strong></td>
                                                <td><span class="badge bg-info text-dark fs-6 px-3 py-2" id="moveQty"></span></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="moveQtyToTransfer" class="form-label fw-semibold">
                                        <i class="fa-solid fa-boxes-packing text-success me-2"></i>Quantity Move <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" class="form-control form-control-lg" id="moveQtyToTransfer" min="1" 
                                        placeholder="Masukkan jumlah yang akan dipindah" required>
                                    <small class="form-text text-muted">Masukkan jumlah unit yang ingin dipindahkan.</small>
                                </div>
                                <div class="col-md-6">
                                    <label for="moveNewLocator" class="form-label fw-semibold">
                                        <i class="fa-solid fa-location-dot text-danger me-2"></i>Move To Locator <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select form-select-lg" id="moveNewLocator" required>
                                        <option value="">-- Pilih Locator Tujuan --</option>
                                    </select>
                                    <small class="form-text text-muted">Pilih locator tujuan untuk memindahkan item.</small>
                                </div>
                            </div>
                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <label for="moveReason" class="form-label fw-semibold">
                                        <i class="fa-solid fa-comment-dots me-2"></i>Reason Movement <span class="text-danger">*</span>
                                    </label>
                                    <textarea class="form-control form-control-lg" id="moveReason" rows="5"
                                        placeholder="Contoh: Reorganisasi gudang, Konsolidasi stock, dll" required></textarea>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer border-top bg-light p-3">
                        <button type="button" class="btn btn-secondary btn-lg px-4" data-bs-dismiss="modal">
                            <i class="fa-solid fa-times me-2"></i>Cancel
                        </button>
                        <button type="submit" form="moveLocatorForm" class="btn btn-primary btn-lg px-4">
                            <i class="fa-solid fa-check me-2"></i>Save
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
