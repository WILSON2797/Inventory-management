function initPageScripts() {
    console.log("User setting.js loaded");

    // ====================================
    // VARIABEL GLOBAL
    // ====================================
    let usersTable = null;

    // ====================================
    // FORM SUBMIT - TAMBAH / EDIT USER
    // ====================================
    $("#userForm").off("submit").on("submit", function (e) {
        e.preventDefault();
        const $form = $(this);
        const $submitBtn = $form.find('button[type="submit"]');

        // Validasi minimal 1 WH & 1 Project
        const whChecked = $form.find('input[name="wh_names[]"]:checked').length;
        const projChecked = $form.find('input[name="project_names[]"]:checked').length;

        if (whChecked === 0) {
            Swal.fire('Error', 'Pilih minimal 1 Warehouse!', 'error');
            return;
        }
        if (projChecked === 0) {
            Swal.fire('Error', 'Pilih minimal 1 Project!', 'error');
            return;
        }

        $submitBtn.prop('disabled', true).text('Menyimpan...');
        showLoading('Menyimpan data user...');

        $.ajax({
            url: "modules/Proses_Register_User.php",
            type: "POST",
            data: $form.serialize(),
            dataType: "json",
            success: function (response) {
                hideLoading();
                $submitBtn.prop('disabled', false).text('Simpan');

                if (response.status === "success") {
                    Swal.fire({
                        icon: "success",
                        title: "Sukses!",
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        $('#userModal').modal('hide');
                        $form[0].reset();
                        $form.find('input[type="checkbox"]').prop('checked', false);

                        // DEBUG & RELOAD AMAN
                        if (typeof usersTable !== 'undefined' && usersTable !== null) {
                            usersTable.ajax.reload(null, false);
                        } else {
                            console.warn("DataTable belum siap, memuat ulang halaman...");
                            location.reload();
                        }
                    });
                } else {
                    Swal.fire('Gagal', response.message || 'Terjadi kesalahan!', 'error');
                }
            },
            error: function () {
                hideLoading();
                $submitBtn.prop('disabled', false).text('Simpan');
                Swal.fire('Error', 'Gagal terhubung ke server!', 'error');
            }
        });
    });

    // ====================================
    // MODAL HIDDEN - RESET FORM
    // ====================================
    $("#userModal").on("hidden.bs.modal", function () {
        $("#userForm")[0].reset();
        $('input[name="wh_names[]"], input[name="project_names[]"]').prop('checked', false);
        $('#password_field').attr('required', 'required');
        $('#userModalLabel').text('Tambah User');
    });

    // ====================================
    // DATATABLE INITIALIZATION
    // ====================================
    if (!$.fn.DataTable.isDataTable('#tabelusers')) {
        usersTable = $("#tabelusers").DataTable({
            ajax: {
                url: "API/data_table_user.php",  // GANTI DARI data_table_user
                dataSrc: "data",
                beforeSend: () => showLoading('Memuat data users...'),
                complete: hideLoading,
                error: () => {
                    hideLoading();
                    Swal.fire('Error', 'Gagal memuat data users!', 'error');
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
                { data: "wh_names" },
                { data: "role" },
                { data: "project_names" },
                {
                    data: null,
                    render: function (data, type, row) {
                        return `
                            <div class="btn-group" role="group">
                                <button class="btn btn-sm edit-btn" 
                                        onclick="editUser(${row.id})" 
                                        title="Edit User">
                                    <i data-feather="edit" style="color:#ff0000; font-size:20px; margin-right:6px;"></i>
                                </button>
                                <button class="btn btn-sm reset-password-btn"
                                        data-id="${row.id}" 
                                        data-nama="${row.nama}"
                                        title="Reset Password">
                                    <i class="fa fa-rotate-left" style="color:#3498db; font-size:20px; margin-right:6px;"></i>
                                </button>
                            </div>
                        `;
                    },
                    orderable: false,
                    searchable: false
                }
            ],
            order: [[1, "asc"]],
            language: { url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json" },
            initComplete: function () {
                initDataTableSearch(this.api());
                console.log("DataTable users initialized");
                feather.replace(); // Refresh ikon
            },
            drawCallback: function () {
                feather.replace(); // Refresh ikon tiap redraw
            }
        });
    }

    // ====================================
    // EDIT USER - GLOBAL FUNCTION
    // ====================================
    window.editUser = function (userId) {
        $.get('API/get_user.php?id=' + userId, function (user) {
            if (!user || user.error) {
                Swal.fire('Error', user?.error || 'User tidak ditemukan', 'error');
                return;
            }

            // Isi form
            $('#userModalLabel').text('Edit User');
            $('#edit_user_id').val(user.id);
            $('input[name="nama"]').val(user.nama);
            $('input[name="email"]').val(user.email);
            $('input[name="username"]').val(user.username);
            $('#password_field').removeAttr('required').val('');

            // Check WH
            $('input[name="wh_names[]"]').prop('checked', false);
            if (user.wh_list && Array.isArray(user.wh_list)) {
                user.wh_list.forEach(wh => {
                    $(`input[name="wh_names[]"][value="${wh}"]`).prop('checked', true);
                });
            }

            // Check Project
            $('input[name="project_names[]"]').prop('checked', false);
            if (user.proj_list && Array.isArray(user.proj_list)) {
                user.proj_list.forEach(proj => {
                    $(`input[name="project_names[]"][value="${proj}"]`).prop('checked', true);
                });
            }

            $('#userModal').modal('show');
        }, 'json').fail(function () {
            Swal.fire('Error', 'Gagal mengambil data user', 'error');
        });
    };

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
        const $form = $(this);
        const $btn = $form.find('button[type="submit"]');
        const originalText = $btn.text();

        $btn.prop("disabled", true).text("Mereset...");
        showLoading('Mereset password...');

        $.ajax({
            url: "API/api_reset_password.php",
            type: "POST",
            data: $form.serialize(),
            dataType: "json",
            success: function (res) {
                hideLoading();
                $btn.prop("disabled", false).text(originalText);

                if (res.success) {
                    Swal.fire("Sukses!", res.success, "success").then(() => {
                        $("#resetPasswordModal").modal("hide");
                        $form[0].reset();
                    });
                } else {
                    Swal.fire("Gagal", res.error || "Gagal reset password", "error");
                }
            },
            error: function () {
                hideLoading();
                $btn.prop("disabled", false).text(originalText);
                Swal.fire("Error", "Server error", "error");
            }
        });
    });

    // ====================================
    // CLEANUP SAAT PAGE CHANGE
    // ====================================
    window.cleanupUserSettingPage = function () {
        if (usersTable) {
            usersTable.destroy();
            usersTable = null;
        }
        $("#userForm").off("submit");
        $("#resetPasswordForm").off("submit");
        $("#userModal").off("hidden.bs.modal");
        $(document).off("click", ".edit-btn");
        $(document).off("click", ".reset-password-btn");
        console.log("User setting page cleaned up");
    };
}

// ====================================
// AUTO INIT
// ====================================
$(document).ready(function () {
    if (typeof initPageScripts === 'function') {
        initPageScripts();
    }
});