<?php
session_start();

// Timeout
$timeout_duration = 1200;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header("Location: login.php?message=session_expired");
    exit();
}
$_SESSION['last_activity'] = time();

// === CEK KONTEKS WH & PROJECT ===
if (!isset($_SESSION['context_wh_name']) || !isset($_SESSION['context_project_name'])) {
    header('Location: select_wh_project.php');
    exit();
}

// Ambil konteks
$wh_name = $_SESSION['context_wh_name'];
$wh_id = $_SESSION['context_wh_id'];
$project_name = $_SESSION['context_project_name'];

// Kompatibilitas dengan kode lama
$_SESSION['wh_name'] = $wh_name;
$_SESSION['wh_id'] = $wh_id;
$_SESSION['project_name'] = $project_name;

// Cek login
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

// Role
$role = $_SESSION['role'] ?? 'user';

// Default page
$default_page = $_GET['page'] ?? 'dashboard';

// Cek apakah pengguna sudah login
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

// Ambil data dari session
$role = isset($_SESSION['role']) ? $_SESSION['role'] : 'user';
$wh_name = isset($_SESSION['wh_name']) ? $_SESSION['wh_name'] : '';
$wh_id = isset($_SESSION['wh_id']) ? $_SESSION['wh_id'] : '';
$project_name = isset($_SESSION['project_name']) ? $_SESSION['project_name'] : '';

// Deteksi halaman default dari URL
$default_page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// Log untuk debugging
error_log("Index.php - Username: {$_SESSION['username']}, Role: $role, WH_Name: $wh_name, Project_Name: $project_name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Inventory Management - FIS APPS</title>
    
    <?php
    // Variabel versi untuk cache busting
    $version = time();
    ?>
    
    <!-- CDN Bawan SB admin Start -->
    <link href="css/styles.css" rel="stylesheet" />
    <link rel="icon" type="image/x-icon" href="assets/img/logonew.png" />
    
    <!-- Font SB Admin -->
    <script data-search-pseudo-elements defer src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/js/all.min.js" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/feather-icons/4.28.0/feather.min.js" crossorigin="anonymous"></script>
    
    <!-- Tambahan CSS Project -->
    <link rel="stylesheet" href="css/animations.css?v=<?php echo $version; ?>">
    <link rel="stylesheet" href="css/custom.css?v=<?php echo $version; ?>">
    <link rel="stylesheet" href="css/pending-orders.css?v=<?php echo $version; ?>">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css?v=<?php echo $version; ?>" rel="stylesheet">
    
    <!-- jQuery DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.dataTables.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/fixedcolumns/4.3.0/css/fixedColumns.dataTables.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.1.4/toastr.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/toastr-custom.css?v=<?php echo $version; ?>">
    
    <script>
        const userRole = '<?php echo $role; ?>';
        console.log('User Role from Index:', userRole); // Debug
    </script>
</head>
<body class="nav-fixed">
    
    <!-- HEADER - Dimuat sekali saja -->
    <?php include 'layouts/header.php'; ?>
    
    <!-- SIDEBAR - Dimuat sekali saja -->
    <?php include 'layouts/sidebar.php'; ?>
    
    <!-- MAIN CONTENT CONTAINER - Hanya bagian ini yang akan berubah -->
    <div id="main-content">
        <!-- Content akan dimuat di sini via AJAX -->
        <div class="d-flex justify-content-center align-items-center" style="height: calc(100vh - 120px);">
            <div class="container-spinner">
        <div class="spinner">
            <div class="grok-spinner">
                <div class="grok-dot"></div>
                <div class="grok-dot"></div>
                <div class="grok-dot"></div>
                <div class="grok-dot"></div>
            </div>
        </div>
        <!--<div class="text" id="loading-text">Loading...</div>-->
        <!--<p class="sub-text">Please wait the moment.</p>-->
    </div>
        </div>
    </div>
    
    <!-- FOOTER - Dimuat sekali saja -->
    <?php include 'layouts/footer.php'; ?>

    
    
</body>
</html>