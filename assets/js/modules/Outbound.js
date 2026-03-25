function initPageScripts() {
     console.log("✅ Outbound.js loaded");

      $("#tabelOutbound").DataTable({
            ajax: {
                url: "API/data_table_outbound",
                dataSrc: "data",
                beforeSend: showLoading,
                complete: hideLoading
            },
            columns: [
                { data: null, render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1 },
                { 
                    data: "created_date",
                    render: (data) => {
                        if (!data) return '';
                        const d = new Date(data);
                        return `${String(d.getDate()).padStart(2, '0')}-${String(d.getMonth() + 1).padStart(2, '0')}-${d.getFullYear()}`;
                    }
                },
                { data: "transaction_id" },
                { data: "order_number" },
                { data: "customer" },
                { data: "lottable3" },
                { data: "total_items" },
                { data: "packing_list" },
                { data: "wh_name" },
                {
                    data: null,
                    render: (data, type, row) => `<button class="btn btn-sm view-items"
                            data-id="${row.id}" 
                            data-order_number="${row.order_number}" 
                            data-bs-toggle="tooltip" 
                            data-bs-placement="left" 
                            title="View Items">
                                <i class="fas fa-eye" style="color:#00008B;"></i>
                        </button>
                        <a href="../modules/print_dn?transaction_id=${row.transaction_id}" 
                        target="_blank" 
                        class="btn btn-sm" 
                        data-bs-toggle="tooltip" 
                        data-bs-placement="top" 
                        title="Print Document">
                        <i class="fas fa-print"></i>
            </a>`
                }
            ],
            order: [[0, "asc"]],
            language: { url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json" },
            scrollX: true,
            fixedColumns: { leftColumns: 0, rightColumns: 1 },
            initComplete: function () {
                initDataTableSearch(this.api());
            },
            destroy: true
        });
    }

    $("#exportExcelOutbound").on("click", () => window.location.href = "../API/export_excel_outbound");

    $("#tabelOutbound tbody").on("click", ".view-items", function () {
        const orderNumber = $(this).data("order_number");
        $("#viewItemsModalLabel").text(`Detail Items untuk Transaction ID: ${orderNumber}`);

        if ($.fn.DataTable.isDataTable('#tabelItems')) {
            $('#tabelItems').DataTable().destroy();
        }

        $("#tabelItems").DataTable({
            ajax: {
                url: "API/view_outbound_items",
                type: "POST",
                data: { order_number: orderNumber },
                dataSrc: "",
                beforeSend: showLoading,
                complete: hideLoading,
                error: function (xhr, error, thrown) {
                    console.error("Failed to load detail items: ", thrown, xhr.responseText);
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
                { data: "locator" },
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
            destroy: true,
            
        });

        $("#viewItemsModal").modal("show");
    });
