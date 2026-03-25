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
    

    <!-- Main page content-->
    <div class="container-fluid px-4 mt-4">
        <div class="card mb-4">
            <div class="card-header">
               
                <div class="float-end">
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#notificationmailModal">
                        <i data-feather="plus" style="width: 14px; height: 14px;"></i> Add New Mail Nofitication
                    </button>
                    
                    <button class="btn btn-success btn-sm" id="exportExcelnotificationmail">
                        <i data-feather="file-text" style="width:14px; height:14px;"></i> Export Excel
                    </button>
                </div>
            </div>
            <div class="card-body" style="overflow-x: auto;">
                <div class="table-responsive" style="overflow-x: auto;">
                    <table class="table table-striped table-hover table-bordered compact-action" id="tabelnotificationmail"
                        style="min-width: 100%; white-space: nowrap;">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;"><strong>No</strong></th>
                                <th style="width: 150px;"><strong>Name</strong></th>
                                <th style="width: 250px;"><strong>Created Date</strong></th>
                                <th style="width: 250px;"><strong>Status</strong></th>
                                <th style="width: 100px;">Action</th>
                            </tr>
                            <tr class="table-search">
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
<div class="modal fade" id="notificationmailModal" tabindex="-1" aria-labelledby="notificationmailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="notificationmailModalLabel">Add New Mail Notification</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="notificationmailForm" action="php/submit_notificationmail.php" method="POST">
                    <div class="row mb-3">
                        <div class="col-md-11">
                            <label class="form-label">Name</label>
                            <input type="text" name="nama" class="form-control" required>
                        </div>
                        <div class="col-md-11">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required>
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

