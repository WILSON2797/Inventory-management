// ======================================================
// GLOBAL FUNCTIONS - LOAD ONCE (SPA READY)
// ======================================================
// File ini di-load SEKALI di index.html dan tidak perlu reload
(function() {
    'use strict';

    // Cegah double initialization
    if (window.FIS_GLOBAL_LOADED) {
        console.warn("⚠️ Global functions already loaded, skipping re-initialization");
        return;
    }

    console.log("🚀 Loading global functions...");

    // ======================================================
    // SESSION MANAGEMENT
    // ======================================================
    const SESSION_CONFIG = {
        idleTimeout: 1200000,    // 20 menit
        warningTimeout: 1140000, // 19 menit
        checkInterval: 60000     // 1 menit
    };

    let idleTimer, warningTimer, sessionExtended = false;
    let lastServerUpdate = Date.now();

    // PERBAIKAN 1: Update activity ke server secara periodik
    function updateLastActivity() {
        try {
            window.tempStorage = window.tempStorage || {};
            window.tempStorage.lastActivity = Date.now();
            
            // Update ke server setiap 30 detik sekali (throttling)
            if (Date.now() - lastServerUpdate > SESSION_CONFIG.updateInterval) {
                $.ajax({
                    url: "update_activity.php",
                    type: "POST",
                    dataType: "json",
                    cache: false,
                    success: function(response) {
                        if (response.status === "updated") {
                            lastServerUpdate = Date.now();
                            console.log("✅ Activity updated on server");
                        }
                    },
                    error: function() {
                        console.warn("⚠️ Failed to update activity on server");
                    }
                });
            }
            
            resetIdleTimer();
        } catch (e) {
            console.warn('Storage not available:', e);
        }
    }

    function showWarning() {
        Swal.fire({
            icon: "warning",
            title: "Session About to Expire",
            text: "Your session will expire in 1 minute. Do you want to extend your session?",
            showCancelButton: true,
            confirmButtonText: "Yes, Extend Session",
            cancelButtonText: "No, Logout",
            timer: 60000,
            timerProgressBar: true,
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "extend_session.php",
                    type: "POST",
                    success: function (response) {
                        if (response.status === "extended") {
                            sessionExtended = true;
                            lastServerUpdate = Date.now();
                            updateLastActivity();
                            Swal.fire({
                                icon: "success",
                                title: "Session Extended",
                                text: "Your session has been extended.",
                                timer: 2000,
                                showConfirmButton: false,
                            });
                        }
                    },
                    error: function () {
                        Swal.fire({
                            icon: "error",
                            title: "Error",
                            text: "Failed to extend session. Please login again.",
                        }).then(() => {
                            window.location.href = "/login?message=session_expired";
                        });
                    },
                });
            } else {
                clearTimeout(idleTimer);
                Swal.fire({
                    icon: "warning",
                    title: "Session Time Expired",
                    text: "Please login again.",
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: true,
                    confirmButtonText: "Login Again",
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = "/login?message=session_expired";
                    }
                });
            }
        });
    }

    function resetIdleTimer() {
        clearTimeout(idleTimer);
        clearTimeout(warningTimer);
        sessionExtended = false;

        warningTimer = setTimeout(() => {
            if (!sessionExtended) showWarning();
        }, SESSION_CONFIG.warningTimeout);

        idleTimer = setTimeout(() => {
            if (!sessionExtended) {
                clearTimeout(warningTimer);
                Swal.fire({
                    icon: "warning",
                    title: "Session Time Expired",
                    text: "Please login again.",
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: true,
                    confirmButtonText: "Login Again",
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = "/login?message=session_expired";
                    }
                });
            }
        }, SESSION_CONFIG.idleTimeout);
    }

    // Event delegation untuk activity tracking (SPA friendly)
    $(document).on("mousemove keydown click", updateLastActivity);

    // PERBAIKAN 2: Hapus interval check yang tidak perlu
    // Tidak perlu check lastActivity dari tempStorage karena sudah di-handle oleh idleTimer

    // PERBAIKAN 3: Server-side session check - lebih jarang (setiap 2 menit)
    setInterval(() => {
        $.ajax({
            url: "check_session.php",
            type: "GET",
            dataType: "json",
            cache: false,
            success: function (response) {
                if (response.status === "expired") {
                    clearTimeout(idleTimer);
                    clearTimeout(warningTimer);
                    Swal.fire({
                        icon: "warning",
                        title: "Session Time Expired",
                        text: "Please login again.",
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: true,
                        confirmButtonText: "Login Again",
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = "/login.php?message=session_expired";
                        }
                    });
                }
            },
            error: function () {
                console.error("Failed to check session on server");
            },
        });
    }, 120000); // Ubah dari 60000 (1 menit) ke 120000 (2 menit)

    // Initialize idle timer
    resetIdleTimer();

    // ======================================================
    // DATATABLE SEARCH BOX
    // ======================================================
    window.initDataTableSearch = function(api) {
    // Fungsi debounce untuk menunda eksekusi sampai user berhenti mengetik
    function debounce(fn, delay = 400) {
        let timer;
        return function (...args) {
            clearTimeout(timer);
            timer = setTimeout(() => fn.apply(this, args), delay);
        };
    }

    api.columns().eq(0).each(function (colIdx) {
        if (colIdx === api.columns().count() - 1) return; // skip kolom terakhir (misal "Action")

        const $input = $('input', api.column(colIdx).header());
        if ($input.length && !$input.data('init-search')) {

            // Tandai agar tidak di-bind ulang
            $input.data('init-search', true);

            // Handle Ctrl + A
            $input.on('keydown', function (e) {
                if (e.ctrlKey && e.keyCode === 65) {
                    e.preventDefault();
                    $(this).select();
                }
            });

            // Event ketik dengan debounce agar tidak draw setiap karakter
            $input.on('keyup change clear', debounce(function (e) {
                const value = this.value;
                api.column(colIdx).search(value).draw();
            }, 400));

            // Klik fokus + select text
            $input.on('click', function (e) {
                e.stopPropagation();
                $(this).focus().select();
            });
        }
    });
};

    // ======================================================
    // TOOLTIP INITIALIZATION (Event Delegation untuk SPA)
    // ======================================================
    // Inisialisasi tooltip untuk elemen dengan attribute [title]
    $(document).ready(function() {
        initializeTooltips();
    });

    // Function untuk inisialisasi semua tooltip
    function initializeTooltips() {
        $('button[title], a[title], [data-bs-toggle="tooltip"]').each(function() {
            if (!$(this).data('bs-tooltip-initialized')) {
                new bootstrap.Tooltip(this, {
                    container: 'body',
                    boundary: 'viewport',
                    placement: $(this).data('bs-placement') || 'top',
                    trigger: 'hover'
                });
                $(this).data('bs-tooltip-initialized', true);
            }
        });
    }
    
    // Re-initialize tooltip setelah konten dinamis dimuat (untuk SPA)
    $(document).on('DOMNodeInserted', function() {
        // Debounce untuk performance
        clearTimeout(window.tooltipTimeout);
        window.tooltipTimeout = setTimeout(function() {
            initializeTooltips();
        }, 100);
    });
    
    // Event delegation untuk mouseenter (backup untuk elemen dinamis)
    $(document).on('mouseenter', '[data-bs-toggle="tooltip"], button[title], a[title]', function () {
        if (!$(this).data('bs-tooltip-initialized')) {
            if ($(this).is(':visible')) {
                const tooltip = new bootstrap.Tooltip(this, {
                    container: 'body',
                    boundary: 'viewport',
                    placement: $(this).data('bs-placement') || 'top',
                    trigger: 'hover'
                });
                $(this).data('bs-tooltip-initialized', true);
                tooltip.show();
            }
        } else {
            const existingTooltip = bootstrap.Tooltip.getInstance(this);
            if (existingTooltip) {
                existingTooltip.show();
            }
        }
    });
    
    // Event untuk hide tooltip
    $(document).on('mouseleave', '[data-bs-toggle="tooltip"], button[title], a[title]', function () {
        const tooltipInstance = bootstrap.Tooltip.getInstance(this);
        if (tooltipInstance) {
            tooltipInstance.hide();
        }
    });
    
    // Dispose tooltip ketika elemen akan dihapus (cleanup)
    $(document).on('DOMNodeRemoved', function(e) {
        const tooltipInstance = bootstrap.Tooltip.getInstance(e.target);
        if (tooltipInstance) {
            tooltipInstance.dispose();
        }
    });

    // ======================================================
    // DROPDOWN STOCK TYPE
    // ======================================================
    window.loadStockTypeDropdown = function(modalId) {
        return $.ajax({
            url: "API/get_stock_type",
            type: "GET",
            success: function (data) {
                $("#stock_type")
                    .empty()
                    .append('<option value=""></option>')
                    .append(data)
                    .select2({
                        placeholder: "Pilih Option",
                        allowClear: true,
                        dropdownParent: $(modalId)
                    })
                    .val(null)
                    .trigger("change");
            },
            error: function () {
                Swal.fire({
                    icon: "error",
                    title: "Gagal",
                    text: "Gagal memuat Stock Type dari server!"
                });
            }
        });
    };

    // ======================================================
    // DROPDOWN SKU
    // ======================================================
   window.loadSKUDropdown = function ($select, index, modalId) {

    // Destroy Select2 sebelumnya kalau ada
    try {
        $select.select2("destroy");
    } catch (e) {
        console.log("Select2 belum diinisialisasi sebelumnya");
    }

    // Inisialisasi Select2 dengan AJAX lazy loading
    $select.select2({
        width: "100%",
        dropdownParent: modalId ? $(modalId) : null,
        allowClear: true,
        placeholder: "Pilih Item Code",
        minimumInputLength: 0,
        ajax: {
            url: "API/dropdown_sku",
            dataType: "json",
            delay: 250, // debounce untuk efisiensi request
            data: function (params) {
                return {
                    search: params.term || "", // parameter pencarian dikirim ke backend
                };
            },
            processResults: function (data) {
                return {
                    results: data.map(item => ({
                        id: item.item_code,
                        text: `${item.item_code} - ${item.item_description}`,
                        description: item.item_description,
                        uom: item.uom
                    }))
                };
            },
            cache: true
        },
        language: {
            // inputTooShort: () => "Ketik minimal 2 huruf...",
            noResults: () => "Data tidak ditemukan",
            searching: () => "Mencari..."
        }
    });

    // ===============================
    // Auto focus dan custom placeholder search
    // ===============================
    $select.on("select2:open", function (e) {
    // Jalankan setelah dropdown selesai render
    let searchField = document.querySelector(".select2-container--open .select2-search__field");
    if (searchField) {
        searchField.setAttribute("placeholder", "Ketik Disini Untuk Mencari..!");
        searchField.focus();
    } else {
        // Jika DOM belum sempat render, tunggu sedikit
        setTimeout(() => {
            let retryField = document.querySelector(".select2-container--open .select2-search__field");
            if (retryField) {
                retryField.setAttribute("placeholder", "Ketik Disini Untuk Mencari..!");
                retryField.focus();
            }
        }, 150);
    }
});

    // ===============================
    // Saat user memilih item
    // ===============================
    $select.off('change').on("change", function () {
        const selectedData = $(this).select2('data')[0];
        if (selectedData) {
            $(`input[name="items[${index}][item_description]"]`).val(selectedData.description);
            $(`input[name="items[${index}][uom]"]`).val(selectedData.uom);
        } else {
            $(`input[name="items[${index}][item_description]"]`).val("");
            $(`input[name="items[${index}][uom]"]`).val("");
        }
    });
};

    // ======================================================
    // DROPDOWN LOCATOR
    // ======================================================
    window.loadLocatorDropdown = function($select, modalId) {
        return $.ajax({
            url: "API/dropdownlist",
            type: "GET",
            data: {
                table: "master_locator",
                column: "locator",
                display: "locator",
                wh_name: window.whName || ""
            },
            dataType: "json",
            success: function (response) {
                if (response.status === "success" && response.data) {
                    $select.empty().append('<option value="">Pilih Locator</option>');
                    response.data.forEach(item => {
                        $select.append(new Option(item.text, item.id));
                    });

                    // Destroy select2 jika sudah ada
                    if ($select.hasClass('select2-hidden-accessible')) {
                        $select.select2('destroy');
                    }

                    $select.select2({
                        width: "100%",
                        dropdownParent: modalId ? $(modalId) : null,
                        allowClear: true,
                        placeholder: "Pilih Locator",
                        language: {
                            noResults: () => "Data locator tidak ditemukan",
                            searching: () => "Mencari..."
                        },
                    });
                } else {
                    console.error("Failed to load Locator:", response.message || "Data kosong");
                    $select.empty().append('<option value="">Pilih Locator</option>');
                }
            },
            error: function (xhr) {
                console.error("Error loading Locator:", xhr.responseText);
                Swal.fire({
                    icon: "error",
                    title: "Gagal",
                    text: "Gagal memuat data locator. Silakan periksa koneksi atau server.",
                });
                $select.empty().append('<option value="">Pilih Locator</option>');
            },
        });
    };

    // ======================================================
    // GENERIC DROPDOWN DATA LOADER
    // ======================================================
    window.loadDropdownData = function($select, table, column, display, modalId) {
        return $.ajax({
            url: "API/dropdownlist",
            type: "GET",
            data: { table, column, display },
            dataType: "json",
            success: function (response) {
                if (response.status === "success") {
                    // DIPERBAIKI: Tambahkan backticks
                    $select.empty().append(`<option value="">Pilih ${display.charAt(0).toUpperCase() + display.slice(1)}</option>`);
                    response.data.forEach(item => {
                        $select.append(new Option(item.text, item.id));
                    });

                    // Destroy select2 jika sudah ada
                    if ($select.hasClass('select2-hidden-accessible')) {
                        $select.select2('destroy');
                    }

                    $select.select2({
                        width: "100%",
                        dropdownParent: modalId ? $(modalId) : null,
                        allowClear: true,
                        // DIPERBAIKI: Tambahkan backticks
                        placeholder: `Pilih ${display.charAt(0).toUpperCase() + display.slice(1)}`,
                        language: {
                            noResults: () => "Data tidak ditemukan",
                            searching: () => "Mencari..."
                        },
                    });
                } else {
                    console.error("Failed to load data:", response.message);
                }
            },
            error: function (xhr) {
                console.error("Error loading dropdown:", xhr.responseText);
            },
        });
    };

    // ======================================================
    // LOADING INDICATOR
    // ======================================================
    window.showLoading = function(message = 'Loading...') {
        Swal.fire({
            title: message,
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
    };

    window.hideLoading = function() {
        Swal.close();
    };

    // ======================================================
    // GENERATE PO NUMBER
    // ======================================================
    window.generatePoNumber = function(inputSelector, buttonSelector) {
        const button = $(buttonSelector);
        const poNumberInput = $(inputSelector);
        button.prop('disabled', true);

        return $.ajax({
            url: 'modules/generate_po_number',
            type: 'POST',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    poNumberInput.val(response.po_number);
                    poNumberInput.prop('readonly', true);
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: 'PO number berhasil di-generate',
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: response.message
                    });
                    button.prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Terjadi kesalahan saat generate PO number'
                });
                button.prop('disabled', false);
            }
        });
    };

    // ======================================================
    // CLEANUP FUNCTION (untuk destroy DataTable, Select2, dll saat page change)
    // ======================================================
    window.cleanupPage = function() {
        // Destroy all DataTables
        $.fn.dataTable.tables({ visible: true, api: true }).destroy();

        // Destroy all Select2
        $('.select2-hidden-accessible').select2('destroy');

        // Hide all tooltips
        $('.tooltip').remove();

        // Remove all SweetAlert
        Swal.close();

        console.log("🧹 Page cleanup completed");
    };
    
    //  fungsi timer
    
    window.initRealtimeClock = function() {
        function updateClock() {
            const now = new Date();
            
            // Convert ke WIB (GMT+7) menggunakan toLocaleString
            const options = {
                timeZone: 'Asia/Jakarta',
                year: 'numeric',
                month: 'long',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false
            };
            
            const formatter = new Intl.DateTimeFormat('en-US', options);
            const parts = formatter.formatToParts(now);
            
            // Parse hasil format
            let day, month, year, hour, minute, second;
            
            parts.forEach(part => {
                if (part.type === 'day') day = part.value;
                if (part.type === 'month') month = part.value;
                if (part.type === 'year') year = part.value;
                if (part.type === 'hour') hour = part.value;
                if (part.type === 'minute') minute = part.value;
                if (part.type === 'second') second = part.value;
            });
            
            // Update setiap button
            $('#dayBtn').text(day);
            $('#monthBtn').text(month);
            $('#yearBtn').text(year);
            $('#timeBtn').text(`${hour}:${minute}:${second} WIB`);
        }

        // Jalankan saat pertama kali
        updateClock();

        // Update setiap 1 detik (1000 millisecond)
        setInterval(updateClock, 1000);
    };

    // Panggil fungsi saat DOM siap
    $(document).ready(function() {
        window.initRealtimeClock();
    });

    // Mark as loaded
    window.FIS_GLOBAL_LOADED = true;
    console.log("✅ Global functions loaded successfully");

})();