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
                Allocated Items Data
                <div class="float-end">
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#allocatedModal">
                        <i data-feather="plus" style="width: 14px; height: 14px;"></i> Add New
                    </button>
                    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#bulkAllocatedModal">
                        <i data-feather="upload" style="width: 14px; height: 14px;"></i> Bulk Upload
                    </button>
                    <button class="btn btn-success btn-sm" id="exportExcelAllocatedItems">
                        <i data-feather="file-text" style="width:14px; height:14px;"></i> Export Excel
                    </button>
                </div>
            </div>
            <div class="card-body" style="overflow-x: auto;">

                <!-- Data Table -->
                <div class="table-responsive" style="overflow-x: auto;">
                    <table class="table table-striped table-hover table-bordered" id="tabelAllocated"
                        style="min-width: 100%; white-space: nowrap;">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 80px;"><strong>No</th>
                                <th style="width: 150px;"><strong>Order Number</strong></th>
                                <th style="width: 150px;"><strong>Customer</strong></th>
                                <th style="width: 150px;"><strong>Lot#1</strong></th>
                                <th style="width: 150px;"><strong>Lot#2</strong></th>
                                <th style="width: 200px;"><strong>Destination</th>
                                <th style="width: 150px;"><strong>Status</th>
                                <th style="width: 150px;"><strong>Allocated Date</strong></th>
                                <th style="width: 150px;"><strong>Create By</th>
                                <th style="width: 150px;"><strong>WH Name</th>
                                <th style="width: 300px;"><strong>Action</th>
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
                                <th></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Modal Tambah Allocated -->
<div class="modal fade" id="allocatedModal" tabindex="-1" aria-labelledby="allocatedModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xxl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="allocatedModalLabel">Tambah Data Allocated Material</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="allocatedForm" action="modules/proses_allocated.php" method="POST">
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label class="form-label">Order Number <span style="color: red;">(Klik Generated Jika Tidak
                                    Punya Order Number)</span></label>
                            <div class="input-group">
                                <input type="text" name="order_number" id="order_number" class="form-control" required>
                                <button type="button" class="btn btn-outline-primary btn-sm" id="generateOrderNumber">
                                    <i class="fas fa-cog me-1"></i>Generate Order Number
                                </button>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Customer</label>
                            <input type="text" name="customer" class="form-control" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label class="form-label">Destination <span style="color: red;">*</span></label>
                            <input type="text" name="lottable3" class="form-control" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Lottable1 <span class="text-muted"> (Optional)</span></label>
                            <input type="text" name="lottable1" class="form-control">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Lottable2 <span class="text-muted"> (Optional)</span></label>
                            <input type="text" name="lottable2" class="form-control">
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered item-table" id="allocatedItemTable">
                            <thead>
                                <tr>
                                    <th style="width: 300px;">Item Code</th>
                                    <th>Item Description</th>
                                    <th style="width: 150px;">Qty Picking</th>
                                    <th style="width: 100px;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="allocatedItemRows">
                                <tr>
                                    <td>
                                        <select name="items[0][item_code]" class="form-select select2 item-code"
                                            data-index="0" required>
                                            <option value="">Pilih Item Code</option>
                                        </select>
                                    </td>
                                    <td><input type="text" name="items[0][item_description]"
                                            class="form-control item-description" readonly></td>
                                    <td><input type="number" step="0.01" name="items[0][qty_picking]"
                                            class="form-control" required></td>
                                    <td><button type="button"
                                            class="btn btn-sm removeAllocatedRow">
                                        <i data-feather="trash-2" class="text-danger"></i>
                                        </button>
                                        </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-success btn-sm mb-3" id="addAllocatedRow">Tambah Item</button>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Bulk Upload Allocated -->
<div class="modal fade" id="bulkAllocatedModal" tabindex="-1" aria-labelledby="bulkAllocatedModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkAllocatedModalLabel">
                    <i class="fas fa-cloud-upload-alt me-2"></i>Bulk Upload Allocated Material
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="download-template">
                    <strong>Upload File xlsx</strong>
                    <p class="mb-2 mt-2"></p>
                    <button onclick="window.open('modules/export_template_allocated.php', '_blank')"
                        class="btn btn-success btn-sm">
                        <i class="fas fa-file-excel me-1"></i>Download Template
                    </button>
                </div>

                <form id="bulkAllocatedForm" action="modules/Proses_BulkAllocated.php" method="POST"
                    enctype="multipart/form-data">
                    <div class="upload-section" id="uploadSection">
                        <div class="text-center">
                            <i class="fas fa-file-upload upload-icon"></i>
                            <div class="upload-text">
                                <strong>Drop your file here, or Browse</strong>
                            </div>
                            <div class="upload-subtext">
                                Maksimum file size 50MB
                            </div>
                            <button type="button" class="btn btn-browse" id="browseButton">
                                <i class="fas fa-folder-open me-2"></i>Browse File Here
                            </button>
                            <input type="file" name="xlxs" class="file-input" id="fileInput" accept=".xlsx,.xls"
                                required>
                            <div class="format-info">
                                <span>Supported formats:</span>
                                <span class="format-badge">xlsx</span>
                            </div>
                        </div>
                    </div>

                    <div class="file-info" id="fileInfo">
                        <div class="file-details">
                            <div>
                                <div class="file-name" id="fileName"></div>
                                <div class="file-size" id="fileSize"></div>
                            </div>
                            <button type="button" class="remove-file" id="removeFile">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="progress-container" id="progressContainer">
                            <div class="progress">
                                <div class="progress-bar bg-primary" id="progressBar" style="width: 0%"></div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail Items -->
<div class="modal fade" id="viewItemsModal" tabindex="-1" aria-labelledby="viewItemsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xxl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewItemsModalLabel">Order Number: </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <table id="tabelItems" class="table table-striped table-hover table-bordered compact-action" style="width:100%">
                    <thead>
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th style="width: 150px;">Item Code</th>
                            <th style="width: 300px;">Item Description</th>
                            <th style="width: 100px;">Qty Picking</th>
                            <th style="width: 150px;">Locator Picking</th>
                            <th style="width: 300px;">Packing List</th>
                            <th style="width: 100px;">UOM</th>
                            <th>Action</th>
                        </tr>
                        <tr class="table-search">
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
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>