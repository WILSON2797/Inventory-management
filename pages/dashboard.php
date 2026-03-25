<?php
session_start();

// Cek apakah user sudah login
if (!isset($_SESSION['username'])) {
    header("Location: ../login?message=session_expired");
    exit();
}

// Ambil role dan wh_name dari session
$role = $_SESSION['role'];
$wh_name_session = $_SESSION['wh_name'] ?? '';

// Tentukan default wh_name berdasarkan role
$default_wh = ($role == 'admin') ? '' : $wh_name_session;

// Jika admin, ambil semua wh_name dari database
$wh_options = '';
if ($role == 'admin') {
    include '../php/config.php'; // Koneksi database
    $query = "SELECT DISTINCT wh_name FROM stock WHERE wh_name IS NOT NULL ORDER BY wh_name ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $selected = ($row['wh_name'] == $wh_name_session) ? ' selected' : '';
        $wh_options .= '<option value="' . htmlspecialchars($row['wh_name']) . '"' . $selected . '>' . htmlspecialchars($row['wh_name']) . '</option>';
    }
    $stmt->close();
    $conn->close();
} else {
    // Untuk non-admin, hanya dari sesi dengan default selected
    if ($wh_name_session) {
        $wh_options = '<option value="' . htmlspecialchars($wh_name_session) . '" selected>' . htmlspecialchars($wh_name_session) . '</option>';
    }
}
?>

<div class="container-fluid px-6">
    
    <div class="mb-3">
        <label for="whFilter" class="form-label">Select Warehouse:</label>
        <select id="whFilter" class="form-select" style="width: 300px;">
            <?php if ($role == 'admin') { ?>
                <option value="">All Warehouses</option>
            <?php } ?>
            <?php echo $wh_options; ?>
        </select>
    </div>
    <div class="row g-2" id="stockSummary">
        <!-- Kotak-kotak summary akan diisi oleh JavaScript -->
    </div>
    <!-- Content Row -->
    <div class="row">
        <!-- Total Inbound -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Inbound</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalInbound">0</div>
                        </div>
                        <div class="col-auto">
                            <i data-feather="package" class="feather-2x text-primary-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Total Allocated -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Total Allocated</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalAllocated">0</div>
                        </div>
                        <div class="col-auto">
                            <i data-feather="alert-triangle" class="feather-2x text-dark-400"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Total Outbound -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Total Outbound</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalOutbound">0</div>
                        </div>
                        <div class="col-auto">
                            <i data-feather="download" class="feather-2x text-dark-400"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Total Onhand -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Total Onhand</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalOnHand">0</div>
                        </div>
                        <div class="col-auto">
                            <i data-feather="upload" class="feather-2x text-dark-400"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Balance Stock -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Balance Stock</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalBalance">0</div>
                        </div>
                        <div class="col-auto">
                            <i data-feather="upload" class="feather-2x text-dark-400"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Content Row -->
    <div class="row">
        <!-- Area Chart -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Inventory Overview</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="inventoryChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <!-- Pie Chart -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Stock Distribution</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2">
                        <canvas id="stockPieChart"></canvas>
                    </div>
                    <div class="mt-4 text-center small">
                        <span class="mr-2">
                            <i class="fas fa-circle text-primary"></i> Onhand Stock
                        </span>
                        <span class="mr-2">
                            <i class="fas fa-circle text-success"></i> Allocated
                        </span>
                        <span class="mr-2">
                            <i class="fas fa-circle text-warning"></i> Outbound
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>