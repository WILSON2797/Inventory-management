<?php
// Ambil profile picture dari session
$profilePicture = $_SESSION['profile_picture'] ?? '';
$headerProfileImage = !empty($profilePicture)
    ? 'assets/Uploads/profile/' . $profilePicture
    : 'assets/img/illustrations/profiles/profile-2.png';
?>


<!-- TOP NAVIGATION BAR -->
<nav class="topnav navbar navbar-expand shadow justify-content-between justify-content-sm-start navbar-light bg-white" id="sidenavAccordion">
    <!-- Sidenav Toggle Button-->
    <button class="btn btn-icon btn-transparent-dark order-1 order-lg-0 me-2 ms-lg-2 me-lg-0" id="sidebarToggle">
        <i data-feather="menu"></i>
    </button>
    
    <!-- Navbar Brand-->
    <a class="navbar-brand pe-3 ps-4 ps-lg-2" href="?page=dashboard">FIS-APPS</a>
    
    <!-- Navbar Search Input (Desktop) -->
    <form class="form-inline me-auto d-none d-lg-block me-3">
        <div class="input-group input-group-joined input-group-solid">
            <input class="form-control pe-0" type="search" placeholder="Search" aria-label="Search" />
            <div class="input-group-text"><i data-feather="search"></i></div>
        </div>
    </form>
    
    <!-- Navbar Items-->
    <ul class="navbar-nav align-items-center ms-auto">
        
        <!-- Search Dropdown (Mobile) -->
        <li class="nav-item dropdown no-caret me-3 d-lg-none">
            <a class="btn btn-icon btn-transparent-dark dropdown-toggle" id="searchDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i data-feather="search"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-end p-3 shadow animated--fade-in-up" aria-labelledby="searchDropdown">
                <form class="form-inline me-auto w-100">
                    <div class="input-group input-group-joined input-group-solid">
                        <input class="form-control pe-0" type="text" placeholder="Search for..." aria-label="Search" />
                        <div class="input-group-text"><i data-feather="search"></i></div>
                    </div>
                </form>
            </div>
        </li>
        
        <!-- Alerts Dropdown-->
        <!--<li class="nav-item dropdown no-caret d-none d-sm-block me-3 dropdown-notifications">-->
            
        <!--    <a class="btn btn-icon btn-transparent-dark dropdown-toggle" id="navbarDropdownAlerts" href="javascript:void(0);" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">-->
        <!--        <i data-feather="bell"></i>-->
        <!--    </a>-->
        <!--    <div class="dropdown-menu dropdown-menu-end border-0 shadow animated--fade-in-up" aria-labelledby="navbarDropdownAlerts">-->
        <!--        <h6 class="dropdown-header dropdown-notifications-header">-->
        <!--            <i class="me-2" data-feather="bell"></i>-->
        <!--            Alerts Center-->
        <!--        </h6>-->
                <!-- Contoh alert item -->
        <!--        <a class="dropdown-item dropdown-notifications-item" href="#!">-->
        <!--            <div class="dropdown-notifications-item-icon bg-warning">-->
        <!--                <i data-feather="activity"></i>-->
        <!--            </div>-->
        <!--            <div class="dropdown-notifications-item-content">-->
        <!--                <div class="dropdown-notifications-item-content-details">December 29, 2021</div>-->
        <!--                <div class="dropdown-notifications-item-content-text">This is an alert message.</div>-->
        <!--            </div>-->
        <!--        </a>-->
        <!--        <a class="dropdown-item dropdown-notifications-footer" href="#!">View All Alerts</a>-->
        <!--    </div>-->
        <!--</li>-->
        
        <!-- Messages Dropdown dengan Notifikasi Pending Orders Start -->
        <li class="nav-item dropdown no-caret d-none d-sm-block me-3 dropdown-notifications" style="position: relative;">
            <a class="btn btn-icon btn-transparent-dark dropdown-toggle" 
               id="navbarDropdownMessages" 
               href="javascript:void(0);" 
               role="button" 
               data-bs-toggle="modal" 
               data-bs-target="#pendingOrdersModal"
               style="position: relative;">
                <i data-feather="mail"></i>
            </a>
            <!-- Badge Notifikasi - Outside link tag -->
            <span class="badge badge-notification" 
                  id="pendingOrderBadge" 
                  style="display: none;">0</span>
        </li>
        <!-- Messages Dropdown dengan Notifikasi Pending Orders End -->
        
        <!-- User Dropdown-->
        <li class="nav-item dropdown no-caret dropdown-user me-3 me-lg-4">
            <a class="btn btn-icon btn-transparent-dark dropdown-toggle" id="navbarDropdownUserImage" href="javascript:void(0);" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <img class="img-fluid" src="<?php echo $headerProfileImage; ?>" style="
                width: 40px;
                height: 40px;
                object-fit: cover;
                border-radius: 50%;
                border: 2px solid #007BFF;
                box-shadow: 0 0 8px rgba(0, 110, 255, 0.6);
            " />
            </a>
            <div class="dropdown-menu dropdown-menu-end border-0 shadow animated--fade-in-up" aria-labelledby="navbarDropdownUserImage">
                <h6 class="dropdown-header d-flex align-items-center">
                    <img class="dropdown-user-img"
                     src="<?php echo $headerProfileImage; ?>"
                     style="
                        width: 40px;
                        height: 40px;
                        object-fit: cover;
                        border-radius: 50%;
                        border: 2px solid #007BFF;
                        box-shadow: 0 0 8px rgba(0, 110, 255, 0.6);
                     " />
                    <div class="dropdown-user-details">
                        <div class="dropdown-user-details-name"><?php echo isset($_SESSION['nama']) ? $_SESSION['nama'] : 'User'; ?></div>
                        <div class="dropdown-user-details-email"><?php echo isset($_SESSION['role']) ? $_SESSION['role'] : 'Role'; ?></div>
                    </div>
                </h6>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="?page=profile" data-link>
                    <div class="dropdown-item-icon"><i data-feather="user"></i></div>
                    Profile
                </a>
                <a class="dropdown-item" href="pages/logout.php">
                    <div class="dropdown-item-icon"><i data-feather="log-out"></i></div>
                    Logout
                </a>
            </div>
        </li>
    </ul>
</nav>

<!-- Modal Pending Orders Start -->
<div class="modal fade" id="pendingOrdersModal" tabindex="-1" aria-labelledby="pendingOrdersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="pendingOrdersModalLabel">
                    <i data-feather="inbox" class="me-2"></i>
                    Pending Material Requests
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Loading Spinner -->
                <div id="pendingOrdersLoading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading pending requests...</p>
                </div>
                
                <!-- Table Container -->
                <div id="pendingOrdersTable" style="display: none;">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped" id="pendingOrdersDataTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Request Number</th>
                                    <th>Request Date</th>
                                    <th>Type</th>
                                    <th>Requested By</th>
                                    <th>Warehouse</th>
                                    <th>Project</th>
                                    <th>Total Qty</th>
                                    <th>Status</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody id="pendingOrdersTableBody">
                                <!-- Data akan diisi via JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Empty State -->
                <div id="pendingOrdersEmpty" style="display: none;" class="text-center py-5">
                    <i data-feather="inbox" style="width: 64px; height: 64px; color: #ccc;"></i>
                    <h5 class="mt-3 text-muted">No Pending Requests</h5>
                    <p class="text-muted">All requests have been processed.</p>
                </div>
                
                <!-- Error State -->
                <div id="pendingOrdersError" style="display: none;" class="alert alert-danger">
                    <i data-feather="alert-circle" class="me-2"></i>
                    <span id="pendingOrdersErrorMessage"></span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="refreshPendingOrders">
                    <i data-feather="refresh-cw" class="me-1"></i>
                    Refresh
                </button>
            </div>
        </div>
    </div>
</div>
<!-- Modal Pending Orders Start -->