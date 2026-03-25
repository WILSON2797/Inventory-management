 function initPageScripts() {
    console.log("✅ MasterSku.js loaded");
 
 $("#skuModal").on("shown.bs.modal", function () {
        $("#skuForm")[0].reset(); // Reset input standar
        const $projectName = $('select[name="project"]');
        loadDropdownData(
            $projectName,
            "project_name",
            "project_name",
            "project_name",
            "#skuModal"
        );
    });

    $("#skuModal").on("hidden.bs.modal", function () {
        $("#skuForm")[0].reset();
        $('select[name="project"]').val("").trigger("change");
    });

    $("#skuForm").on("submit", function (e) {
        e.preventDefault();
        const $submitBtn = $(this).find('button[type="submit"]');
        $submitBtn.prop("disabled", true);
        showLoading();
        $.ajax({
            url: "../modules/proses_sku",
            type: "POST",
            data: $(this).serialize(),
            dataType: "json",
            success: function (response) {
                hideLoading();
                $submitBtn.prop("disabled", false);
                if (response.status === "success") {
                    $("#skuModal").modal("hide");
                    $("#tabelSKU").DataTable().ajax.reload();
                    showSuccessToast(
                        response.message || "SKU has been successfully saved",
                        "Success"
                    );
                } else {
                    showErrorToast(
                        "Error",
                        response.message || "Unable to save the SKU. Please try again."
                    );
                }
            },
            error: function (xhr) {
                hideLoading();
                $submitBtn.prop("disabled", false);
                const response = JSON.parse(xhr.responseText) || { status: "error", message: "Terjadi kesalahan tidak terduga!" };
                showErrorToast(
                    "Error",
                    response.message || "Unable to save the SKU. Please try again."
                );
            },
        });
    });

    if (!$.fn.DataTable.isDataTable('#tabelSKU')) {
        $("#tabelSKU").DataTable({
            ajax: {
                url: "API/data_table_sku",
                dataSrc: "data",
                beforeSend: showLoading,
                complete: hideLoading,
            },
            columns: [
                { data: null, render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1 },
                { data: "item_code" },
                { data: "item_description" },
                { data: "volume" },
                { data: "uom" },
                { data: "project" },
                { data: "created_at" },
                {
                    data: null,
                    render: (data, type, row) => `<button class="btn btn-sm edit-sku" data-id="${row.id}"data-bs-toggle="tooltip" data-bs-placement="top" title="Edit SKU">
                    <i class="fas fa-pen text-dark"></i>
                    </button> 
                    `
                },
            ],
            order: [[0, "asc"]],
            language: { url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json" },
            initComplete: function () {
                initDataTableSearch(this.api());
            },

            destroy: true
        });
    }

    $("#exportExcelSKU").on("click", () => window.location.href = "../API/export_sku");

    $("#bulkUploadForm").on("submit", function (e) {
        e.preventDefault();
        const formData = new FormData(this);

        $.ajax({
            url: "../modules/proses_bulk_sku",
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            dataType: "json",
            success: function (response) {
                $("#bulkUploadModal").modal("hide");
                $("#tabelSKU").DataTable().ajax.reload();
                let swalConfig = {
                    icon: response.status === "success" ? "success" : "error",
                    title: response.status === "success" ? "Sukses" : "Gagal",
                    text: response.message,
                };
                if (response.warnings && response.warnings.length > 0) {
                    swalConfig.html = `${response.message}<br><br><strong>Peringatan:</strong><ul style="text-align: left;">${response.warnings.map((w) => `<li>${w}</li>`).join("")}</ul>`;
                }
                Swal.fire(swalConfig);
            },
            error: function (xhr) {
                const response = JSON.parse(xhr.responseText) || { status: "error", message: "Terjadi kesalahan tidak terduga!" };
                Swal.fire({
                    icon: "error",
                    title: "Gagal",
                    text: response.message || "Terjadi kesalahan saat mengunggah file!",
                });
            },
        });
    });
}