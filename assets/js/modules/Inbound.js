
function initPageScripts() {
    console.log("inbound.js loaded");

    // ======================================================
    //  Variabel Global Lokal
    // ======================================================
    let inboundRowIndex = 1;

    // ======================================================
    // Tambah Baris Item
    // ======================================================
    $("#addInboundRow").on("click", function () {
        const newRow = `
            <tr>
                <td>
                    <select name="items[${inboundRowIndex}][item_code]" class="form-select select2 item-code" data-index="${inboundRowIndex}" required>
                        <option value="">Pilih Item Code</option>
                    </select>
                </td>
                <td><input type="text" name="items[${inboundRowIndex}][item_description]" class="form-control item-description" readonly></td>
                <td><input type="number" name="items[${inboundRowIndex}][qty]" class="form-control" required></td>
                <td><input type="text" name="items[${inboundRowIndex}][uom]" class="form-control item-uom" readonly></td>
                <td>
                    <select name="items[${inboundRowIndex}][locator]" class="form-select select2 locator-select" required>
                        <option value="">Pilih Locator</option>
                    </select>
                </td>
                <td>
                <button type="button" class="btn btn-sm removeInboundRow">
                    <i data-feather="trash-2" class="text-danger"></i>
                </button>
                </td>
            </tr>`;
        $("#inboundItemRows").append(newRow);
         feather.replace();

        // Pastikan fungsi ini ada di utils.js atau global
        if (typeof loadSKUDropdown === "function") {
            loadSKUDropdown($(`select[name="items[${inboundRowIndex}][item_code]"]`), inboundRowIndex, "#inboundModal");
        }
        if (typeof loadLocatorDropdown === "function") {
            loadLocatorDropdown($(`select[name="items[${inboundRowIndex}][locator]"]`), "#inboundModal");
        }

        inboundRowIndex++;
    });

    // ======================================================
    // Hapus Baris Item
    // ======================================================
    $("#inboundItemTable").on("click", ".removeInboundRow", function () {
        if ($("#inboundItemRows tr").length > 1) {
            $(this).closest("tr").remove();
        } else {
            showWarningToast('Minimal harus ada satu baris item.', 'Peringatan');
        }
    });

    // ======================================================
    // Saat Modal Dibuka
    // ======================================================
    $("#inboundModal").on("shown.bs.modal", function () {
        if (typeof loadStockTypeDropdown === "function") {
            loadStockTypeDropdown("#inboundModal");
        }
        if (typeof loadSKUDropdown === "function") {
            loadSKUDropdown($('#inboundItemRows select[name="items[0][item_code]"]'), 0, "#inboundModal");
        }
        if (typeof loadLocatorDropdown === "function") {
            loadLocatorDropdown($('#inboundItemRows select[name="items[0][locator]"]'), "#inboundModal");
        }
    });

    // ======================================================
    // Reset Modal Saat Ditutup
    // ======================================================
    $("#inboundModal").on("hidden.bs.modal", function () {
        $("#inboundForm")[0].reset();
        $("#inboundItemRows").html(`
            <tr>
                <td>
                    <select name="items[0][item_code]" class="form-select select2 item-code" data-index="0" required>
                        <option value="">Pilih Item Code</option>
                    </select>
                </td>
                <td><input type="text" name="items[0][item_description]" class="form-control item-description" readonly></td>
                <td><input type="number" name="items[0][qty]" class="form-control" required></td>
                <td><input type="text" name="items[0][uom]" class="form-control item-uom" readonly></td>
                <td>
                    <select name="items[0][locator]" class="form-select select2 locator-select" required>
                        <option value="">Pilih Locator</option>
                    </select>
                </td>
                <td>
                <button type="button" class="btn btn-sm removeInboundRow">
                    <i data-feather="trash-2" class="text-danger"></i>
                </button>
                </td>
            </tr>
        `);
        inboundRowIndex = 1;
         feather.replace();

        if (typeof loadSKUDropdown === "function") {
            loadSKUDropdown($('#inboundItemRows select[name="items[0][item_code]"]'), 0, "#inboundModal");
        }
        if (typeof loadLocatorDropdown === "function") {
            loadLocatorDropdown($('#inboundItemRows select[name="items[0][locator]"]'), "#inboundModal");
        }
    });

    // ======================================================
    //  Submit Form Inbound
    // ======================================================
    $("#inboundForm").on("submit", function (e) {
        e.preventDefault();
        const $submitBtn = $(this).find('button[type="submit"]');
        $submitBtn.prop('disabled', true);
        if (typeof showLoading === "function") showLoading();

        $.ajax({
            url: "modules/proses_inbound",
            type: "POST",
            data: $(this).serialize(),
            dataType: "json",
            success: function (response) {
                if (typeof hideLoading === "function") hideLoading();
                $submitBtn.prop('disabled', false);

                if (response.status === "success") {
                    $("#inboundModal").modal("hide");
                    if ($("#tabelInbound").length && $.fn.DataTable.isDataTable('#tabelInbound')) {
                        $("#tabelInbound").DataTable().ajax.reload();
                    }
                    showSuccessToast(
                        response.message || 'inbound Successfully.',
                        'Success!'
                    );
                } else {
                    showErrorToast(
                        response.message || 'inbound Failed. Please try again.',
                        'Failed'
                    );
                }
            },
            error: function (xhr, status, error) {
                window.hideLoading();
                $submitBtn.prop('disabled', false);
                handleAjaxError(xhr, 'Gagal memproses permintaan');
            }
        });
    });

    // ======================================================
    // Bulk Upload (Inbound)
    // ======================================================
    $('#fileInput').on('change', function () {
        const file = this.files[0];
        if (file) {
            $('#fileName').text(file.name);
            $('#fileSize').text((file.size / 1024 / 1024).toFixed(2) + ' MB');
            if (file.size > 50 * 1024 * 1024) {
                showErrorToast('Ukuran file melebihi 50MB!', 'Gagal');
                this.value = '';
                $('#fileName').text('');
                $('#fileSize').text('');
            }
        }
    });

    $('#bulkInboundForm').on('submit', function (e) {
        e.preventDefault();
        const $submitBtn = $(this).find('button[type="submit"]');
        const formData = new FormData(this);

        $.ajax({
            url: 'modules/proses_bulk_inbound',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            beforeSend: function () {
                if (typeof showLoading === "function") showLoading('Uploading...');
                $submitBtn.prop('disabled', true).text('Uploading...');
                $('#progressContainer').show();
                $('#progressBar').css('width', '0%');
            },
            xhr: function () {
                const xhr = new XMLHttpRequest();
                xhr.upload.addEventListener('progress', function (e) {
                    if (e.lengthComputable) {
                        const percent = (e.loaded / e.total) * 100;
                        $('#progressBar').css('width', percent + '%');
                    }
                });
                return xhr;
            },
            success: function (response) {
                if (typeof hideLoading === "function") hideLoading();
                $submitBtn.prop('disabled', false).text('Unggah');
                $('#progressContainer').hide();

                Swal.fire({
                    icon: response.status === 'success' ? 'success' : 'error',
                    title: response.status === 'success' ? 'Upload Berhasil' : 'Gagal',
                    text: response.message || (response.status === 'success' ? 'File berhasil diunggah' : 'Periksa kembali template file Anda!'),
                }).then(() => {
                    $('#bulkInboundModal').modal('hide');
                    $('#bulkInboundForm')[0].reset();
                    if ($('#queueTable').length && $.fn.DataTable.isDataTable('#queueTable')) {
                        $('#queueTable').DataTable().ajax.reload();
                    }
                });
            },
            error: function (xhr) {
                if (typeof hideLoading === "function") hideLoading();
                $submitBtn.prop('disabled', false).text('Unggah');
                $('#progressContainer').hide();

                let response;
                try {
                    response = JSON.parse(xhr.responseText);
                } catch {
                    response = { message: "Terjadi kesalahan tidak terduga!" };
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: response.message || 'Upload gagal. Silakan coba lagi!',
                });
            }
        });
    });

    // ======================================================
    // DataTable Inbound
    // ======================================================
    if ($('#tabelInbound').length && !$.fn.DataTable.isDataTable('#tabelInbound')) {
        $("#tabelInbound").DataTable({
            ajax: {
                url: "API/data_table_inventory.php",
                dataSrc: "",
                beforeSend: () => typeof showLoading === "function" && showLoading(),
                complete: () => typeof hideLoading === "function" && hideLoading(),
                error: function (xhr, error, thrown) {
                    console.error("Failed to load inbound data:", thrown);
                    handleAjaxError(xhr, 'Gagal memuat data inbound');
                }
            },
            columns: [
                { data: null, render: (d, t, r, m) => m.row + 1 },
                { data: "po_number" },
                { data: "reference_number" },
                { data: "packing_list" },
                { data: "item_code" },
                { data: "item_description" },
                { data: "qty" },
                { data: "uom" },
                { data: "locator" },
                { data: "created_date" },
                { data: "created_by" },
                { data: "wh_name" },
                {
                    data: null,
                    render: row => `
                        <button class="btn btn-sm edit-inbound" data-id="${row.id}" data-bs-toggle="tooltip" title="Edit">
                            <i data-feather="edit" style="color:#ff0000; font-size:20px; margin-right:6px;"></i>
                        </button>`
                }
            ],
            order: [[9, "asc"]],
            language: { url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json" },
            scrollX: true,
            fixedColumns: { leftColumns: 0, rightColumns: 1 },
            initComplete: function () {
                initDataTableSearch(this.api());
                feather.replace();
            },
            destroy: true,
            drawCallback:function () {
                feather.replace();
            }
        });
    }
    
    
    $("#exportExcelInbound").on("click", () => {
        window.location.href = "API/export_inbound.php";
    });

    $('#generatePoNumber').click(function() {
    const button = $(this);
    const poNumberInput = $('#po_number');
    
    // Disable tombol (akan berubah warna abu-abu sesuai styling Bootstrap)
    button.prop('disabled', true);
    
    $.ajax({
        url: 'modules/generate_po_number',
        type: 'POST',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Isi field po_number dan set read-only
                poNumberInput.val(response.po_number);
                poNumberInput.prop('readonly', true);
                
                // Tampilkan notifikasi sukses
                showSuccessToast(
                'PO Number berhasil di-generate',
                'Success!'
            );
            } else {
                // Tampilkan error dan enable tombol kembali
                showErrorToast(response.message || 'Gagal generate PO number', 'Error!');
                button.prop('disabled', false);
            }
        },
        error: function(xhr, status, error) {
            handleAjaxError(xhr);
            button.prop('disabled', false);
        }
        // Tidak ada complete block, tombol tetap disable setelah generate berhasil
    });
});

    // Enable tombol, reset form, dan hapus read-only saat modal dibuka
    $('#inboundModal').on('shown.bs.modal', function() {
        $('#po_number').val('');
        $('#po_number').prop('readonly', false);
        $('#generatePoNumber').prop('disabled', false);
    });
    
    // Reset form saat modal ditutup
    $('#inboundModal').on('hidden.bs.modal', function() {
        $('#po_number').val('');
    });
    
    // File Upload Handling
        $('#browseButton').on('click', () => $('#fileInput').click());
    
        $('#fileInput').on('change', function (event) {
            const file = event.target.files[0];
            if (file) {
                $('#fileName').text(file.name);
                const fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);
                $('#fileSize').text(fileSizeMB + ' MB');
                $('#fileInfo').show();
                $('#uploadButton').prop('disabled', false);
                $('#progressContainer').show();
                $('#progressBar').css('width', '100%').text('100%');
            } else {
                $('#fileInfo').hide();
                $('#uploadButton').prop('disabled', true);
                $('#progressContainer').hide();
            }
        });
    
        $('#removeFile').on('click', function () {
            $('#fileInput').val('');
            $('#fileName').text('');
            $('#fileSize').text('');
            $('#fileInfo').hide();
            $('#uploadButton').prop('disabled', true);
            $('#progressContainer').hide();
        });
    
}


