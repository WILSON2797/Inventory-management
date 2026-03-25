<div id="layoutSidenav">
  <div id="layoutSidenav_nav">
    <nav class="sidenav shadow-right sidenav-light">
      <div class="sidenav-menu">
        <div class="nav accordion" id="accordionSidenav">

          <!-- Mobile Account -->
          <!--<div class="sidenav-menu-heading d-sm-none">Account</div>-->
          <!--<a class="nav-link d-sm-none" href="#!">-->
          <!--  <div class="nav-link-icon"><i data-feather="bell"></i></div>-->
          <!--  Alerts-->
          <!--  <span class="badge bg-warning-soft text-warning ms-auto">4 New!</span>-->
          <!--</a>-->

          <!--<a class="nav-link d-sm-none" href="#!">-->
          <!--  <div class="nav-link-icon"><i data-feather="mail"></i></div>-->
          <!--  Messages-->
          <!--  <span class="badge bg-success-soft text-success ms-auto">2 New!</span>-->
          <!--</a>-->

          <!-- INVENTORY -->
          <div class="sidenav-menu-heading">Inventory</div>

          <!-- Dashboard -->
          <a class="nav-link active spa-link" href="#" data-page="dashboard">
            <div class="nav-link-icon"><i data-feather="activity"></i></div>
            Dashboard
          </a>
          
          <div class="sidenav-menu-heading">Main Menu</div>

          <!-- WH Management -->
          <a class="nav-link collapsed" href="javascript:void(0);" data-bs-toggle="collapse"
            data-bs-target="#collapseFlows" aria-expanded="false" aria-controls="collapseFlows">
            <div class="nav-link-icon"><i data-feather="repeat"></i></div>
            WH Management
            <div class="sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
          </a>

          <div class="collapse" id="collapseFlows" data-bs-parent="#accordionSidenav">
            <nav class="sidenav-menu-nested nav">
              <a class="nav-link" href="#" data-page="Inbound">
                <div class="nav-link-icon"><i data-feather="download"></i></div>
                Manage Inbound
              </a>
              <a class="nav-link spa-link" href="#" data-page="AllocatedItems">
                <div class="nav-link-icon"><i data-feather="package"></i></div>
                Allocated Items
              </a>
              <a class="nav-link spa-link" href="#" data-page="Outbound">
                <div class="nav-link-icon"><i data-feather="upload"></i></div>
                Outbound
              </a>
              <a class="nav-link spa-link" href="#" data-page="StockDetails">
                <div class="nav-link-icon"><i data-feather="archive"></i></div>
                Stock Details
              </a>
            </nav>
          </div>
          
          <!-- Request Item -->
          <div class="sidenav-menu-heading">Request Item</div>

          <a class="nav-link collapsed" href="javascript:void(0);" data-bs-toggle="collapse"
            data-bs-target="#collapseRequestMaterials" aria-expanded="false" aria-controls="collapseRequestMaterials">
            <div class="nav-link-icon"><i data-feather="check-square"></i></div>
            Request Stock
            <div class="sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
          </a>

          <div class="collapse" id="collapseRequestMaterials" data-bs-parent="#accordionSidenav">
            <nav class="sidenav-menu-nested nav">
              <a class="nav-link spa-link" href="#" data-page="RequestList">
                <div class="nav-link-icon"><i data-feather="shopping-cart"></i></div>
                Request Stock
              </a>
            </nav>
          </div>

          <!-- SETTINGS -->
          <div class="sidenav-menu-heading">Settings</div>
            <?php
          
          if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
          ?>
          <!-- User Management -->
          <a class="nav-link collapsed" href="javascript:void(0);" data-bs-toggle="collapse"
            data-bs-target="#collapseComponents" aria-expanded="false" aria-controls="collapseComponents">
            <div class="nav-link-icon"><i data-feather="users"></i></div>
            User Management
            <div class="sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
          </a>

          <div class="collapse" id="collapseComponents" data-bs-parent="#accordionSidenav">
            <nav class="sidenav-menu-nested nav">
              <a class="nav-link spa-link" href="#" data-page="UserSetting">
                <div class="nav-link-icon"><i data-feather="user"></i></div>
                User Setting
              </a>
            </nav>
          </div>
            <?php
          }
          ?>
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
              <a class="nav-link spa-link" href="#" data-page="MasterLocator">
                <div class="nav-link-icon"><i data-feather="map-pin"></i></div>
                Master Locator
              </a>
            </nav>
          </div>

          
          
          <?php
          
          if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
          ?>
          <div class="sidenav-menu-heading">Resources</div>
          
          <!-- Email Recipient Settings -->
          <a class="nav-link collapsed" href="javascript:void(0);" data-bs-toggle="collapse"
            data-bs-target="#collapseEmailRecipients" aria-expanded="false" aria-controls="collapseEmailRecipients">
            <div class="nav-link-icon"><i data-feather="mail"></i></div>
            Email Recipients
            <div class="sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
          </a>

          <div class="collapse" id="collapseEmailRecipients" data-bs-parent="#accordionSidenav">
            <nav class="sidenav-menu-nested nav">
              <a class="nav-link spa-link" href="#" data-page="AddRecipientMail">
                <div class="nav-link-icon"><i data-feather="plus-circle"></i></div>
                Manage Recipients
              </a>
              <a class="nav-link spa-link" href="#" data-page="NotificationMail">
              <div class="nav-link-icon"><i data-feather="bell"></i></div>
              Notification Mail
              </a>
            </nav>
          </div>
          <?php
          }
          ?>


          <!-- LOG -->
          <div class="sidenav-menu-heading">LOG</div>
          <a class="nav-link spa-link" href="#" data-page="UploadLogStatus">
            <div class="nav-link-icon"><i data-feather="upload"></i></div>
            Bulk Upload Log
          </a>

        </div>
      </div>
    </nav>
  </div>

  <div id="layoutSidenav_content">