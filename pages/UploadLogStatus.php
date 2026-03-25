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
                Log Upload Status Data Table
                <div class="float-end">
                    <button id="refreshBtn" class="btn btn-primary mb-3">
                        <i data-feather="refresh-cw" class="me-1 feather-lg"></i>
                        Refresh Status
                    </button>
                </div>

            </div>
            <div class="card-body" style="overflow-x: auto;">
                <div class="table-responsive" style="overflow-x: auto;">

                    <table class="table table-striped table-hover table-bordered compact-action" id="fileStatusTable"
                        style="min-width: 100%; white-space: nowrap;">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;"><strong>No</th>
                                <th style="width: 150px;"><strong>JOB Type</strong></th>
                                <th style="width: 150px;"><strong>File Name</strong></th>
                                <th style="width: 150px;"><strong>Status</strong></th>
                                <th style="width: 250px;"><strong>Row Success</strong></th>
                                <th style="width: 250px;"><strong>Error Message</th>
                                <th style="width: 80px;"><strong>Upload Date</th>
                                <th style="width: 80px;"><strong>Upload By</strong></th>
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