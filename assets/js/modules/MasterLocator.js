
function initPageScripts() {
    console.log("aster_locator.js loaded");

// Locator
    $("#locatorForm").on("submit", function (e) {
        e.preventDefault();
        const $submitBtn = $(this).find('button[type="submit"]');
        $submitBtn.prop("disabled", true);
        showLoading();
        $.ajax({
            url: "modules/proses_locator",
            type: "POST",
            data: $(this).serialize(),
            dataType: "json",
            success: function (response) {
                hideLoading();
                $submitBtn.prop("disabled", false);
                if (response.status === "success") {
                    $("#locatorModal").modal("hide");
                    $("#tabellocator").DataTable().ajax.reload();
                    showSuccessToast(
                        response.message || "Data locator berhasil disimpan!",
                        "Berhasil"
                    );
                } else {
                    showErrorToast(
                        response.message || "Terjadi kesalahan saat menyimpan data!",
                        "Gagal"
                    );
                }
            },
            error: function (xhr) {
                hideLoading();
                $submitBtn.prop("disabled", false);
                const response = JSON.parse(xhr.responseText) || { status: "error", message: "Terjadi kesalahan tidak terduga!" };
                showErrorToast(
                    response.message || "Terjadi kesalahan Tak Terduga!",
                    "Gagal"
                );
            },
        });
    });

    $("#locatorModal").on("hidden.bs.modal", function () {
        $("#locatorForm")[0].reset();
    });

    if (!$.fn.DataTable.isDataTable('#tabellocator')) {
        $("#tabellocator").DataTable({
            ajax: {
                url: "modules/proses_locator",
                dataSrc: "data",
                beforeSend: showLoading,
                complete: hideLoading,
            },
            columns: [
                { data: null, render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1 },
                { data: "locator" },
                { data: "locator_description" },
                { data: "wh_name" },
                {
                    data: null,
                    render: (data, type, row) => `<button class="btn btn-sm edit-locator" data-id="${row.id}"data-bs-toggle="tooltip" data-bs-placement="top" title="Edit Locator">
                    <i class="fas fa-pen text-dark"></i>
                    </button> 
                    `
                },
            ],
            order: [[0, "asc"]],
            language: { url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json" },
            destroy: true
        });
    }

    $("#exportExcelLocator").on("click", () => window.location.href = "../modules/export_excel_locator");

}