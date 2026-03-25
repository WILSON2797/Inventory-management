/*!
 * SB Admin Pro + SPA Loader Integration
 * FIS-APPS Custom Version
 * ----------------------------------------
 * Menjaga sidebar, header, footer tidak reload.
 * Memuat halaman dari folder /pages/ menggunakan AJAX.
 * Re-inject assets/js/script.js di setiap pergantian halaman.
 */

window.addEventListener("DOMContentLoaded", (event) => {
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

  // Fungsi untuk memuat halaman via AJAX
  function loadPage(pageName, push = true) {
    // Spinner sementara
    mainContent.innerHTML = `
      <div class="d-flex justify-content-center align-items-center" style="min-height:300px;">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
      </div>
    `;

    fetch(`pages/${pageName}.php?v=${Date.now()}`)
      .then((res) => {
        if (!res.ok) throw new Error("Halaman tidak ditemukan");
        return res.text();
      })
      .then((html) => {
        mainContent.innerHTML = html;
// ================================
        // 👇 Tambahan untuk ubah URL tanpa index.php
        // ================================
        if (push) {
          const newUrl = `page=${pageName}`;
          window.history.pushState({ page: pageName }, "", newUrl);
        }
        // ================================

        // Reaktifkan Feather & Bootstrap
        feather.replace();
        document
          .querySelectorAll("[data-bs-toggle='tooltip']")
          .forEach((el) => new bootstrap.Tooltip(el));
        document
          .querySelectorAll("[data-bs-toggle='popover']")
          .forEach((el) => new bootstrap.Popover(el));

        // Muat ulang script module kamu (assets/js/script.js)
        reloadAppScript();

        // Jalankan initPageScripts() jika ada di halaman
        if (typeof initPageScripts === "function") {
          try {
            initPageScripts();
          } catch (err) {
            console.error("Error initPageScripts:", err);
          }
        }
      })
      .catch((err) => {
        mainContent.innerHTML = `<div class="alert alert-danger text-center mt-5">${err.message}</div>`;
      });
  }

  // Fungsi untuk reload assets/js/script.js
  function reloadAppScript() {
    const oldScript = document.querySelector('script[src*="assets/js/script.js"]');
    if (oldScript) oldScript.remove();

    const newScript = document.createElement("script");
    newScript.src = `assets/js/script.js?v=${Date.now()}`;
    newScript.defer = true;
    document.body.appendChild(newScript);
  }

  // Event klik sidebar
  links.forEach((link) => {
    link.addEventListener("click", (e) => {
      e.preventDefault();
      const page = link.getAttribute("data-page");
      links.forEach((l) => l.classList.remove("active"));
      link.classList.add("active");
      loadPage(page);
    });
  });

  // Cek URL ?page=
  const urlParams = new URLSearchParams(window.location.search);
  const defaultPage = urlParams.get("page") || "dashboard";
  loadPage(defaultPage, false);

  // Tangani tombol back / forward
  window.addEventListener("popstate", (e) => {
    const page = e.state ? e.state.page : "dashboard";
    loadPage(page, false);
  });
});
