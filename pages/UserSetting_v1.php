<?php
// CRITICAL: Pastikan session_start() ada di awal
session_start();

if (!isset($_SESSION['username'])) {
    http_response_code(401);
    exit('Unauthorized');
}

// Refresh last_activity
$_SESSION['last_activity'] = time();

//Periksa apakah pengguna sudah login dan memiliki role admin
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'superadmin')) {
    header('Location: ../login.php');
    exit;
}
?>  

<main>
    <header class="page-header page-header-compact page-header-light border-bottom bg-white mb-4">
        <div class="container-xl px-4">
            <div class="page-header-content">
                <div class="row align-items-center justify-content-between pt-3">
                    <div class="col-auto mb-3">
                        <!--<h1 class="page-header-title">-->
                        <!--    <div class="page-header-icon"><i data-feather="users"></i></div>-->
                        <!--    User Setting-->
                        <!--</h1>-->
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
                Table Users
                <div class="float-end">
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#userModal">
                        <i data-feather="plus" style="width: 14px; height: 14px;"></i> Add New
                    </button>
                </div>
            </div>
            <div class="card-body" style="overflow-x: auto;">
                <div class="table-responsive" style="overflow-x: auto;">
                    <table class="table table-striped table-hover table-bordered compact-action" id="tabelusers"
                        style="min-width: 100%; white-space: nowrap;">
                        <thead class="table-light">
                           <tr>
                                <th style="width: 50px;"><strong>No</th>
                                <th style="width: 150px;"><strong>Full Name</strong></th>
                                <th style="width: 150px;"><strong>Username</strong></th>
                                <th style="width: 150px;"><strong>Email</strong></th>
                                <th style="width: 150px;"><strong>WH Name</strong></th>
                                <th style="width: 250px;"><strong>Role</th>
                                <th style="width: 80px;"><strong>Project Name</th>
                                
                                <th style="width: 100px;">Action</th>
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
            </div>
        </div>
    </div>
</main>


<!-- Modal Reset Password -->
        <div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="resetPasswordModalLabel">Reset Password</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="resetPasswordForm">
                            <input type="hidden" name="user_id" id="user_id">
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label class="form-label">Password Baru</label>
                                    <input type="password" name="new_password" class="form-control" required>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-primary">Reset Password</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userModalLabel">Tambah user</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="userForm" action="modules/Proses_Register_User.php" method="POST">
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label">Nama</label>
                            <input type="text" name="nama" class="form-control" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" placeholder="example@gmail.com" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">User Name</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">WH ID</label>
                            <select name="wh_name" class="form-select select2" required>
                                <option value="">Pilih WH-ID</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Project</label>
                            <select name="project_name" class="form-select select2" required>
                                <option value="">Pilih project</option>
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