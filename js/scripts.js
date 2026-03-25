/*!
 * SB Admin Pro + SPA Loader Integration
 * FIS-APPS Custom Version (Modular JS Ready)
 * ----------------------------------------
 * Menjaga sidebar, header, footer tidak reload.
 * Memuat halaman dari folder /pages/ menggunakan AJAX.
 * Memuat module JS per halaman dari /assets/js/modules/
 */
window.addEventListener("DOMContentLoaded", (event) => {
  console.log("🚀 SPA Loader initializing...");

  // ==================== AUTO-DETECT BASE PATH ====================
 
  const getBasePath = () => {
    const path = window.location.pathname;
    // Cari base path (contoh: /inventory-final/)
    const match = path.match(/^(\/[^\/]+\/)/);
    if (match) {
      return match[1]; // return /inventory-final/
    }
    return '/'; // jika di root
  };

  const BASE_PATH = getBasePath();
  console.log(`📁 Detected BASE_PATH: ${BASE_PATH}`);

  // ==================== SB ADMIN DEFAULT ====================
  feather.replace();

  // Tooltip global
  document
    .querySelectorAll("[data-bs-toggle='tooltip']")
    .forEach((el) => new bootstrap.Tooltip(el));

  // Popover global
  document
    .querySelectorAll("[data-bs-toggle='popover']")
    .forEach((el) => new bootstrap.Popover(el));

  // Sidebar toggle
  const sidebarToggle = document.body.querySelector("#sidebarToggle");
  if (sidebarToggle) {
    sidebarToggle.addEventListener("click", (event) => {
      event.preventDefault();
      document.body.classList.toggle("sidenav-toggled");
      localStorage.setItem(
        "sb|sidebar-toggle",
        document.body.classList.contains("sidenav-toggled")
      );
    });
  }

  // Tutup sidebar otomatis di layar kecil
  const sidenavContent = document.body.querySelector("#layoutSidenav_content");
  if (sidenavContent) {
    sidenavContent.addEventListener("click", (event) => {
      if (window.innerWidth < 992 && document.body.classList.contains("sidenav-toggled")) {
        document.body.classList.toggle("sidenav-toggled");
      }
    });
  }

  // ==================== SPA HANDLER ====================
  const mainContent = document.getElementById("main-content");
  const links = document.querySelectorAll("[data-page]");

  // ======================================================
  // 🔹 Helper: Extract page name dari URL
  // ======================================================
  function getPageFromURL() {
    // Cek query string dulu (?page=)
    const urlParams = new URLSearchParams(window.location.search);
    const pageParam = urlParams.get("page");
    if (pageParam) {
      return pageParam;
    }
    
    // Cek path (untuk clean URL)
    const path = window.location.pathname.replace(BASE_PATH, '/');
    const match = path.match(/\/([a-zA-Z0-9_-]+)\/?$/);
    if (match && match[1] !== 'index') {
    return match[1];
  }
    
    return "dashboard";
  }

  // ======================================================
  // 🔹 Fungsi untuk update active menu
  // ======================================================
  function updateActiveMenu(pageName) {
    console.log(`🎯 Setting active menu: ${pageName}`);
    
    // Remove active dari semua link
    links.forEach((link) => {
      link.classList.remove("active");
    });
    
    // Add active ke menu yang sesuai
    links.forEach((link) => {
      if (link.getAttribute("data-page") === pageName) {
        link.classList.add("active");
        
        // ✅ PERBAIKAN: Buka parent collapse jika menu ada di dalam dropdown
        const parentCollapse = link.closest('.collapse');
        if (parentCollapse) {
          // Gunakan Bootstrap collapse API untuk membuka
          const bsCollapse = new bootstrap.Collapse(parentCollapse, {
            toggle: false
          });
          bsCollapse.show();
          
          // Update aria-expanded pada trigger link
          const collapseId = parentCollapse.id;
          const triggerLink = document.querySelector(`[data-bs-target="#${collapseId}"]`);
          if (triggerLink) {
            triggerLink.classList.remove('collapsed');
            triggerLink.setAttribute('aria-expanded', 'true');
          }
          
          console.log(`✅ Parent collapse opened: ${collapseId}`);
        }
      }
    });
  }

  // ======================================================
  // 🔹 Fungsi untuk memuat halaman via AJAX
  // ======================================================
  function loadPage(pageName, push = true) {
    console.log(`📄 Loading page: ${pageName}, push: ${push}`);

    // Spinner sementara
    mainContent.innerHTML = `
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
        
    </div>
      </div>
    `;

    // ✅ CLEANUP: Panggil cleanup global
    if (typeof window.cleanupPage === 'function') {
      try {
        window.cleanupPage();
        console.log("🧹 Global cleanup completed");
      } catch (err) {
        console.error('❌ Error during cleanup:', err);
      }
    }

    fetch(`pages/${pageName}.php?v=${Date.now()}`)
      .then((res) => {
        if (!res.ok) throw new Error("Halaman tidak ditemukan");
        return res.text();
      })
      .then((html) => {
        mainContent.innerHTML = html;

        // ======================================================
        // 🔸 CLEAN URL: Format /pagename (bukan ?page=)
        // ======================================================
        if (push) {
      // ✅ PERBAIKAN: Include BASE_PATH
      const newUrl = `${BASE_PATH}${pageName}`;
      window.history.pushState({ page: pageName }, "", newUrl);
      console.log(`✅ URL pushed: ${window.location.href}`);
    } else {
      // ✅ PERBAIKAN: Include BASE_PATH
      const newUrl = `${BASE_PATH}${pageName}`;
      window.history.replaceState({ page: pageName }, "", newUrl);
      console.log(`✅ URL replaced: ${window.location.href}`);
    }

        // ======================================================
        // 🔸 Update active menu
        // ======================================================
        updateActiveMenu(pageName);

        // ======================================================
        // 🔸 Reaktifkan Feather & Bootstrap (tooltip, popover)
        // ======================================================
        feather.replace();

        // Destroy tooltip lama
        document.querySelectorAll('.tooltip').forEach(el => el.remove());

        document
          .querySelectorAll("[data-bs-toggle='tooltip']")
          .forEach((el) => {
            const existingTooltip = bootstrap.Tooltip.getInstance(el);
            if (existingTooltip) {
              existingTooltip.dispose();
            }
            new bootstrap.Tooltip(el);
          });

        document
          .querySelectorAll("[data-bs-toggle='popover']")
          .forEach((el) => {
            const existingPopover = bootstrap.Popover.getInstance(el);
            if (existingPopover) {
              existingPopover.dispose();
            }
            new bootstrap.Popover(el);
          });

        loadModuleScript(pageName);
      })
      .catch((err) => {
        console.error("❌ Error loading page:", err);
        mainContent.innerHTML = `
          <div class="alert alert-danger text-center mt-5">
            <i class="fas fa-exclamation-triangle"></i> ${err.message}
          </div>
        `;
      });
  }

  // ======================================================
  // 🔹 Fungsi untuk memuat JS module per halaman
  // ======================================================
  function loadModuleScript(pageName) {
    // Hapus script module lama (agar tidak double binding)
    document.querySelectorAll('script[data-module]').forEach((s) => {
      console.log(`🗑️ Removing old module: ${s.dataset.module}`);
      s.remove();
    });

    // Buat script baru
    const moduleScript = document.createElement("script");
    moduleScript.src = `assets/js/modules/${pageName}.js?v=${Date.now()}`;
    moduleScript.defer = true;
    moduleScript.dataset.module = pageName; // penanda

    // Setelah script dimuat, jalankan initPageScripts()
    moduleScript.onload = () => {
      console.log(`✅ Modul ${pageName}.js berhasil dimuat`);
      if (typeof initPageScripts === "function") {
        try {
          initPageScripts();
          console.log(`✅ initPageScripts() executed for ${pageName}`);
        } catch (err) {
          console.error(`❌ Error menjalankan initPageScripts() dari ${pageName}.js:`, err);
        }
      } else {
        console.warn(`⚠️ initPageScripts() tidak ditemukan di ${pageName}.js`);
      }
    };

    moduleScript.onerror = () => {
      console.warn(`⚠️ Tidak ada modul JS untuk halaman "${pageName}"`);
    };

    document.body.appendChild(moduleScript);
  }

  // ======================================================
  // 🔹 Event klik sidebar (ganti halaman SPA)
  // ======================================================
  links.forEach((link) => {
    link.addEventListener("click", (e) => {
      e.preventDefault();
      const page = link.getAttribute("data-page");

      // ✅ PERBAIKAN: Cek jika sudah di halaman yang sama
      const currentPage = getPageFromURL();
      if (page === currentPage) {
        console.log(`ℹ️ Already on page: ${page}`);
        return;
      }

      loadPage(page);
    });
  });

  // ======================================================
  // 🔹 Cek URL dan muat halaman pertama
  // ======================================================
  const defaultPage = getPageFromURL();

  console.log(`🔍 Initial URL: ${window.location.href}`);
  console.log(`📄 Loading initial page: ${defaultPage}`);

  // ✅ PERBAIKAN: Load dengan push=false agar tidak duplikat di history
  loadPage(defaultPage, false);

  // ======================================================
  // 🔹 Tangani tombol back / forward browser
  // ======================================================
  window.addEventListener("popstate", (e) => {
    // ✅ PERBAIKAN: Ambil dari state atau dari URL
    const page = e.state?.page || getPageFromURL();
    console.log(`⬅️ Browser navigation: ${page}`);

    // ✅ PERBAIKAN: Load dengan push=false karena sudah handled oleh browser
    loadPage(page, false);
  });

  console.log("✅ SPA Loader initialized successfully");
});