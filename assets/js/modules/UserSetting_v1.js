function initPageScripts() {
    console.log("✅ user_setting.js loaded");

    // ====================================
    // VARIABEL GLOBAL UNTUK PAGE INI
    // ====================================
    let usersTable = null;

    // ====================================
    // FORM SUBMIT - REGISTER USER
    // ====================================
    $("#userForm").off("submit").on("submit", function (e) {
    e.preventDefault();
    const $form = $(this);
    const $submitBtn = $form.find('button[type="submit"]');

    $submitBtn.prop('disabled', true);
    showLoading('Menyimpan data...');

    $.ajax({
        url: "modules/Proses_Register_User.php",
        type: "POST",
        data: $form.serialize(),
        dataType: "json",
        success: function (response) {
            hideLoading();
            $submitBtn.prop('disabled', false);

            // Pastikan modal benar-benar tertutup & fokus dilepas
            if ($('#userModal').hasClass('show')) {
                $('#userModal').modal('hide');
            }
            document.activeElement.blur();

            if (response.status === "success") {
                // Delay kecil supaya aria-hidden tidak bentrok
                setTimeout(() => {
                    Swal.fire({
                        icon: "success",
                        title: "Sukses",
                        text: response.message || "Register berhasil!",
                        timer: 2500,
                        showConfirmButton: false,
                    }).then(() => {
                        $form[0].reset();
                        $('select[name="wh_name"]').val(null).trigger("change");
                        $('select[name="project_name"]').val(null).trigger("change");

                        if (usersTable) usersTable.ajax.reload(null, false);
                    });
                }, 200);
            } else {
                setTimeout(() => {
                    Swal.fire({
                        icon: "error",
                        title: "Gagal",
                        text: response.message || "Terjadi kesalahan saat Register!",
                    });
                }, 200);
            }
        },
        error: function (xhr) {
            hideLoading();
            $submitBtn.prop('disabled', false);

            let response;
            try {
                response = JSON.parse(xhr.responseText);
            } catch (e) {
                response = { status: "error", message: "Terjadi kesalahan tidak terduga!" };
            }

            setTimeout(() => {
                Swal.fire({
                    icon: "error",
                    title: "Gagal",
                    text: response.message || "Terjadi kesalahan pada server!",
                });
            }, 200);
        },
    });
});

    // ====================================
    // MODAL EVENTS - LOAD DROPDOWN
    // ====================================
    $("#userModal").on("shown.bs.modal", function () {
        const $whId = $('select[name="wh_name"]');
        const $project = $('select[name="project_name"]');

        // Gunakan fungsi global dari script.js
        loadDropdownData($whId, "warehouses", "wh_name", "wh_name", "#userModal");
        loadDropdownData($project, "project_name", "project_name", "project_name", "#userModal");
    });

    $("#userModal").on("hidden.bs.modal", function () {
        $("#userForm")[0].reset();
        
        // Proper Select2 cleanup
        $('select[name="wh_name"]').val(null).trigger("change");
        $('select[name="project_name"]').val(null).trigger("change");
    });

    // ====================================
    // DATATABLE INITIALIZATION
    // ====================================
    if (!$.fn.DataTable.isDataTable('#tabelusers')) {
        usersTable = $("#tabelusers").DataTable({
            ajax: {
                url: "API/data_table_user",
                dataSrc: "data",
                beforeSend: function() {
                    showLoading('Memuat data users...');
                },
                complete: hideLoading,
                error: function(xhr, error, thrown) {
                    hideLoading();
                    console.error('DataTable error:', error, thrown);
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: 'Gagal memuat data users!'
                    });
                }
            },
            columns: [
                { 
                    data: null, 
                    render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1,
                    orderable: false,
                    searchable: false
                },
                { data: "nama" },
                { data: "username" },
                { data: "email" },
                { data: "wh_name" },
                { data: "role" },
                { data: "project_name" },
                {
                    data: null,
                    render: function (data, type, row) {
                        return `
                            <button class="btn btn-sm reset-password-btn" 
                                    data-id="${row.id}" 
                                    data-nama="${row.nama}"
                                    data-bs-toggle="tooltip"
                                    data-bs-placement="top"
                                    title="Reset password user">
                                <i class="fa fa-rotate-left" style="color:#3498db; font-size:20px; margin-right:6px;"></i>
                            </button>
                        `;
                    },
                    orderable: false,
                    searchable: false
                },
            ],
            order: [[0, "asc"]], // Sort by nama
            language: { 
                url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json" 
            },
            
            initComplete: function () {
                // Gunakan fungsi global dari script.js
                initDataTableSearch(this.api());
                console.log("✅ DataTable users initialized");
            },
            
            destroy: true

            
        });
    }

    // ====================================
    // RESET PASSWORD - BUTTON CLICK
    // ====================================
    $(document).on("click", ".reset-password-btn", function () {
        const userId = $(this).data("id");
        const userNama = $(this).data("nama");
        
        $("#resetPasswordModalLabel").text(`Reset Password untuk ${userNama}`);
        $("#resetPasswordForm [name='user_id']").val(userId);
        $("#resetPasswordModal").modal("show");
    });

    // ====================================
    // RESET PASSWORD - FORM SUBMIT
    // ====================================
    $("#resetPasswordForm").on("submit", function (e) {
        e.preventDefault();
        const $submitBtn = $(this).find('button[type="submit"]');
        const originalText = $submitBtn.text();
        
        $submitBtn.prop("disabled", true).text("Mereset...");
        showLoading('Mereset password...');

        $.ajax({
            url: "API/api_reset_password",
            type: "POST",
            data: $(this).serialize(),
            dataType: "json",
            success: function (response) {
                hideLoading();
                $submitBtn.prop("disabled", false).text(originalText);
                
                if (response.success) {
                    Swal.fire({
                        icon: "success",
                        title: "Berhasil",
                        text: response.success || "Password berhasil direset!",
                        showConfirmButton: true,
                        timer: 3000,
                    }).then(() => {
                        $("#resetPasswordModal").modal("hide");
                        $("#resetPasswordForm")[0].reset();
                        
                        // Reload DataTable
                        if (usersTable) {
                            usersTable.ajax.reload(null, false);
                        }
                    });
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Gagal",
                        text: response.error || "Terjadi kesalahan saat mereset password!",
                    });
                }
            },
            error: function (xhr) {
                hideLoading();
                $submitBtn.prop("disabled", false).text(originalText);
                
                let response;
                try {
                    response = JSON.parse(xhr.responseText);
                } catch (e) {
                    response = { error: "Terjadi kesalahan tidak terduga!" };
                }
                
                Swal.fire({
                    icon: "error",
                    title: "Gagal",
                    text: response.error || "Terjadi kesalahan pada server!",
                });
            }
        });
    });

    // ====================================
    // CLEANUP SAAT PAGE CHANGE (PENTING UNTUK SPA!)
    // ====================================
    window.cleanupUserSettingPage = function() {
        // Destroy DataTable
        if (usersTable) {
            usersTable.destroy();
            usersTable = null;
        }
        
        // Destroy Select2
        $('select[name="wh_name"]').select2('destroy');
        $('select[name="project_name"]').select2('destroy');
        
        // Remove event handlers
        $("#userForm").off("submit");
        $("#resetPasswordForm").off("submit");
        $("#userModal").off("shown.bs.modal hidden.bs.modal");
        
        console.log("🧹 User setting page cleaned up");
    };
}

// ====================================
// AUTO INIT SAAT PAGE LOAD
// ====================================
$(document).ready(function() {
    if (typeof initPageScripts === 'function') {
        initPageScripts();
    }
});