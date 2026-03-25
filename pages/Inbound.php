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
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#inboundModal">
                        <i data-feather="plus" style="width: 14px; height: 14px;"></i> Add New Inbound
                    </button>
                    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#bulkInboundModal">
                        <i data-feather="upload" style="width: 14px; height: 14px;"></i> Bulk Upload
                    </button>
                    <button class="btn btn-success btn-sm" id="exportExcelInbound">
                        <i data-feather="file-text" style="width:14px; height:14px;"></i> Export Excel
                    </button>
                </div>
            </div>
            <div class="card-body" style="overflow-x: auto;">
                <div class="table-responsive" style="overflow-x: auto;">
                    <table class="table table-striped table-hover table-bordered compact-action" id="tabelInbound"
                        style="min-width: 100%; white-space: nowrap;">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;"><strong>No</th>
                                <th style="width: 150px;"><strong>PO Number</strong></th>
                                <th style="width: 150px;"><strong>Reference Number</strong></th>
                                <th style="width: 150px;"><strong>Packing List</strong></th>
                                <th style="width: 250px;"><strong>Item Code</strong></th>
                                <th style="width: 250px;"><strong>Item Description</th>
                                <th style="width: 80px;"><strong>Qty</th>
                                <th style="width: 80px;"><strong>UOM</strong></th>
                                <th style="width: 120px;"><strong>Locator</th>
                                <th style="width: 120px;"><strong>Create Date</th>
                                <th style="width: 120px;"><strong>Create By</th>
                                <th style="width: 120px;"><strong>WH Name</th>
                                <th style="width: 80px;"><strong>Action</th>
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

<!-- Modal Tambah Inbound -->
<div class="modal fade" id="inboundModal" tabindex="-1" aria-labelledby="inboundModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xxl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="inboundModalLabel">Tambah Data Inbound</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="inboundForm" action="php/proses_inbound.php" method="POST">
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label class="form-label">PO Number <span style="color: #8B0000;">(Generate Jika Tidak Ada PO Number Atau Nomor Document )</span></label>
                            <div class="input-group">
                                <input type="text" name="po_number" id="po_number" class="form-control" required>
                                <button type="button" class="btn btn-outline-primary btn-sm" id="generatePoNumber">
                                    <i class="fas fa-cog me-1"></i>Generate PO Internal
                                </button>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Supplier</label>
                            <input type="text" name="supplier" class="form-control" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Reference Number</label>
                            <input type="text" name="reference_number" class="form-control" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Packing List</label>
                            <input type="text" name="packing_list" class="form-control" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Stock Type</label>
                            <select name="stock_type" id="stock_type" class="form-select select2" required>
                                <option value=""></option>
                            </select>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered item-table" id="inboundItemTable">
                            <thead>
                                <tr>
                                    <th style="width: 300px;">Item Code</th>
                                    <th>Item Description</th>
                                    <th style="width: 150px;">Qty</th>
                                    <th style="width: 100px;">UOM</th>
                                    <th style="width: 150px;">Locator</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="inboundItemRows">
                                <tr>
                                    <td>
                                        <select name="items[0][item_code]" class="form-select select2 item-code"
                                            data-index="0" required>
                                            <option value="">Pilih Item Code</option>
                                        </select>
                                    </td>
                                    <td><input type="text" name="items[0][item_description]"
                                            class="form-control item-description" readonly></td>
                                    <td><input type="number" name="items[0][qty]" class="form-control" required></td>
                                    <td><input type="text" name="items[0][uom]" class="form-control item-uom" readonly></td>
                                    <td>
                                        <select name="items[0][locator]" class="form-select select2 locator-select" required>
                                            <option value="">Pilih Locator</option>
                                        </select>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm removeInboundRow">
                                            <i data-feather="trash-2" class="text-danger"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-success btn-sm mb-3" id="addInboundRow">
                        <i data-feather="plus" style="width: 14px; height: 14px;"></i> Tambah Item
                    </button>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" form="inboundForm" class="btn btn-primary">Simpan Data</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Bulk Upload -->
<div class="modal fade" id="bulkInboundModal" tabindex="-1" aria-labelledby="bulkInboundModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkInboundModalLabel">
                    <i class="fas fa-cloud-upload-alt me-2"></i>Bulk Upload Form
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="download-template">
                    <strong>Upload File xlsx</strong>
                    <p class="mb-2 mt-2"></p>
                    <button onclick="window.open('modules/export_template_inbound.php', '_blank')"
                        class="btn btn-success btn-sm">
                        <i class="fas fa-file-excel me-1"></i>Download Template
                    </button>
                </div>

                <form id="bulkInboundForm" action="modules/proses_bulk_inbound.php" method="POST"
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
                            <input type="file" name="xlxs" class="file-input" id="fileInput" accept=".xlsx,.xls" required>
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
