function initPageScripts() {
     console.log("✅ StockDetails.js loaded");
     
$("#tabelStock").DataTable({
            ajax: {
                url: "API/data_table_stock",
                dataSrc: "data",
                beforeSend: showLoading,
                complete: hideLoading
            },
            columns: [
                { data: null, render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1 },
                { data: "po_number" },
                { data: "supplier" },
                { data: "packing_list" },
                { data: "item_code" },
                { data: "item_description" },
                { data: "qty_inbound" },
                { data: "qty_allocated" },
                { data: "qty_out" },
                { 
                data: "stock_on_hand",
                render: function(data, type, row) {
                    const value = parseFloat(data) || 0.00;
                    const Color = value > 0.00 ? '#155724' : '#ff6b6b'; // Green or Red background
                    return `<span style="background-color: ${Color}; color: white; padding: 1px 6px; border-radius: 4px; font-size: 10px;">${data}</span>`;
                }
            },
                
                { 
                data: "stock_balance",
                render: function(data, type, row) {
                    const value = parseFloat(data) || 0.00;
                    const bgColor = value > 0.00 ? '#155724' : '#ff6b6b'; // Green or Red background
                    return `<span style="background-color: ${bgColor}; color: white; padding: 1px 6px; border-radius: 4px; font-size: 10px;">${data}</span>`;
                }
            },
                { data: "uom" },
                { data: "locator" },
                { data: "stock_type" },
                { data: "wh_name" },
                { data: "last_updated" },
                {
                    data: null,
                    render: (data, type, row) => `<button class="btn btn-sm edit-stock" data-id="${row.id}"data-bs-toggle="tooltip" data-bs-placement="left" title="Action">
                    <i class="fas fa-pen text-dark"></i>
                    </button> 
                    `
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

    $("#exportExcelStock").on("click", () => window.location.href = "API/export_excel_stock");