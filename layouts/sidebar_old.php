<div id="layoutSidenav">
  <div id="layoutSidenav_nav">
    <nav class="sidenav shadow-right sidenav-light">
      <div class="sidenav-menu">
        <div class="nav accordion" id="accordionSidenav">

          <!-- Mobile Account -->
          <div class="sidenav-menu-heading d-sm-none">Account</div>
          <a class="nav-link d-sm-none" href="#!">
            <div class="nav-link-icon"><i data-feather="bell"></i></div>
            Alerts
            <span class="badge bg-warning-soft text-warning ms-auto">4 New!</span>
          </a>

          <a class="nav-link d-sm-none" href="#!">
            <div class="nav-link-icon"><i data-feather="mail"></i></div>
            Messages
            <span class="badge bg-success-soft text-success ms-auto">2 New!</span>
          </a>

          <!-- INVENTORY -->
          <div class="sidenav-menu-heading">Inventory</div>

          <!-- Dashboard -->
          <a class="nav-link active spa-link" href="#" data-page="dashboard">
            <div class="nav-link-icon"><i data-feather="activity"></i></div>
            Dashboard
          </a>

          <!-- WH Management -->
          <a class="nav-link collapsed" href="javascript:void(0);" data-bs-toggle="collapse"
            data-bs-target="#collapseFlows" aria-expanded="false" aria-controls="collapseFlows">
            <div class="nav-link-icon"><i data-feather="repeat"></i></div>
            WH Management
            <div class="sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
          </a>

          <div class="collapse" id="collapseFlows" data-bs-parent="#accordionSidenav">
            <nav class="sidenav-menu-nested nav">
              <a class="nav-link" href="#" data-page="inbound_content">
                <div class="nav-link-icon"><i data-feather="download"></i></div>
                Manage Inbound
              </a>
              <a class="nav-link spa-link" href="#" data-page="item_allocated">
                <div class="nav-link-icon"><i data-feather="package"></i></div>
                Allocated Items
              </a>
              <a class="nav-link spa-link" href="#" data-page="outbound">
                <div class="nav-link-icon"><i data-feather="upload"></i></div>
                Outbound
              </a>
              <a class="nav-link spa-link" href="#" data-page="StockDetails">
                <div class="nav-link-icon"><i data-feather="archive"></i></div>
                Stock Details
              </a>
            </nav>
          </div>

          <!-- SETTINGS -->
          <div class="sidenav-menu-heading">Settings</div>

          <!-- User Management -->
          <a class="nav-link collapsed" href="javascript:void(0);" data-bs-toggle="collapse"
            data-bs-target="#collapseComponents" aria-expanded="false" aria-controls="collapseComponents">
            <div class="nav-link-icon"><i data-feather="users"></i></div>
            User Management
            <div class="sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
          </a>

          <div class="collapse" id="collapseComponents" data-bs-parent="#accordionSidenav">
            <nav class="sidenav-menu-nested nav">
              <a class="nav-link spa-link" href="#" data-page="user_setting">
                <div class="nav-link-icon"><i data-feather="user"></i></div>
                User Setting
              </a>
            </nav>
          </div>

          <!-- WH Settings -->
          <a class="nav-link collapsed" href="javascript:void(0);" data-bs-toggle="collapse"
            data-bs-target="#collapseUtilities" aria-expanded="false" aria-controls="collapseUtilities">
            <div class="nav-link-icon"><i data-feather="settings"></i></div>
            WH Settings
            <div class="sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
          </a>

          <div class="collapse" id="collapseUtilities" data-bs-parent="#accordionSidenav">
            <nav class="sidenav-menu-nested nav">
              <a class="nav-link spa-link" href="#" data-page="MasterSku">
                <div class="nav-link-icon"><i data-feather="box"></i></div>
                Master SKU
              </a>
              <a class="nav-link spa-link" href="#" data-page="master_locator">
                <div class="nav-link-icon"><i data-feather="map-pin"></i></div>
                Master Locator
              </a>
            </nav>
          </div>

          <!-- LOG -->
          <div class="sidenav-menu-heading">LOG</div>
          <a class="nav-link spa-link" href="#" data-page="Upload_log_Status">
            <div class="nav-link-icon"><i data-feather="upload"></i></div>
            Bulk Upload Log
          </a>

        </div>
      </div>
    </nav>
  </div>

  <div id="layoutSidenav_content">
