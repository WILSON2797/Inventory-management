function initPageScripts() {
    console.log("select_context.js loaded");

    // Buka modal dari header
    $(document).on('click', '#openChangeContextModal', function () {
        $('#changeContextModal').modal('show');
        loadContextOptions();
    });

    // Load WH & Project options via AJAX
    function loadContextOptions() {
        showLoading('Memuat opsi konteks...');
        $.get('select_wh_project.php?ajax=1', function (data) {
            hideLoading();
            if (data.wh_options && data.project_options) {
                $('#changeContextModal select[name="wh_name"]').html(data.wh_options);
                $('#changeContextModal select[name="project_name"]').html(data.project_options);
                // Init Select2 di dalam modal
                $('#changeContextModal .select2').select2({
                    dropdownParent: $('#changeContextModal'),
                    width: '100%'
                });
            } else {
                showErrorToast('Gagal memuat opsi konteks.');
            }
        }, 'json').fail(function () {
            hideLoading();
            showErrorToast('Tidak dapat terhubung ke server.');
        });
    }

    // Submit form ganti konteks
    $('#changeContextForm').off('submit').on('submit', function (e) {
        e.preventDefault();
        const $btn = $(this).find('button[type="submit"]');
        const originalText = $btn.html();

        $btn.prop('disabled', true).html('<i data-feather="loader" class="me-1 spin"></i> Menyimpan...');
        showLoading('Menerapkan konteks baru...');

        $.post('set_session.php', $(this).serialize(), function (res) {
            hideLoading();
            if (res.status === 'success') {
                showSuccessToast('Warehouse & Project berhasil diubah.');

        setTimeout(() => {
            window.location.reload(); // Refresh untuk update semua data
        }, 1200);
            } else {
                $btn.prop('disabled', false).html(originalText);
                showErrorToast(res.message || 'Gagal mengubah Warehouse & Project.');
            }
        }, 'json').fail(function () {
            hideLoading();
            $btn.prop('disabled', false).html(originalText);
            showErrorToast('Tidak dapat terhubung ke server.');
        });
    });

    // Reset form saat modal ditutup
    $('#changeContextModal').on('hidden.bs.modal', function () {
        $('#changeContextForm')[0].reset();
        $('#changeContextModal .select2').select2('destroy');
    });

    // Cleanup saat ganti page (SPA)
    window.cleanupSelectContext = function () {
        $(document).off('click', '#openChangeContextModal');
        $('#changeContextForm').off('submit');
        $('#changeContextModal').off('hidden.bs.modal');
        if ($.fn.select2) {
            $('#changeContextModal .select2').select2('destroy');
        }
        console.log("select_context.js cleaned up");
    };
}

// Auto init
$(document).ready(function () {
    if (typeof initPageScripts === 'function') {
        initPageScripts();
    }
});