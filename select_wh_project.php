<?php
// ===================================
// 1. SESSION & SECURITY CONFIG (WAJIB!)
// ===================================
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);

// Hanya aktifkan secure cookie jika HTTPS
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    ini_set('session.cookie_secure', 1);
}

session_start();

$version = time();

// Regenerate session ID (anti-fixation)
if (!isset($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
}

// Session timeout: 30 menit
$timeout = 1800;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
    session_unset();
    session_destroy();
    if (isset($_GET['ajax'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Session expired']);
        exit();
    }
    header('Location: login.php?timeout=1');
    exit();
}
$_SESSION['last_activity'] = time();

// CSRF Token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ===================================
// 2. AUTHENTICATION CHECK
// ===================================
if (!isset($_SESSION['user_id'])) {
    if (isset($_GET['ajax'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit();
    }
    header('Location: login.php');
    exit();
}

$user_id = filter_var($_SESSION['user_id'], FILTER_VALIDATE_INT);
if ($user_id === false || $user_id <= 0) {
    session_unset();
    session_destroy();
    header('Location: login.php?error=invalid');
    exit();
}

include 'php/config.php';
$conn->set_charset('utf8mb4');

// ===================================
// 3. SECURITY HEADERS
// ===================================
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

$csp = "default-src 'self'; " .
       "script-src 'self' https://code.jquery.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com 'unsafe-inline'; " .
       "style-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com 'unsafe-inline'; " .
       "font-src 'self' https://cdnjs.cloudflare.com data:; " .  // Tambahkan ini untuk font
       "img-src 'self' data: https:; " .
       "connect-src 'self';";
header("Content-Security-Policy: $csp");

// ===================================
// 4. RATE LIMITING (50 req/menit)
// ===================================
$rate_key = 'req_' . $user_id;
if (!isset($_SESSION[$rate_key])) {
    $_SESSION[$rate_key] = ['count' => 0, 'time' => time()];
}
if (time() - $_SESSION[$rate_key]['time'] < 60) {
    $_SESSION[$rate_key]['count']++;
    if ($_SESSION[$rate_key]['count'] > 50) {
        if (isset($_GET['ajax'])) {
            http_response_code(429);
            echo json_encode(['error' => 'Too many requests']);
            exit();
        }
        die('Too many requests. Please wait.');
    }
} else {
    $_SESSION[$rate_key] = ['count' => 1, 'time' => time()];
}

// ===================================
// 5. AJAX MODE: Load options
// ===================================
if (isset($_GET['ajax'])) {
    $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    if (!$is_ajax) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid request']);
        exit();
    }

    header('Content-Type: application/json; charset=utf-8');

    try {
        $wh_options = '<option value="">-- Pilih Warehouse --</option>';
        $stmt = $conn->prepare("SELECT wh_name, wh_id FROM user_warehouses WHERE user_id = ? ORDER BY wh_name ASC LIMIT 100");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $selected = ($row['wh_name'] === ($_SESSION['context_wh_name'] ?? '')) ? ' selected' : '';
            $wh_options .= "<option value=\"".htmlspecialchars($row['wh_name'])."\"$selected>".
                       htmlspecialchars($row['wh_name'])." (ID: ".$row['wh_id'].")</option>";
        }
        $stmt->close();

        $proj_options = '<option value="">-- Pilih Project --</option>';
        $stmt = $conn->prepare("SELECT project_name FROM user_projects WHERE user_id = ? ORDER BY project_name ASC LIMIT 100");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $selected = ($row['project_name'] === ($_SESSION['context_project_name'] ?? '')) ? ' selected' : '';
            $proj_options .= sprintf(
                '<option value="%s"%s>%s</option>',
                htmlspecialchars($row['project_name'], ENT_QUOTES, 'UTF-8'),
                $selected,
                htmlspecialchars($row['project_name'], ENT_QUOTES, 'UTF-8')
            );
        }
        $stmt->close();

        echo json_encode([
            'success' => true,
            'wh_options' => $wh_options,
            'project_options' => $proj_options,
            'csrf_token' => $_SESSION['csrf_token']
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        error_log("AJAX Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Server error']);
    }
    exit();
}

// ===================================
// 6. HTML MODE: Load data untuk form
// ===================================
$warehouses = [];
$stmt = $conn->prepare("SELECT wh_name, wh_id FROM user_warehouses WHERE user_id = ? ORDER BY wh_name ASC LIMIT 100");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $warehouses[] = $row;
}
$stmt->close();

$projects = [];
$stmt = $conn->prepare("SELECT project_name FROM user_projects WHERE user_id = ? ORDER BY project_name ASC LIMIT 100");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $projects[] = $row['project_name'];
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    
    <title>Inventory Management</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.1.4/toastr.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/toastr-custom.css?v=<?php echo $version; ?>">
    <link href="css/context-style.css?v=<?= time() ?>" rel="stylesheet">
</head>
<body>
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
        <p>Loading...</p>
    </div>

    <div class="main-container">
        <div class="logo-section">
            <div class="logo-icon"><i class="fas fa-warehouse"></i></div>
            <h1 class="logo-text">Sistem Warehouse</h1>
            <p class="logo-subtitle">Manajemen Gudang & Project</p>
        </div>

        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-tasks"></i> Select WH & Project</h4>
                <div class="text-white" style="font-size: 1.1rem; margin-top: 0.5rem;">
                    <div style="font-weight: 500;">
                        Hi <?= htmlspecialchars($_SESSION['nama'] ?? 'Unknown', ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <small style="font-size: 0.9rem; opacity: 0.9;">
                        Please select your WH & Project
                    </small>
                </div>
            </div>
            <div class="card-body">
                <form action="set_session.php" method="POST" id="contextForm" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                    <!-- Warehouse -->
                    <div class="form-group mb-3">
                        <label class="form-label">
                            <i class="fas fa-warehouse"></i> Warehouse <span class="text-danger">*</span>
                        </label>
                        <select name="wh_name" class="form-select select2" required id="warehouseSelect">
                            <option value="">-- Pilih Warehouse --</option>
                            <?php foreach ($warehouses as $wh): ?>
                                <option value="<?= htmlspecialchars($wh['wh_name'], ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($wh['wh_name'], ENT_QUOTES, 'UTF-8') ?> (ID: <?= $wh['wh_id'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($warehouses)): ?>
                            <small class="text-danger">Tidak ada warehouse tersedia.</small>
                        <?php endif; ?>
                    </div>

                    <!-- Project -->
                    <div class="form-group mb-3">
                        <label class="form-label">
                            <i class="fas fa-project-diagram"></i> Project <span class="text-danger">*</span>
                        </label>
                        <select name="project_name" class="form-select select2" required id="projectSelect">
                            <option value="">-- Pilih Project --</option>
                            <?php foreach ($projects as $proj): ?>
                                <option value="<?= htmlspecialchars($proj, ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($proj, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($projects)): ?>
                            <small class="text-danger">Tidak ada project tersedia.</small>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn-submit" <?= (empty($warehouses) || empty($projects)) ? 'disabled' : '' ?>>
                        <span>Masuk ke Sistem</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>

                    <div class="text-center mt-3">
                        <a href="pages/logout.php" class="text-muted" style="font-size:0.9rem;">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="text-center mt-3">
            <small class="text-muted">
                Session otomatis berakhir dalam <?= floor($timeout / 60) ?> menit
            </small>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.1.4/toastr.min.js?v=<?php echo $version; ?>"></script>
    <script src="js/context-script.js?v=<?= time() ?>"></script>
    <script src="assets/js/toastr-init.js?v=<?php echo $version; ?>"></script>

    <script>
        // Warning sebelum timeout
        setTimeout(() => {
            alert('Session akan berakhir. Silakan refresh halaman.');
        }, <?= ($timeout * 1000) - 60000 ?>);
    </script>
</body>
</html>