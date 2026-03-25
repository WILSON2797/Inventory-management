<?php
session_start();

if (!isset($_SESSION['username'])) {
    http_response_code(401);
    exit('Unauthorized');
}

// Refresh last_activity
$_SESSION['last_activity'] = time();

$nama = $_SESSION['nama'] ?? 'nama';
$wh_name = $_SESSION['wh_name'] ?? '';
$wh_id = $_SESSION['wh_id'] ?? '';
$project_name = $_SESSION['project_name'] ?? '';
$role = $_SESSION['role'] ?? 'user'; 
date_default_timezone_set('Asia/Jakarta');
?>

<main>
    <div class="container-fluid px-4 mt-4">
    <ul class="nav nav-tabs mb-3" id="requestTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-request" data-bs-toggle="tab" data-bs-target="#tabContentRequest"
                type="button" role="tab" aria-controls="tabContentRequest" aria-selected="true">
                <i data-feather="clipboard" style="width: 14px; height: 14px;"></i> Request Stock
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-approved" data-bs-toggle="tab" data-bs-target="#tabContentApproved"
                type="button" role="tab" aria-controls="tabContentApproved" aria-selected="false">
                <i data-feather="check-circle" style="width: 14px; height: 14px;"></i> Approved
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-reject" data-bs-toggle="tab" data-bs-target="#tabContentReject"
                type="button" role="tab" aria-controls="tabContentReject" aria-selected="false">
                <i data-feather="x-circle" style="width: 14px; height: 14px;"></i> Reject
            </button>
        </li>
    </ul>

    <!-- ======================== -->
    <!--  TAB PANES CONTENT AREA  -->
    <!-- ======================== -->
    <div class="tab-content" id="requestTabsContent">

        <!--1: REQUEST STOCK -->
        <div class="tab-pane fade show active" id="tabContentRequest" role="tabpanel" aria-labelledby="tab-request">
            <div class="card mb-4">
                <div class="card-header">
                    Request Stock List
                    <div class="float-end">
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#requestModal">
                            <i data-feather="plus" style="width: 14px; height: 14px;"></i> New Request
                        </button>
                        <button class="btn btn-success btn-sm" id="exportRequestExcel">
                            <i data-feather="file-text" style="width:14px; height:14px;"></i> Export Excel
                        </button>
                    </div>
                </div>
                <div class="card-body" style="overflow-x:auto;">
                    <div class="table-responsive" style="overflow-x: auto;">
                    <table class="table table-striped table-hover table-bordered " id="tabelRequest"
                        style="min-width:100%; white-space:nowrap;">
                        <thead class="table-light">
                            <tr>
                                <th><strong>No</strong></th>
                                <th><strong>Request Date</strong></th>
                                <th><strong>Request Number</strong></th>
                                <th><strong>Request Stock Type</strong></th>
                                <th><strong>Total Items</strong></th>
                                <th><strong>Request By</strong></th>
                                <th><strong>WH Name</strong></th>
                                <th><strong>Status</strong></th>
                                <th><strong>Action</strong></th>
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
                                <th></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                </div>
            </div>
        </div>

        
        <div class="tab-pane fade" id="tabContentApproved" role="tabpanel" aria-labelledby="tab-approved">
            <div class="card mb-4">
                <div class="card-header">
                    Approved Requests
                </div>
                <div class="card-body" style="overflow-x:auto;">
                    <div class="table-responsive" style="overflow-x: auto;">
                    <table class="table table-striped table-hover table-bordered compact-action" id="tabelApproved"
                        style="min-width:100%; white-space:nowrap;">
                        <thead class="table-light">
                            <tr>
                                <th><strong>No</strong></th>
                                <th><strong>Approved Date</strong></th>
                                <th><strong>Request Number</strong></th>
                                <th><strong>Request Stock Type</strong></th>
                                <th><strong>Total Items</strong></strong></th>
                                <th><strong>Approved By</strong></th>
                                <th><strong>WH Name</strong></th>
                                <th><strong>Project Name</strong></th>
                                <th><strong>Status</strong></th>
                                <th><strong>Approved Note</strong></th>
                                <th><strong>Action</strong></th>
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
                                <th class="action-column"></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>

       
        <div class="tab-pane fade" id="tabContentReject" role="tabpanel" aria-labelledby="tab-reject">
            <div class="card mb-4">
                <div class="card-header">
                    Rejected Requests
                </div>
                <div class="card-body" style="overflow-x:auto;">
                    <div class="table-responsive" style="overflow-x: auto;">
                    <table class="table table-striped table-hover table-bordered compact-action" id="tabelReject"
                        style="min-width:100%; white-space:nowrap;">
                        <thead class="table-light">
                            <tr>
                                <th><strong>No</strong></th>
                                <th><strong>Rejected Date</strong></th>
                                <th><strong>Request Number</strong></th>
                                <th><strong>Request Stock Type</strong></th>
                                <th><strong>Total Qty</strong></th>
                                <th><strong>Rejected By</strong></th>
                                <th><strong>WH Name</strong></th>
                                <th><strong>Project Name</strong></th>
                                <th><strong>Rejected Reasons</strong></th>
                                <th><strong>Status</strong></th>
                                <th><strong>Action</strong></th>
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
                                <th class="action-column"></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Modal Tambah Request -->
<div class="modal fade" id="requestModal" tabindex="-1" aria-labelledby="requestModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xxl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="requestModalLabel">Request Material FORM</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="requestForm" action="modules/proses_request.php" method="POST">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Request Number</label>
                            <div class="input-group">
                                <input type="text" name="request_number" id="request_number" class="form-control"
                                    required>
                                <button type="button" class="btn btn-outline-primary btn-sm" id="generateRequestNumber">
                                    <i class="fas fa-cog me-1"></i> Generate
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Stock Type</label>
                            <select name="stock_type" id="stock_type" class="form-select select2" required>
                                <option value=""></option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Requester Name</label>
                            <input type="text" name="requester" class="form-control"
                                value="<?php echo htmlspecialchars($nama); ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Warehouse</label>
                            <input type="text" name="warehouse" class="form-control"
                                value="<?php echo htmlspecialchars($wh_name); ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Project</label>
                            <input type="text" name="project" class="form-control"
                                value="<?php echo htmlspecialchars($project_name); ?>" readonly>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered item-table" id="requestItemTable">
                            <thead>
                                <tr>
                                    <th style="width:300px;">Item Code</th>
                                    <th>Item Description</th>
                                    <th style="width:120px;">Qty</th>
                                    <th style="width:100px;">UOM</th>
                                    <th>Remarks</th>
                                    <th style="width:80px;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="requestItemRows">
                                <tr>
                                    <td>
                                        <select name="items[0][item_code]" class="form-select select2 item-code"
                                            data-index="0" required>
                                            <option value="">Pilih Item</option>
                                        </select>
                                    </td>
                                    <td><input type="text" name="items[0][item_description]"
                                            class="form-control item-description" readonly></td>
                                    <td><input type="number" name="items[0][qty]" class="form-control" required></td>
                                    <td><input type="text" name="items[0][uom]" class="form-control item-uom" readonly>
                                    </td>
                                    <td><input type="text" name="items[0][remarks]" class="form-control"></td>
                                    <td>
                                        <button type="button" class="btn btn-sm removeRequestRow"><i
                                                data-feather="trash-2" class="text-danger"></i></button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-success btn-sm mb-3" id="addRequestRow">
                        <i data-feather="plus" style="width:14px; height:14px;"></i> Tambah Item
                    </button>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" form="requestForm" class="btn btn-primary">Kirim Request</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail Items -->
<div class="modal fade" id="viewItemsRequestModal" tabindex="-1" aria-labelledby="viewItemsRequestModal" aria-hidden="true">
    <div class="modal-dialog modal-xxl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewItemsRequestModal">Order Number: </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <table id="tabelItems" class="table table-striped table-hover table-bordered compact-action" style="width:100%">
                    <thead>
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th style="width: 150px;">Item Code</th>
                            <th style="width: 300px;">Item Description</th>
                            <th style="width: 100px;">Qty</th>
                            <th style="width: 100px;">UOM</th>
                            <th style="width: 300px;">Remarks</th>
                            <th>Action</th>
                        </tr>
                        <tr class="table-search">
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
<!-- Modal untuk Approved Note -->
<div class="modal fade" id="approveNoteModal" tabindex="-1" aria-labelledby="approveNoteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="approveNoteModalLabel">Catatan Persetujuan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="approveNoteForm">
                    <input type="hidden" id="approve_request_number" name="request_number">
                    <div class="mb-3">
                        <label for="approved_note" class="form-label">Catatan Persetujuan (Wajib)</label>
                        <textarea class="form-control" id="approved_note" name="approved_note" rows="4" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="submitApproveNote">Setujui</button>
            </div>
        </div>
    </div>
</div>
