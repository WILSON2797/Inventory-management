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
                <h5 class="modal-title" id="userModalLabel">Tambah User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="userForm">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Nama Lengkap</label>
                            <input type="text" name="nama" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Username</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Password <small class="text-muted">(kosongkan jika edit)</small></label>
                            <input type="password" name="password" class="form-control" id="password_field">
                        </div>
                        
                        <!-- Warehouse Akses: Grup Checkbox -->
                        <div class="col-md-6 mb-3">
                            <label>Warehouse Akses <small class="text-danger">(pilih minimal 1)</small></label>
                            <div class="form-check-group border p-3" style="max-height: 200px; overflow-y: auto;">
                                <?php
                                // Load WH dari DB
                                include '../php/config.php';  // Ganti dengan path config kamu
                                $wh_query = "SELECT wh_name FROM warehouses ORDER BY wh_name ASC";
                                $wh_result = $conn->query($wh_query);
                                if ($wh_result->num_rows > 0) {
                                    while ($row = $wh_result->fetch_assoc()) {
                                        $wh_name = htmlspecialchars($row['wh_name']);
                                        echo "<div class='form-check'>
                                                <input class='form-check-input' type='checkbox' name='wh_names[]' value='$wh_name' id='wh_$wh_name'>
                                                <label class='form-check-label' for='wh_$wh_name'>$wh_name</label>
                                              </div>";
                                    }
                                } else {
                                    echo "<p class='text-danger'>Tidak ada warehouse tersedia.</p>";
                                }
                                $conn->close();
                                ?>
                            </div>
                        </div>
                        
                        <!-- Project Akses: Grup Checkbox -->
                        <div class="col-md-6 mb-3">
                            <label>Project Akses <small class="text-danger">(pilih minimal 1)</small></label>
                            <div class="form-check-group border p-3" style="max-height: 200px; overflow-y: auto;">
                                <?php
                                // Load Project dari DB
                                include '../php/config.php';  // Ganti dengan path config kamu
                                $proj_query = "SELECT Project_Name FROM project_name ORDER BY Project_Name ASC";
                                $proj_result = $conn->query($proj_query);
                                if ($proj_result->num_rows > 0) {
                                    while ($row = $proj_result->fetch_assoc()) {
                                        $proj_name = htmlspecialchars($row['Project_Name']);
                                        echo "<div class='form-check'>
                                                <input class='form-check-input' type='checkbox' name='project_names[]' value='$proj_name' id='proj_$proj_name'>
                                                <label class='form-check-label' for='proj_$proj_name'>$proj_name</label>
                                              </div>";
                                    }
                                } else {
                                    echo "<p class='text-danger'>Tidak ada project tersedia.</p>";
                                }
                                $conn->close();
                                ?>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>