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
    <header class="page-header page-header-compact page-header-light border-bottom bg-white mb-4">
    <div class="container-xl px-4">
        <div class="page-header-content">
            <div class="row align-items-center justify-content-between pt-3">
                <div class="col-auto mb-3">
                    
                </div>
                <div class="col-12 col-xl-auto mb-3">
                    <button class="btn btn-sm btn-light text-primary active me-2" id="dayBtn"></button>
                    <button class="btn btn-sm btn-light text-primary me-2" id="monthBtn"></button>
                    <button class="btn btn-sm btn-light text-primary" id="yearBtn"></button>
                    <button class="btn btn-sm btn-light text-primary" id="timeBtn"></button>
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
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#skuModal">
                        <i data-feather="plus" style="width: 14px; height: 14px;"></i> Add New
                    </button>
                    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#bulkUploadModal">
                        <i data-feather="upload" style="width: 14px; height: 14px;"></i> Bulk Upload
                    </button>
                    <button class="btn btn-success btn-sm" id="exportExcelSKU">
                        <i data-feather="file-text" style="width:14px; height:14px;"></i> Export Excel
                    </button>
                </div>
            </div>
            <div class="card-body" style="overflow-x: auto;">
                <div class="table-responsive" style="overflow-x: auto;">
                    <table class="table table-striped table-hover table-bordered compact-action" id="tabelSKU"
                        style="min-width: 100%; white-space: nowrap;">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;"><strong>No</th>
                                <th style="width: 150px;"><strong>Item Code</strong></th>
                                <th style="width: 250px;"><strong>Item Description</th>
                                <th style="width: 80px;"><strong>Volume</th>
                                <th style="width: 80px;"><strong>UOM</strong></th>
                                <th style="width: 120px;"><strong>Project</th>
                                <th style="width: 120px;"><strong>Create Date</th>
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
                                    <th class="action-column"></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>


<!-- Modal Tambah SKU -->
<div class="modal fade" id="skuModal" tabindex="-1" aria-labelledby="skuModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="skuModalLabel">Tambah Master SKU</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="skuForm" action="php/proses_sku.php" method="POST">
                    <div class="mb-2">
                        <div class="col-md-12">
                            <label class="form-label">Item Code</label>
                            <input type="text" name="item_code" class="form-control" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Item Description</label>
                            <input type="text" name="item_description" class="form-control" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Volume</label>
                            <input type="number" step="any" name="volume" class="form-control" placeholder="Masukkan volume" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">UOM</label>
                            <input type="text" name="uom" class="form-control" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Project Name</label>
                            <select name="project" class="form-select select2" required>
                                <option value="">Pilih Project</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Bulk Upload -->
<div class="modal fade" id="bulkUploadModal" tabindex="-1" aria-labelledby="bulkUploadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkUploadModalLabel">Bulk Upload Master SKU</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="bulkUploadForm" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Unggah File Excel</label>
                        <input type="file" name="excel_file" class="form-control" accept=".xlsx,.xls" required>
                        <small class="form-text text-muted">
                            Format Excel: Kolom harus berisi item_code, item_description, uom, project (urutan wajib). 
                            <a href="modules/export_sku_template.php" target="_blank">Unduh template Excel</a>.
                        </small>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Unggah</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit SKU -->
<div class="modal fade" id="editSkuModal" tabindex="-1" aria-labelledby="editSkuModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editSkuModalLabel">Edit Master SKU</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editSkuForm" method="POST">
                    <input type="hidden" name="id" id="edit_id">
                    
                    <div class="row mb-2">
                        <div class="col-md-12">
                            <label class="form-label">Item Code</label>
                            <input type="text" id="edit_item_code" class="form-control" readonly style="background-color: #e9ecef;">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Project Name <span class="text-danger">*</span></label>
                            <select name="project" id="edit_project" class="form-select select2" required>
                                <option value="">Pilih Project</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Item Description <span class="text-danger">*</span></label>
                            <input type="text" name="item_description" id="edit_item_description" class="form-control" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Volume <span class="text-danger">*</span></label>
                            <input type="number" step="any" name="volume" id="edit_volume" class="form-control" placeholder="Masukkan volume" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">UOM <span class="text-danger">*</span></label>
                            <input type="text" name="uom" id="edit_uom" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Update Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>