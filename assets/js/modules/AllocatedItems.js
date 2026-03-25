function initPageScripts() {
    console.log("✅ item_allocated.js loaded");

// Allocated Table
    if (!$.fn.DataTable.isDataTable('#tabelAllocated')) {
        $("#tabelAllocated").DataTable({
            ajax: {
                url: "API/data_table_allocated",
                dataSrc: "",
                beforeSend: showLoading,
                complete: hideLoading,
                error: function (xhr, error, thrown) {
                    console.error("Failed to load allocated data: " + thrown);
                    handleAjaxError(xhr, 'Loading Allocated Data');
                }
            },
            columns: [
                { data: null, render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1 },
                { data: "order_number" },
                { data: "customer" },
                { data: "lottable1" },
                { data: "lottable2" },
                { data: "lottable3" },
                { data: "status" },
                { data: "allocated_date" },
                { data: "created_by" },
                { data: "wh_name" },
                {
                    data: null,
                    orderable: false,
                    className: 'text-center', // Tambah ini
                    render: (data, type, row) => `
                    <div class="d-flex gap-0">
                    <a href="../modules/print_pickticket?order_number=${row.order_number}" 
                            target="_blank" 
                            class="btn btn-sm" 
                            data-bs-toggle="tooltip" 
                            data-bs-placement="top" 
                            title="Print Document">
                                <i class="fas fa-print"></i>
                            </a>
                        <button class="btn btn-sm process-allocated" 
                            data-id="${row.id}" 
                            data-order_number="${row.order_number}" 
                            data-bs-toggle="tooltip" 
                            data-bs-placement="top" 
                            title="Confirm Shipped">
                                <i class="fa-solid fa-circle-check" style="color:#32CD32;"></i>
                        </button>
                        <button class="btn btn-sm view-items" 
                            data-order_number="${row.order_number}" 
                            data-bs-toggle="tooltip" 
                            data-bs-placement="top" 
                            title="View Items">
                                <i class="fas fa-eye" style="color:#00008B;"></i>
                    </button>
                    <button class="btn btn-sm allocation-cancel" 
                            data-order_number="${row.order_number}" 
                            data-bs-toggle="tooltip" 
                            data-bs-placement="top" 
                            title="Cancel Order">
                                <i class="fa fa-rotate-left" style="color:#dc3545;"></i>
                    </button>
                     </div>`
                }
            ],
            order: [[7, "desc"]],
            language: { url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json" },
            initComplete: function () {
                initDataTableSearch(this.api());
            },
            scrollX: true,
            fixedColumns: { leftColumns: 0, rightColumns: 1 },
            destroy: true
        });
    }

    $("#exportExcelAllocatedItems").on("click", () => window.location.href = "../API/export_excel_allocated_items");
    
    // Handle Cancel Allocation
$("#tabelAllocated tbody").on("click", ".allocation-cancel", function () {
    const orderNumber = $(this).data("order_number");

    Swal.fire({
        title: "Batalkan Allocation?",
        text: `Yakin ingin membatalkan allocation untuk Order Number ${orderNumber}?`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Ya, Batalkan",
        cancelButtonText: "Tidak",
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            showLoading();
            $.ajax({
                url: "modules/cancel_allocated",
                type: "POST",
                data: { order_number: orderNumber },
                dataType: "json",
                success: function (response) {
                    hideLoading();
                    if (response.success === true) {
                        $("#tabelAllocated").DataTable().ajax.reload();
                        Swal.fire({
                            icon: "success",
                            title: "Allocation Dibatalkan",
                            text: response.message || "Data allocation berhasil dibatalkan dan stok dikembalikan.",
                            showConfirmButton: true
                        });
                    } else {
                        Swal.fire({
                            icon: "error",
                            title: "Gagal",
                            text: response.message || "Terjadi kesalahan saat membatalkan allocation!",
                        });
                    }
                },
                error: function (xhr) {
                    hideLoading();
                    const response = JSON.parse(xhr.responseText) || { success: false, message: "Terjadi kesalahan tidak terduga!" };
                    Swal.fire({
                        icon: "error",
                        title: "Gagal",
                        text: response.message || "Terjadi kesalahan pada server!",
                    });
                }
            });
        }
    });
});

    $("#tabelAllocated tbody").on("click", ".view-items", function () {
        const orderNumber = $(this).data("order_number");

        $("#viewItemsModalLabel").text("Order Number: " + orderNumber);

        if ($.fn.DataTable.isDataTable('#tabelItems')) {
            $('#tabelItems').DataTable().destroy();
        }

        $("#tabelItems").DataTable({
            ajax: {
                url: "API/get_allocated_items",
                type: "POST",
                data: { order_number: orderNumber },
                dataSrc: "",
                beforeSend: showLoading,
                complete: hideLoading,
                error: function (xhr, error, thrown) {
                    console.error("Failed to load detail items: " + thrown);
                    Swal.fire({
                        icon: "error",
                        title: "Gagal Load Detail",
                        text: "Terjadi kesalahan saat memuat detail items."
                    });
                }
            },
            columns: [
                { data: null, render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1 },
                { data: "item_code" },
                { data: "item_description" },
                { data: "qty_picking" },
                { data: "locator_picking" },
                { data: "packing_list" },
                { data: "uom" },
                { 
                    data: null,
                    render: function (data, type, row) {
                        return `
                        <button type="button" 
                            class="btn btn-sm btn-outline-success btn-view-file" 
                            data-bs-toggle="tooltip" 
                            data-bs-placement="top" 
                            title="File"
                        >
                            <i data-feather="file" style="stroke:#28a745;"></i>
                        </button>
                    `;
                    }
                }
            ],
            order: [[0, "asc"]],
            language: { url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json" },
            initComplete: function () {
                initDataTableSearch(this.api());
                feather.replace();
            },
            scrollX: true,
            destroy: true
        });

        $("#viewItemsModal").modal("show");
    });

    $("#tabelAllocated tbody").on("click", ".process-allocated", function () {
        const orderNumber = $(this).data("order_number");
        const id = $(this).data("id");

        Swal.fire({
            title: "Konfirmasi Shipped",
            text: `Are you sure you want to mark Order Number ${orderNumber} as Shipped?`,
            icon: "question",
            showCancelButton: true,
            confirmButtonText: "Ya, Shipped",
            cancelButtonText: "Batal"
        }).then((result) => {
            if (result.isConfirmed) {
                showLoading();
                $.ajax({
                    url: "modules/proses_shipped",
                    type: "POST",
                    data: { order_number: orderNumber, id: id },
                    dataType: "json",
                    success: function (response) {
                        hideLoading();
                        if (response.success === true) {
                            $("#tabelAllocated").DataTable().ajax.reload();
                            showSuccessToast(response.message || "Order berhasil ditandai sebagai Shipped!");
                        } else {
                            showErrorToast(response.message || "Terjadi kesalahan saat memproses Shipped!");
                        }
                    },
                    error: function (xhr) {
                        hideLoading();
                        const response = JSON.parse(xhr.responseText) || { success: false, message: "Terjadi kesalahan tidak terduga!" };
                        Swal.fire({
                            icon: "error",
                            title: "Gagal",
                            text: response.message || "Terjadi kesalahan pada server!",
                        });
                    }
                });
            }
        });
    });

    $("#exportExcelAllocated").on("click", () => window.location.href = "php/export_allocated");

    // Allocated Material
    let allocatedRowIndex = 1;

    $("#addAllocatedRow").on("click", function () {
        const newRow = `
            <tr>
                <td>
                    <select name="items[${allocatedRowIndex}][item_code]" class="form-select select2 item-code" data-index="${allocatedRowIndex}" required>
                        <option value="">Pilih Item Code</option>
                    </select>
                </td>
                <td><input type="text" name="items[${allocatedRowIndex}][item_description]" class="form-control item-description" readonly></td>
                <td><input type="number" step="0.01" name="items[${allocatedRowIndex}][qty_picking]" class="form-control" required></td>
                <td>
                <button type="button" class="btn btn-sm removeAllocatedRow">
                <i data-feather="trash-2" class="text-danger"></i>
                </td>
            </tr>`;
        $("#allocatedItemRows").append(newRow);
        feather.replace();

        loadSKUDropdown($(`select[name="items[${allocatedRowIndex}][item_code]"]`), allocatedRowIndex, "#allocatedModal");
        allocatedRowIndex++;
    });

    $("#allocatedItemTable").on("click", ".removeAllocatedRow", function () {
        if ($("#allocatedItemRows tr").length > 1) {
            $(this).closest("tr").remove();
        } else {
            showWarningToast("Minimal harus ada satu baris item allocated.");
        }
    });

    $("#allocatedModal").on("shown.bs.modal", function () {
        loadSKUDropdown($('#allocatedItemRows select[name="items[0][item_code]"]'), 0, "#allocatedModal");
    });

    $("#allocatedModal").on("hidden.bs.modal", function () {
        $("#allocatedForm")[0].reset();
        $("#allocatedItemRows").html(`
            <tr>
                <td>
                    <select name="items[0][item_code]" class="form-select select2 item-code" data-index="0" required>
                        <option value="">Pilih Item Code</option>
                    </select>
                </td>
                <td><input type="text" name="items[0][item_description]" class="form-control item-description" readonly></td>
                <td><input type="number" step="0.01" name="items[0][qty_picking]" class="form-control" required></td>
                <td>
                <button type="button" class="btn btn-sm removeAllocatedRow">
                <i data-feather="trash-2" class="text-danger"></i>
                </td>
            </tr>
        `);
        allocatedRowIndex = 1;
         feather.replace();
        loadSKUDropdown($('#allocatedItemRows select[name="items[0][item_code]"]'), 0, "#allocatedModal");
    });

    $("#allocatedForm").on("submit", function (e) {
        e.preventDefault();
        const $submitBtn = $(this).find('button[type="submit"]');
        $submitBtn.prop('disabled', true);
        showLoading();

        $.ajax({
            url: "modules/proses_allocated",
            type: "POST",
            data: $(this).serialize(),
            dataType: "json",
            success: function (response) {
                hideLoading();
                $submitBtn.prop('disabled', false);
                if (response.success === true) {
                    $("#allocatedModal").modal("hide");
                    $("#tabelAllocated").DataTable().ajax.reload();
                    $("#allocatedForm")[0].reset();
                    allocatedRowIndex = 1;
                    showSuccessToast(`Allocated Success! Transaction ID: ${response.transaction_id || 'N/A'}`);
                } else {
                    showErrorToast(response.message || "Terjadi kesalahan saat Allocated!");
                }
            },
            error: function (xhr) {
                hideLoading();
                $submitBtn.prop('disabled', false);
                const response = JSON.parse(xhr.responseText) || { success: false, message: "Terjadi kesalahan tidak terduga!" };
                if (!response.success) {
                    if (response.message && response.message.includes("Stok tidak cukup")) {
                        showErrorToast(response.message || "Stok tidak mencukupi!");
                    } else {
                        showErrorToast(response.message || "Terjadi kesalahan saat menyimpan data!");
                    }
                } else {
                    showErrorToast("Terjadi kesalahan tidak terduga!");
                }
            },
        });
    });

    $("#allocatedModal").on("hidden.bs.modal", function () {
        $("#allocatedForm")[0].reset();
    });

    // Bulk Upload Allocated
    $("#bulkAllocatedForm").on("submit", function (e) {
        e.preventDefault();
        const $submitBtn = $(this).find('button[type="submit"]');
        $submitBtn.prop('disabled', true);
        showLoading();

        const formData = new FormData(this);
        $.ajax({
            url: "modules/proses_bulk_allocated",
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            dataType: "json",
            success: function (response) {
                hideLoading();
                $submitBtn.prop('disabled', false);
                $("#bulkAllocatedModal").modal("hide");
                $("#tabelAllocated").DataTable().ajax.reload();
                let swalConfig = {
                    icon: response.status === "success" ? "success" : "error",
                    title: response.status === "success" ? "Sukses" : "Gagal",
                    text: response.message,
                };
                if (response.details && response.details.length > 0) {
                    swalConfig.html = `${response.message}<br><br><strong>Detail Kesalahan:</strong><ul style="text-align: left;">${response.details.map((d) => `<li>${d}</li>`).join("")}</ul>`;
                }
                Swal.fire(swalConfig);
            },
            error: function (xhr) {
                hideLoading();
                $submitBtn.prop('disabled', false);
                const response = JSON.parse(xhr.responseText) || { status: "error", message: "Terjadi kesalahan tidak terduga!" };
                let swalConfig = {
                    icon: "error",
                    title: "Gagal",
                    text: response.message || "Terjadi kesalahan saat mengunggah file!",
                };
                if (response.details && response.details.length > 0) {
                    swalConfig.html = `${response.message}<br><br><strong>Detail Kesalahan:</strong><ul style="text-align: left;">${response.details.map((d) => `<li>${d}</li>`).join("")}</ul>`;
                }
                Swal.fire(swalConfig);
            },
        });
    });

     // Handle generate order number button
    $('#generateOrderNumber').click(function() {
    const button = $(this);
    const orderNumberInput = $('#order_number');
    
    // Disable tombol (akan berubah warna abu-abu sesuai styling Bootstrap)
    button.prop('disabled', true);
    
    $.ajax({
        url: 'modules/generate_order_number',
        type: 'POST',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Isi field order_number dan set read-only
                orderNumberInput.val(response.order_number);
                orderNumberInput.prop('readonly', true);
                
                // Tampilkan notifikasi sukses
                showSuccessToast('Order number generated successfully!');
            } else {
                // Tampilkan error dan enable tombol kembali
                showErrorToast(response.message || 'Failed to generate order number');
                button.prop('disabled', false);
            }
        },
        error: function(xhr, status, error) {
            handleAjaxError(xhr, 'Terjadi Kesalahan Tak Terduga:');
            button.prop('disabled', false);
        }
        // Tidak ada complete block, tombol tetap disable setelah generate berhasil
    });
});

// Enable tombol, reset form, dan hapus read-only saat modal dibuka
$('#allocatedModal').on('shown.bs.modal', function() {
    $('#order_number').val('');
    $('#order_number').prop('readonly', false);
    $('#generateOrderNumber').prop('disabled', false);
});

// Reset form saat modal ditutup
$('#allocatedModal').on('hidden.bs.modal', function() {
    $('#order_number').val('');
});
}

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

