function initPageScripts() {
    console.log("✅ request_list.js loaded");

    // ======================================================
    // 🧭 INIT FUNCTION FOR DATATABLES
    // ======================================================
    function getDataSrc() {
        return function (json) {
            if (json && json.success && Array.isArray(json.data)) {
                return json.data;
            }
            console.warn("Unexpected API format:", json);
            return [];
        };
    }

    function createRowNumber(_, __, ___, meta) {
        return meta.row + meta.settings._iDisplayStart + 1;
    }

    // ======================================================
    // 🟡 1️⃣ Pending Request Table
    // ======================================================
    function initRequestTable() {
        const table = $("#tabelRequest");
        if ($.fn.DataTable.isDataTable(table)) {
            table.DataTable().ajax.reload();
            return;
        }

        table.DataTable({
            ajax: {
                url: "API/data_table_request_pending.php",
                dataSrc: getDataSrc(),
                beforeSend: showLoading,
                complete: hideLoading,
                error: (xhr, err, thrown) => handleAjaxError(xhr, 'Load Pending Request')
            },
            columns: [
                { data: null, render: (d, t, r, m) => m.row + 1 },
                { data: "request_date" },
                { data: "request_number" },
                { data: "request_stock_type" },
                { data: "total_qty" },
                { data: "request_by" },
                { data: "wh_name" },
                { 
                data: "status",
                render: function(data, type, row) {
                    if (type === 'display') {
                        return `<span class="badge bg-dark">${data}</span>`;
                    }
                    return data;
                }
            },
                {
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    
                    if (typeof userRole === 'undefined') {
                        console.warn('userRole undefined, defaulting to user');
                        userRole = 'user'; // Fallback
                    }
                    console.log('Current userRole:', userRole); 
                    if (userRole === 'user') {
                        return `
                            <div class="d-flex gap-1 justify-content-center">
                                <button class="btn btn-sm btn-outline-primary view-request-items" 
                                    data-request_number="${row.request_number || ''}"
                                    data-bs-toggle="tooltip" data-bs-placement="top" title="View Items & Remarks">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>`;
                    } else if (userRole === 'admin' || userRole === 'Admin') { 
                        return `
                            <div class="d-flex gap-1 justify-content-center">
                                <button class="btn btn-sm btn-outline-primary view-request-items" 
                                    data-request_number="${row.request_number || ''}"
                                    data-bs-toggle="tooltip" data-bs-placement="top" title="View Items & Remarks">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm approve-request" 
                                    data-request_number="${row.request_number || ''}" 
                                    data-bs-toggle="tooltip" data-bs-placement="top" title="Approve Request">
                                    <i class="fa fa-circle-check" style="color:#32CD32;"></i>
                                </button>
                                <button class="btn btn-sm reject-request" 
                                    data-request_number="${row.request_number || ''}" 
                                    data-bs-toggle="tooltip" data-bs-placement="top" title="Reject Request">
                                    <i class="fa fa-rotate-left" style="color:#dc3545;"></i>
                                </button>
                            </div>`;
                    }
                    return ''; 
                }
            }
            ],
            order: [[1, "desc"]],
            scrollX: true,
            fixedColumns: { leftColumns: 0, rightColumns: 1 },
            language: { url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json" },
            initComplete: function () {
                initDataTableSearch(this.api());
            },
            destroy: true
        });
    }

    // ======================================================
    // Approved Table
    // ======================================================
    function initApprovedTable() {
        const table = $("#tabelApproved");
        if ($.fn.DataTable.isDataTable(table)) {
            table.DataTable().ajax.reload();
            return;
        }

        table.DataTable({
            ajax: {
                url: "API/data_table_request_approved.php",
                dataSrc: getDataSrc(),
                beforeSend: showLoading,
                complete: hideLoading,
                error: (xhr, err, thrown) => handleAjaxError(xhr, 'Load Approved Request')
            },
            columns: [
                { data: null, render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1 },
                { data: "approved_date" },
                { data: "request_number" },
                { data: "request_stock_type" },
                { data: "total_qty" },
                { data: "approved_by" },
                { data: "wh_name" },
                { data: "project_name" },
                { 
                data: "status",
                render: function(data, type, row) {
                    if (type === 'display') {
                        return `<span class="badge bg-success">${data}</span>`;
                    }
                    return data;
                }
            },
            { data: "approved_note" },
                {
                    data: null,
                    orderable: false,
                    render: (data, type, row) => `
                        <div class="d-flex gap-1 justify-content-center">
                            <button class="btn btn-sm btn-outline-primary view-request-items" 
                                data-request_number="${row.request_number}"
                                data-bs-toggle="tooltip" data-bs-placement="top" title="View Items">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>`
                }
            ],
            order: [[1, "asc"]],
            scrollX: true,
            language: { url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json" },
            initComplete: function () {
                initDataTableSearch(this.api());
            },
            destroy: true
        });
    }

    // ======================================================
    // Reject Table
    // ======================================================
    function initRejectTable() {
        const table = $("#tabelReject");
        if ($.fn.DataTable.isDataTable(table)) {
            table.DataTable().ajax.reload();
            return;
        }

        table.DataTable({
            ajax: {
                url: "API/data_table_request_reject.php",
                dataSrc: getDataSrc(),
                beforeSend: showLoading,
                complete: hideLoading,
                error: (xhr, err, thrown) => handleAjaxError(xhr, 'Load Rejected Request')
            },
            columns: [
                { data: null, render: (d, t, r, m) => m.row + 1 },
                { data: "rejected_date" },
                { data: "request_number" },
                { data: "request_stock_type" },
                { data: "total_qty" },
                { data: "rejected_by" },
                { data: "wh_name" },
                { data: "project_name" },
                { data: "reject_reason" },
                { 
                data: "status",
                render: function(data, type, row) {
                    if (type === 'display') {
                        return `<span class="badge bg-danger">${data}</span>`;
                    }
                    return data;
                }
            },
                {
                    data: null,
                    orderable: false,
                    render: (data, type, row) => `
                        <div class="d-flex gap-1 justify-content-center">
                            <button class="btn btn-sm btn-outline-primary view-request-items" 
                                data-request_number="${row.request_number}"
                                data-bs-toggle="tooltip" data-bs-placement="top" title="View Items">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>`
                }
            ],
            order: [[1, "desc"]],
            scrollX: true,
            language: { url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json" },
            initComplete: function () {
                initDataTableSearch(this.api());
            },
            destroy: true
        });
    }

    // ======================================================
    // 🧩 INIT DEFAULT (Request TAB)
    // ======================================================
    initRequestTable();

    // ======================================================
    // 🪄 TAB SWITCH HANDLER
    // ======================================================
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        const target = $(e.target).attr('data-bs-target');
        switch (target) {
            case '#tabContentRequest':
                initRequestTable();
                break;
            case '#tabContentApproved':
                initApprovedTable();
                break;
            case '#tabContentReject':
                initRejectTable();
                break;
        }
        feather.replace();
    });

    // ======================================================
    // 🟩 EXPORT (Opsional)
    // ======================================================
    $("#exportRequestExcel").on("click", () => {
        window.location.href = "API/export_excel_request";
    });

    // ======================================================
    // REQUEST MATERIAL FORM HANDLERS (AS IS)
    // ======================================================
    let requestRowIndex = 1;

    $("#addRequestRow").on("click", function () {
        const newRow = `
            <tr>
                <td>
                    <select name="items[${requestRowIndex}][item_code]" class="form-select select2 item-code" data-index="${requestRowIndex}" required>
                        <option value="">Pilih Item</option>
                    </select>
                </td>
                <td><input type="text" name="items[${requestRowIndex}][item_description]" class="form-control item-description" readonly></td>
                <td><input type="number" step="0.01" name="items[${requestRowIndex}][qty]" class="form-control" required></td>
                <td><input type="text" name="items[${requestRowIndex}][uom]" class="form-control item-uom" readonly></td>
                <td><input type="text" name="items[${requestRowIndex}][remarks]" class="form-control"></td>
                <td>
                    <button type="button" class="btn btn-sm removeRequestRow">
                        <i data-feather="trash-2" class="text-danger"></i>
                    </button>
                </td>
            </tr>`;
        $("#requestItemRows").append(newRow);
        feather.replace();

        loadSKUDropdown($(`select[name="items[${requestRowIndex}][item_code]"]`), requestRowIndex, "#requestModal");
        requestRowIndex++;
    });

    $("#requestItemTable").on("click", ".removeRequestRow", function () {
        if ($("#requestItemRows tr").length > 1) {
            $(this).closest("tr").remove();
        } else {
            showWarningToast("Minimal harus ada satu baris item request.");
        }
    });

    $("#requestModal").on("shown.bs.modal", function () {
        if (typeof loadStockTypeDropdown === "function") {
            loadStockTypeDropdown("#requestModal");
        }
        loadSKUDropdown($('#requestItemRows select[name="items[0][item_code]"]'), 0, "#requestModal");
        $('#request_number').val('').prop('readonly', false);
        $('#generateRequestNumber').prop('disabled', false);
    });

    $("#requestModal").on("hidden.bs.modal", function () {
        $("#requestForm")[0].reset();
        $("#requestItemRows").html(`
            <tr>
                <td>
                    <select name="items[0][item_code]" class="form-select select2 item-code" data-index="0" required>
                        <option value="">Pilih Item</option>
                    </select>
                </td>
                <td><input type="text" name="items[0][item_description]" class="form-control item-description" readonly></td>
                <td><input type="number" step="0.01" name="items[0][qty]" class="form-control" required></td>
                <td><input type="text" name="items[0][uom]" class="form-control item-uom" readonly></td>
                <td><input type="text" name="items[0][remarks]" class="form-control"></td>
                <td><button type="button" class="btn btn-danger btn-sm removeRequestRow">Hapus</button></td>
            </tr>
        `);
        requestRowIndex = 1;
        loadSKUDropdown($('#requestItemRows select[name="items[0][item_code]"]'), 0, "#requestModal");
        $('#request_number').val('');
    });

    $("#requestForm").on("submit", function (e) {
        e.preventDefault();
        const $submitBtn = $(this).find('button[type="submit"]');
        $submitBtn.prop('disabled', true);
        showLoading();

        $.ajax({
            url: "modules/proses_request",
            type: "POST",
            data: $(this).serialize(),
            dataType: "json",
            success: function (response) {
                hideLoading();
                $submitBtn.prop('disabled', false);
                if (response.success === true) {
                    $("#requestModal").modal("hide");
                    $("#tabelRequest").DataTable().ajax.reload();
                    $("#requestForm")[0].reset();
                    requestRowIndex = 1;
                    showSuccessToast(`Request Success! Request Number: ${response.request_number || 'N/A'}`);
                } else {
                    showErrorToast(response.message || "Terjadi kesalahan saat menyimpan data!");
                }
            },
            error: function (xhr) {
                hideLoading();
                $submitBtn.prop('disabled', false);
                const response = JSON.parse(xhr.responseText) || { success: false, message: "Terjadi kesalahan tidak terduga!" };
                showErrorToast(response.message || "Terjadi kesalahan saat menyimpan data!");
            }
        });
    });

    $('#generateRequestNumber').on('click', function() {
        const button = $(this);
        const requestNumberInput = $('#request_number');
        button.prop('disabled', true);

        $.ajax({
            url: 'modules/generate_request_number',
            type: 'POST',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    requestNumberInput.val(response.request_number);
                    requestNumberInput.prop('readonly', true);
                    showSuccessToast('Request number generated successfully!');
                } else {
                    showErrorToast(response.message || 'Failed to generate request number');
                    button.prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                handleAjaxError(xhr, 'Terjadi Kesalahan Tak Terduga:');
                button.prop('disabled', false);
            }
        });
    });

    $(document).on("click", ".view-request-items", function () {
    const requestNumber = $(this).data("request_number");
    $("#viewItemsRequestModal .modal-title").text("Request Number: " + requestNumber);

    if ($.fn.DataTable.isDataTable('#tabelItems')) {
        $('#tabelItems').DataTable().destroy();
    }

    $("#tabelItems").DataTable({
        ajax: {
            url: "API/get_request_items",
            type: "POST",
            data: { request_number: requestNumber },
            dataSrc: "",
            beforeSend: showLoading,
            complete: hideLoading,
            error: function (xhr, error, thrown) {
                console.error("Failed to load request items: " + thrown);
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
            { data: "qty" },
            { data: "uom" },
            { data: "remarks" },
            { 
        data: null,
        render: function (data, type, row) {
            return `
            <button type="button" 
                class="btn btn-sm btn-outline-success btn-view-file" 
                data-bs-toggle="tooltip" 
                data-bs-placement="top" 
                title="Lihat File"
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

    $("#viewItemsRequestModal").modal("show");
});

// Handler untuk tombol Approve
$("#tabelRequest tbody").on("click", ".approve-request", function () {
    const requestNumber = $(this).data("request_number");

    // Set judul modal dan isi request_number
    $("#approveNoteModalLabel").text(`Approval Note ${requestNumber}`);
    $("#approve_request_number").val(requestNumber);
    $("#approved_note").val("");
    $("#approveNoteModal").modal("show");
});

// Handler untuk tombol Submit di modal
$("#submitApproveNote").on("click", function () {
    const requestNumber = $("#approve_request_number").val();
    const approvedNote = $("#approved_note").val();
    
    if (!approvedNote) {
        showErrorToast("Catatan persetujuan wajib diisi!");
        return;
    }

    showLoading();
    $.ajax({
        url: "modules/approve_request.php",
        type: "POST",
        data: {
            request_number: requestNumber,
            approved_note: approvedNote
        },
        dataType: "json",
        success: function (response) {
            hideLoading();
            if (response.success) {
                $("#approveNoteModal").modal("hide");
                $("#tabelRequest").DataTable().ajax.reload();
                showSuccessToast(response.message);
            } else {
                showErrorToast(response.message);
            }
        },
        error: function (xhr) {
            hideLoading();
            showErrorToast("Terjadi kesalahan saat menyetujui permintaan!");
        }
    });
});

// Handler untuk tombol Reject
$("#tabelRequest tbody").on("click", ".reject-request", function () {
    const requestNumber = $(this).data("request_number"); // Ubah dari order_number ke request_number

    Swal.fire({
        icon: "warning",
        title: "Konfirmasi Penolakan",
        text: `Masukkan alasan penolakan untuk permintaan ${requestNumber}:`,
        input: "textarea",
        inputPlaceholder: "Masukkan alasan penolakan...",
        showCancelButton: true,
        confirmButtonText: "Ya, Tolak",
        cancelButtonText: "Batal",
        inputValidator: (value) => {
            if (!value) {
                return "Alasan penolakan harus diisi!";
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            showLoading();
            $.ajax({
                url: "modules/reject_request.php",
                type: "POST",
                data: {
                    request_number: requestNumber,
                    reject_reason: result.value
                },
                dataType: "json",
                success: function (response) {
                    hideLoading();
                    if (response.success) {
                        $("#tabelRequest").DataTable().ajax.reload();
                        showSuccessToast(response.message);
                    } else {
                        showErrorToast(response.message);
                    }
                },
                error: function (xhr) {
                    hideLoading();
                    showErrorToast("Terjadi kesalahan saat menolak permintaan!");
                }
            });
        }
    });
});
}
