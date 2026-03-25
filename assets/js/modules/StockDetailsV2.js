function initPageScripts() {
  console.log("StockDetails.js berjalan");

  // Variabel untuk tracking state
  let isUpdating = false;
  let currentFreezeStockId = null;

  // Inisialisasi DataTable
  window.table = $("#tabelStock").DataTable({
    ajax: {
      url: "API/data_table_stock",
      dataSrc: "data",
      beforeSend: showLoading,
      complete: hideLoading,
    },
    columns: [
      {
        data: null,
        render: (data, type, row, meta) =>
          meta.row + meta.settings._iDisplayStart + 1,
      },
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
        render: function (data) {
          const value = parseFloat(data) || 0.0;
          const color = value > 0.0 ? "#155724" : "#ff6b6b";
          return `<span style="background-color:${color};color:#fff;padding:1px 6px;border-radius:4px;font-size:10px;">${data}</span>`;
        },
      },
      {
        data: "stock_balance",
        render: function (data) {
          const value = parseFloat(data) || 0.0;
          const color = value > 0.0 ? "#155724" : "#ff6b6b";
          return `<span style="background-color:${color};color:#fff;padding:1px 6px;border-radius:4px;font-size:10px;">${data}</span>`;
        },
      },
      { data: "uom" },
      { data: "locator" },
      { data: "stock_type" },
      { data: "wh_name" },
      { data: "last_updated" },
      {
        data: "status_stock",
        render: function (data, type, row) {
          const status = data ? data.toString().toLowerCase() : "";

          if (status === "active") {
            return '<span class="badge" style="background-color: #228B22; color: white; padding: 0.4em 0.8em; border-radius: 0.25rem;">Active</span>';
          } else if (status === "freeze") {
            return '<span class="badge" style="background-color: #495057; color: white; padding: 0.4em 0.8em; border-radius: 0.25rem;">Freeze</span>';
          } else {
            return `<span class="badge bg-secondary">${data || "Unknown"}</span>`;
          }
        },
      },
      { data: "freeze_reason" },
      {
        data: "status_stock",
        render: function (data, type, row) {
          // Normalisasi status
          const status = data ? data.toString().toLowerCase() : "";
          const isActive = status === "active";
          
          // PENTING: Jangan gunakan tooltip di sini, akan ditambahkan via JavaScript
          return `
            <label class="toggle-switch">
                <input type="checkbox" 
                       class="toggle-stock-status"
                       data-id="${row.id}" 
                       data-status="${status}"
                       ${isActive ? "checked" : ""}>
                <span class="toggle-slider"></span>
            </label>`;
        },
      },
    ],
    order: [[0, "asc"]],
    language: {
      url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json",
    },
    scrollX: true,
    fixedColumns: { leftColumns: 0, rightColumns: 1 },
    initComplete: function () {
      initDataTableSearch(this.api());
    },
    drawCallback: function () {
      // HAPUS semua tooltip yang ada untuk prevent duplikasi
      $('.toggle-switch').tooltip('dispose');
      
      // Inisialisasi tooltip HANYA untuk toggle yang baru di-render
      $('.toggle-switch').each(function() {
        const $label = $(this);
        const $input = $label.find('.toggle-stock-status');
        const isChecked = $input.is(':checked');
        const tooltipText = isChecked ? "Freeze Stock" : "UnFreeze Stock";
        
        $label.attr('title', tooltipText)
              .tooltip({
                placement: 'top',
                trigger: 'hover'
              });
      });
    },
    destroy: true,
  });

  // Event Handler untuk Toggle - gunakan event delegation sederhana
  $(document).off("change", ".toggle-stock-status").on("change", ".toggle-stock-status", function () {
    // Prevent multiple operations
    if (isUpdating) {
      return false;
    }

    const $toggle = $(this);
    const stockId = $toggle.data("id");
    const currentStatus = $toggle.data("status");
    const isChecked = $toggle.is(":checked");
    
    // Determine new status
    const newStatus = isChecked ? "Active" : "Freeze";

    if (newStatus === "Freeze") {
      // Set flag dan simpan ID
      isUpdating = true;
      currentFreezeStockId = stockId;
      
      // Set form
      $("#freezeStockId").val(stockId);
      $("#freezeReason").val("");
      
      // Show modal
      $("#freezeReasonModal").modal("show");
    } else {
      // Unfreeze langsung
      isUpdating = true;
      updateStockStatus(stockId, newStatus, "");
    }
  });

  // Form Submit Reason
  $("#freezeReasonForm").off("submit").on("submit", function (e) {
    e.preventDefault();
    
    const id = $("#freezeStockId").val();
    const reason = $("#freezeReason").val().trim();

    if (reason === "") {
      Swal.fire("Perhatian", "Harap isi alasan terlebih dahulu.", "warning");
      return;
    }

    // Close modal
    $("#freezeReasonModal").modal("hide");
    
    // Update status
    updateStockStatus(id, "Freeze", reason);
    
    // Clear ID
    currentFreezeStockId = null;
  });

  // Handle modal close tanpa submit
  $("#freezeReasonModal").off("hidden.bs.modal").on("hidden.bs.modal", function () {
    // Jika modal ditutup tapi masih ada pending freeze
    if (currentFreezeStockId !== null) {
      // Cari toggle dan kembalikan ke checked (Active)
      const $toggle = $(`.toggle-stock-status[data-id="${currentFreezeStockId}"]`);
      if ($toggle.length) {
        $toggle.prop("checked", true);
      }
      
      // Reset state
      currentFreezeStockId = null;
      isUpdating = false;
    }
  });

  // Function Update Status
  function updateStockStatus(stockId, newStatus, reason) {
    $.ajax({
      url: "modules/update_stock_status.php",
      type: "POST",
      data: { 
        id: stockId, 
        status_stock: newStatus, 
        reason: reason 
      },
      dataType: "json",
      success: function (response) {
        if (response.success) {
          Swal.fire({
            icon: "success",
            title: "Berhasil",
            text: `Stock berhasil diubah menjadi ${newStatus.toUpperCase()}`,
            timer: 1500,
            showConfirmButton: false,
          });

          // Reload table dan reset flag setelah selesai
          window.table.ajax.reload(function() {
            isUpdating = false;
          }, false);
          
        } else {
          // Gagal - rollback toggle
          Swal.fire("Gagal", response.message, "error");
          rollbackToggle(stockId);
        }
      },
      error: function () {
        Swal.fire("Error", "Tidak dapat menghubungi server.", "error");
        rollbackToggle(stockId);
      },
    });
  }

  // Function untuk rollback toggle
  function rollbackToggle(stockId) {
    const $toggle = $(`.toggle-stock-status[data-id="${stockId}"]`);
    if ($toggle.length) {
      const currentStatus = $toggle.data("status");
      $toggle.prop("checked", currentStatus === "active");
    }
    isUpdating = false;
  }

  // Export Excel Handler
  $("#exportExcelStock").off("click").on("click", function () {
    window.location.href = "API/export_excel_stock";
  });
}