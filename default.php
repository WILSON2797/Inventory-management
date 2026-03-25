<?php include 'layouts/header.php'; ?>
<div class="main-container d-flex">
    <?php include 'layouts/sidebar.php'; ?>
    <div class="content">
        <nav class="navbar navbar-expand-md navbar-light bg-light">
            <div class="container-fluid">
                <button class="btn px-1 py-0 open-btn me-2"><i class="fas fa-bars"></i></button>
                <a class="navbar-brand" href="#">
                    <img src="../assets/img/icon.png" alt="Logo" style="width: 50px; height: 50px;" class="me-2">
                    <span class="text-primary">Aplication Demo</span>
                </a>
            </div>
        </nav>

        <div class="card m-3 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0"><i class="fas fa-checklist"></i>Task Management</h4>
                <div>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#NewTaskModal">
                        <i class="fas fa-plus me-1"></i> Tambah Data
                    </button>
                    <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#bulkNewTaskModal">
                        <i class="fas fa-upload me-1"></i> Bulk Upload
                    </button>
                    <button class="btn btn-success btn-sm" id="exportTask">
                        <i class="fas fa-file-excel me-1"></i> Export Excel
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                <div class="table-wrapper">
                    <table id="tabelNewTask" class="table table-striped table-hover table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th style="background-color: #008080;">No</th>
                                <th style="background-color: #008080;">SDR Number</th>
                                <th style="background-color: #008080;">Site ID</th>
                                <th style="background-color: #008080;">Site Name</th>
                                <th style="background-color: #008080;">Customer / Supplier</th>
                                <th style="background-color: #008080;">Destination</th>
                                <th style="background-color: #008080;">Create_at</th>
                                <th style="background-color: #008080;">Status</th>
                                <th style="background-color: #008080;">Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Inbound -->
<div class="modal fade" id="NewTaskModal" tabindex="-1" aria-labelledby="NewTaskModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="inboundModalLabel">Create New Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="NewTaskForm" action="../modules/Proses_NewTask.php" method="POST">
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label">Order Number</label>
                            <input type="text" name="order_number" class="form-control" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Site ID</label>
                            <input type="text" name="site_id" class="form-control" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Site Name</label>
                            <input type="text" name="site_name" class="form-control" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label">Customer / Supplier</label>
                            <input type="text" name="customer" class="form-control" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label">Destination</label>
                            <input type="text" name="destination" class="form-control" required>
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

<!-- Modal Bulk Upload Inbound -->
<div class="modal fade" id="bulkNewTaskModal" tabindex="-1" aria-labelledby="bulkNewTaskModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkNewTaskModalLabel">Bulk Upload Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="bulkNewTaskForm" action="../modules/Proses_BulkNewTask.php" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Unggah File xlsx</label>
                        <input type="file" name="xlxs" class="form-control" accept=".xlsx" required>
                        <small class="form-text text-muted"> 
                            <a href="../modules/export_template.php" target="_blank">Unduh template xlsx</a>.
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

<!-- Modal Tambah Item -->
<div class="modal fade" id="addItemModal" tabindex="-1" aria-labelledby="addItemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addItemModalLabel">Tambah Item untuk Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addItemForm" action="../modules/Proses_AddItems.php" method="POST">
                    <input type="hidden" name="order_number" id="order_number">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Date Pickup</label>
                            <input type="date" name="date_pickup" class="form-control" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Driver Name</label>
                            <input type="text" name="driver_name" class="form-control" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Destination</label>
                            <input type="text" name="destination" class="form-control" required>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Item Name</th>
                                    <th>Dimension (Panjang x Lebar x Tinggi)</th>
                                    <th>Qty</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="itemsContainer">
                                <!-- Baris item akan ditambahkan secara dinamis oleh JavaScript -->
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-success mb-3" id="addItemRow">Tambah Item</button>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'layouts/footer.php'; ?>