<?php
// CRITICAL: Pastikan session sudah dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../php/config.php';

// Ambil data dari session
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'N/A';
$role = isset($_SESSION['role']) ? $_SESSION['role'] : 'N/A';
$wh_name = isset($_SESSION['wh_name']) ? $_SESSION['wh_name'] : 'N/A';
$wh_id = isset($_SESSION['wh_id']) ? $_SESSION['wh_id'] : 'N/A';
$project_name = isset($_SESSION['project_name']) ? $_SESSION['project_name'] : 'N/A';

// Ambil nama lengkap dari session (gunakan 'nama' sesuai dengan login)
$full_name = isset($_SESSION['nama']) ? $_SESSION['nama'] : $username;
$profilePicture = $_SESSION['profile_picture'] ?? '';

$profileImagePath = !empty($profilePicture)
    ? 'assets/Uploads/profile/' . $profilePicture
    : 'assets/img/illustrations/profiles/profile-1.png';

// Debug: log data session untuk troubleshooting
error_log("Profile.php - Session data: Username={$username}, Role={$role}, WH_Name={$wh_name}, Project={$project_name}, Nama={$full_name}");
?>
<main>
    <header class="page-header page-header-compact page-header-light border-bottom bg-white mb-4">
        <div class="container-xl px-4">
            <div class="page-header-content">
                <div class="row align-items-center justify-content-between pt-3">
                    <div class="col-auto mb-3">
                        <h1 class="page-header-title">
                            <div class="page-header-icon"><i data-feather="user"></i></div>
                            Account Settings - Profile
                        </h1>
                    </div>
                </div>
            </div>
        </div>
    </header>
    <!-- Main page content-->
    <div class="container-xl px-4 mt-4">
        <!-- Account page navigation-->
        <nav class="nav nav-borders nav-borders-underline">
        <a class="top-nav-link active ms-0" href="?page=profile">Profile</a>
        <a class="top-nav-link" href="?page=#">Security</a>
        </nav>
        <hr class="mt-0 mb-4" />
        <div class="row">
            <div class="col-xl-4">
                <div class="card mb-4 mb-xl-0">
                    <div class="card-header">Profile Picture</div>
                    <div class="card-body text-center">
                        
                        <img id="profileImage" class="img-account-profile rounded-circle mb-3 shadow"
                             src="<?php echo $profileImagePath; ?>"
                             alt="Profile Picture"
                             style="width: 150px; height: 150px; object-fit: cover;" />

                        <div class="small font-italic text-muted mb-3">
                            JPG atau PNG maksimal 5 MB
                        </div>

                        <form id="uploadProfileForm" action="modules/upload_profile.php" method="POST" enctype="multipart/form-data">
                            <input id="profileImageInput" class="form-control form-control-sm mb-3"
                                   type="file"
                                   name="profile_image"
                                   accept="image/jpeg,image/jpg,image/png" />

                            <button id="uploadBtn" class="btn btn-primary w-100" type="submit">
                                <i data-feather="upload" class="me-1"></i>
                                <span id="btnText">Upload new image</span>
                            </button>

                            <!-- Loading spinner -->
                            <div id="uploadSpinner" class="spinner-border spinner-border-sm text-primary mt-2" role="status" style="display: none;">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
            <div class="col-xl-8">
                <!-- Account details card-->
                <div class="card mb-4">
                    <div class="card-header">Account Details</div>
                    <div class="card-body">
                        <form>
                            <!-- Form Group (Name)-->
                            <div class="mb-3">
                                <label class="small mb-1" for="inputName">
                                    <i data-feather="user" class="me-1" style="width: 14px; height: 14px;"></i>
                                    Full Name
                                </label>
                                <input class="form-control bg-light" 
                                       id="inputName" 
                                       type="text"
                                       value="<?php echo htmlspecialchars($full_name); ?>" 
                                       readonly />
                            </div>

                            <!-- Form Group (Username)-->
                            <div class="mb-3">
                                <label class="small mb-1" for="inputUsername">
                                    <i data-feather="at-sign" class="me-1" style="width: 14px; height: 14px;"></i>
                                    Username
                                </label>
                                <input class="form-control bg-light" 
                                       id="inputUsername" 
                                       type="text"
                                       value="<?php echo htmlspecialchars($username); ?>" 
                                       readonly />
                            </div>

                            <!-- Form Group (Role)-->
                            <div class="mb-3">
                                <label class="small mb-1" for="inputRole">
                                    <i data-feather="shield" class="me-1" style="width: 14px; height: 14px;"></i>
                                    Role
                                </label>
                                <input class="form-control bg-light" 
                                       id="inputRole" 
                                       type="text"
                                       value="<?php echo htmlspecialchars(ucfirst($role)); ?>" 
                                       readonly />
                            </div>

                            <!-- Form Row (Project & Warehouse)-->
                            <div class="row gx-3 mb-3">
                                <!-- Form Group (Project Name)-->
                                <div class="col-md-6">
                                    <label class="small mb-1" for="inputProject">
                                        <i data-feather="briefcase" class="me-1" style="width: 14px; height: 14px;"></i>
                                        Project Name
                                    </label>
                                    <input class="form-control bg-light" 
                                           id="inputProject" 
                                           type="text"
                                           value="<?php echo htmlspecialchars($project_name); ?>" 
                                           readonly />
                                </div>

                                <!-- Form Group (Warehouse Name)-->
                                <div class="col-md-6">
                                    <label class="small mb-1" for="inputWarehouse">
                                        <i data-feather="package" class="me-1" style="width: 14px; height: 14px;"></i>
                                        Warehouse Name
                                    </label>
                                    <input class="form-control bg-light" 
                                           id="inputWarehouse" 
                                           type="text"
                                           value="<?php echo htmlspecialchars($wh_name); ?>" 
                                           readonly />
                                </div>
                            </div>

                            <!-- Info Alert -->
                            <div class="alert alert-info d-flex align-items-center" role="alert">
                                <i data-feather="info" class="me-2"></i>
                                <div>
                                    <strong>Information:</strong> Profile data is read-only. Please contact your administrator to update account information.
                                </div>
                            </div>

                            <!-- Disabled Save Button -->
                            <button class="btn btn-secondary" type="button" disabled>
                                <i data-feather="save" class="me-1"></i>
                                Save changes
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
// Re-initialize feather icons setelah konten dimuat
if (typeof feather !== 'undefined') {
    feather.replace();
}
</script>