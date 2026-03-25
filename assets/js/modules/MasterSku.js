function initPageScripts() {
    console.log("✅ MasterSku.js loaded");
 
    // ========== INISIALISASI SELECT2 UNTUK KEDUA MODAL ==========
    // Inisialisasi Select2 untuk modal TAMBAH
    if (!$('#skuModal select[name="project"]').hasClass("select2-hidden-accessible")) {
        $('#skuModal select[name="project"]').select2({
            dropdownParent: $('#skuModal'),
            width: '100%',
            placeholder: 'Pilih Project'
        });
    }

    // Inisialisasi Select2 untuk modal EDIT
    if (!$("#edit_project").hasClass("select2-hidden-accessible")) {
        $("#edit_project").select2({
            dropdownParent: $('#editSkuModal'),
            width: '100%',
            placeholder: 'Pilih Project'
        });
    }

    // Load dropdown untuk EDIT (sekali saat halaman load)
    const $editProject = $("#edit_project");
    loadDropdownData(
        $editProject,
        "project_name",
        "project_name",
        "project_name",
        "#editSkuModal"
    );
    // ========== AKHIR INISIALISASI ==========

    // Modal TAMBAH SKU
    $("#skuModal").on("shown.bs.modal", function () {
        $("#skuForm")[0].reset();
        const $projectName = $('select[name="project"]');
        
        // Load dropdown setiap kali modal dibuka
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
            url: "modules/proses_sku",
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
                    <i data-feather="edit" style="color:#ff0000; font-size:20px; margin-right:6px;"></i>
                    </button> 
                    `
                },
            ],
            order: [[0, "asc"]],
            language: { url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json" },
            initComplete: function () {
                initDataTableSearch(this.api());
                feather.replace(); // Refresh ikon
            },
            destroy: true,
            drawCallback:function () {
                feather.replace();
            }
        });
    }

    $("#exportExcelSKU").on("click", () => window.location.href = "../API/export_sku");

    $("#bulkUploadForm").on("submit", function (e) {
        e.preventDefault();
        const formData = new FormData(this);

        $.ajax({
            url: "modules/proses_bulk_sku",
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

    // ========== KODE EDIT (PALING BAWAH) ==========
    
    // Handle klik tombol edit
    $(document).on("click", ".edit-sku", function () {
        const skuId = $(this).data("id");
        
        showLoading();
        $.ajax({
            url: "modules/get_sku_detail",
            type: "GET",
            data: { id: skuId },
            dataType: "json",
            success: function (response) {
                hideLoading();
                if (response.status === "success") {
                    const data = response.data;
                    
                    $("#edit_id").val(data.id);
                    $("#edit_item_code").val(data.item_code);
                    $("#edit_item_description").val(data.item_description);
                    $("#edit_volume").val(data.volume);
                    $("#edit_uom").val(data.uom);
                    $("#edit_project").val(data.project).trigger("change");
                    
                    $("#editSkuModal").modal("show");
                } else {
                    showErrorToast("Error", response.message || "Gagal memuat data SKU");
                }
            },
            error: function (xhr) {
                hideLoading();
                const response = xhr.responseJSON || { message: "Terjadi kesalahan saat memuat data!" };
                showErrorToast("Error", response.message);
            }
        });
    });

    // Handle submit form edit
    $("#editSkuForm").on("submit", function (e) {
        e.preventDefault();
        const $submitBtn = $(this).find('button[type="submit"]');
        $submitBtn.prop("disabled", true);
        
        showLoading();
        $.ajax({
            url: "modules/update_sku",
            type: "POST",
            data: $(this).serialize(),
            dataType: "json",
            success: function (response) {
                hideLoading();
                $submitBtn.prop("disabled", false);
                
                if (response.status === "success") {
                    $("#editSkuModal").modal("hide");
                    $("#tabelSKU").DataTable().ajax.reload();
                    showSuccessToast(
                        response.message || "SKU berhasil diupdate",
                        "Success"
                    );
                } else {
                    showErrorToast(
                        "Error",
                        response.message || "Gagal mengupdate SKU"
                    );
                }
            },
            error: function (xhr) {
                hideLoading();
                $submitBtn.prop("disabled", false);
                const response = xhr.responseJSON || { message: "Terjadi kesalahan tidak terduga!" };
                showErrorToast("Error", response.message);
            }
        });
    });

    // Reset form dan select2 saat modal ditutup
    $("#editSkuModal").on("hidden.bs.modal", function () {
        $("#editSkuForm")[0].reset();
        $("#edit_project").val("").trigger("change");
    });
    

}