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
            <div class="d-flex align-items-center gap-2">
            <button class="btn btn-sm btn-move-locator" 
                data-id="${row.id}"
                data-item-code="${row.item_code}"
                data-item-desc="${row.item_description}"
                data-current-locator="${row.locator}"
                data-balance="${row.stock_balance}"
                data-wh="${row.wh_name}"
                data-bs-toggle="tooltip" 
                title="Move Locator">
          <i class="fas fa-exchange-alt"></i>
        </button>
        <label class="toggle-switch">
            <input type="checkbox" 
                   class="toggle-stock-status"
                   data-id="${row.id}" 
                   data-status="${status}"
                   ${isActive ? "checked" : ""}>
            <span class="toggle-slider"></span>
        </label>
      </div>`;
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
      showWarningToast("Alasan freeze harus diisi.", "Perhatian");
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
    
     $(this).find('[data-bs-toggle="tooltip"]').tooltip('dispose');
    $('.tooltip').remove(); // Hapus tooltip yang masih nongol
    
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
          showSuccessToast(`Stock berhasil diubah menjadi ${newStatus}`, "Success!");

          // Reload table dan reset flag setelah selesai
          window.table.ajax.reload(function() {
            isUpdating = false;
          }, false);
          
        } else {
          // Gagal - rollback toggle
          showErrorToast(response.message || "Gagal mengupdate status stock", "Error");
          rollbackToggle(stockId);
        }
      },
      error: function () {
        showErrorToast("Tidak dapat menghubungi server.", "Error");
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

// FUNGSI MOVE LOCATOR 
 
  // Event handler untuk Move Locator button
  $("#tabelStock").on("click", ".btn-move-locator", function () {
    const stockId = $(this).data("id");
    const itemCode = $(this).data("item-code");
    const itemDesc = $(this).data("item-desc");
    const currentLocator = $(this).data("current-locator");
    const balance = $(this).data("balance");
    const whName = $(this).data("wh");
    
    // Set data ke modal
    $("#moveStockId").val(stockId);
    $("#moveItemCode").text(itemCode);
    $("#moveItemDesc").text(itemDesc);
    $("#moveCurrentLocator").text(currentLocator);
    $("#moveQty").text(balance);
    
    // Set min/max pada input qty
    $("#moveQtyToTransfer").attr("min", 1).attr("max", balance).val(1);
    
    
    window.whName = whName; // Set temporary untuk global loadLocatorDropdown
    window.loadLocatorDropdown($("#moveNewLocator"), "#moveLocatorModal").then(() => {
      // Manual exclude currentLocator setelah load
      $("#moveNewLocator option[value='" + currentLocator + "']").remove();
      $("#moveNewLocator").val("").trigger("change"); // Reset select2
    });
    
    // Show modal
    $("#moveLocatorModal").modal("show");
  });
  
  // Form submit untuk move locator
  $("#moveLocatorForm").on("submit", function (e) {
    e.preventDefault();
    
    const stockId = $("#moveStockId").val();
    const newLocator = $("#moveNewLocator").val();
    const moveReason = $("#moveReason").val().trim();
    const qtyToMove = parseInt($("#moveQtyToTransfer").val()) || 0;
    const maxBalance = parseInt($("#moveQty").text()) || 0;
    
    // Validasi
    if (qtyToMove <= 0 || qtyToMove > maxBalance) {
      showWarningToast(`Quantity harus antara 1 dan ${maxBalance}`, "warning");
      return;
    }
    
    if (!newLocator) {
      showWarningToast("Harap pilih locator baru", "Warning");
      return;
    }
    
    if (!moveReason) {
      showWarningToast("Harap isi alasan pemindahan", "Warning");
      return;
    }
    
    // Konfirmasi
    Swal.fire({
      title: "Konfirmasi Pemindahan",
      text: `Pindahkan ${qtyToMove} unit ke locator ${newLocator}?`,
      icon: "question",
      showCancelButton: true,
      confirmButtonText: "Ya, Pindahkan",
      cancelButtonText: "Batal",
      confirmButtonColor: "#3085d6",
      cancelButtonColor: "#d33"
    }).then((result) => {
      if (result.isConfirmed) {
        processMoveLocator(stockId, newLocator, moveReason, qtyToMove);
      }
    });
  });
  
  // Function untuk proses move locator
  function processMoveLocator(stockId, newLocator, reason, qtyToMove) {
    $.ajax({
      url: "modules/move_locator.php",
      type: "POST",
      contentType: "application/json",
      data: JSON.stringify({
        id: stockId,
        new_locator: newLocator,
        reason: reason,
        qty_to_move: qtyToMove
      }),
      dataType: "json",
      beforeSend: showLoading,
      complete: hideLoading,
      success: function (response) {
        if (response.success) {
          $("#moveLocatorModal").modal("hide");
          
          let successMsg = "Item berhasil dipindahkan ke locator baru";
          if (response.data && response.data.move_type === "merged") {
            successMsg = "Item berhasil digabung dengan stock di locator tujuan";
          } else if (response.data && response.data.move_type === "split") {
            successMsg = "Partial item berhasil dipindahkan";
          }
          
          Swal.fire({
            icon: "success",
            title: "Berhasil",
            text: successMsg,
            // timer: 2000,
            showConfirmButton: true,
          });
          
          // Reload table
          window.table.ajax.reload(null, false);
          
          // Reset form
          $("#moveLocatorForm")[0].reset();
        } else {
          Swal.fire("Gagal", response.message, "error");
        }
      },
      error: function (xhr) {
        let errorMsg = "Tidak dapat menghubungi server";
        if (xhr.responseJSON && xhr.responseJSON.message) {
          errorMsg = xhr.responseJSON.message;
        }
        Swal.fire("Error", errorMsg, "error");
      }
    });
  }
  
  // Reset form ketika modal ditutup
  $("#moveLocatorModal").on("hidden.bs.modal", function () {
    $("#moveLocatorForm")[0].reset();
    
    $(this).find('[data-bs-toggle="tooltip"]').tooltip('dispose');
    $('.tooltip').remove(); // Hapus tooltip yang masih nongol
    
  });

  window.tableMovementLog = $("#tabelMovementLocatorLog").DataTable({
    ajax: {
      url: "API/data_table_movement_locator_log",
      dataSrc: "data",
      beforeSend: showLoading,
      complete: hideLoading,
      error: function(xhr, error, code) {
        console.error("Error loading movement log data:", error);
        hideLoading();
      }
    },
    columns: [
      {
        data: null,
        render: (data, type, row, meta) =>
          meta.row + meta.settings._iDisplayStart + 1,
      },
      { data: "item_code" },
      { data: "item_description" },
      { 
        data: "from_locator",
        render: function(data) {
          return data ? `<span class="badge bg-secondary">${data}</span>` : '-';
        }
      },
      { 
        data: "to_locator",
        render: function(data) {
          return data ? `<span class="badge bg-primary">${data}</span>` : '-';
        }
      },
      { 
        data: "qty",
        render: function(data) {
          return `<span class="badge bg-info text-dark">${data}</span>`;
        }
      },
      { data: "wh_name" },
      { data: "project_name" },
      { data: "packing_list" },
      { 
        data: "reason",
        render: function(data) {
          if (!data) return '-';
          // Truncate jika terlalu panjang
          if (data.length > 50) {
            return `<span title="${data}">${data.substring(0, 47)}...</span>`;
          }
          return data;
        }
      },
      
      { data: "created_by" },
      { 
        data: "created_date",
        render: function(data) {
          if (!data) return '-';
          // Format tanggal jika diperlukan
          try {
            const date = new Date(data);
            return date.toLocaleString('id-ID', {
              year: 'numeric',
              month: '2-digit',
              day: '2-digit',
              hour: '2-digit',
              minute: '2-digit'
            });
          } catch (e) {
            return data;
          }
        }
      },
      { 
        data: "action_type",
        render: function(data) {
          let badgeClass = 'bg-secondary';
          let icon = 'fa-exchange-alt';
          
          if (data === 'MOVE') {
            badgeClass = 'bg-success';
            icon = 'fa-arrow-right';
          } else if (data === 'SPLIT') {
            badgeClass = 'bg-warning text-dark';
            icon = 'fa-cut';
          } else if (data === 'MERGE') {
            badgeClass = 'bg-info';
            icon = 'fa-compress-arrows-alt';
          }
          
          return `<span class="badge ${badgeClass}">
                    <i class="fas ${icon} me-1"></i>${data}
                  </span>`;
        }
      },
      
    ],
    order: [[12, "desc"]], // Urutkan berdasarkan created_date descending
    language: {
      url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json",
    },
    scrollX: true,
    fixedColumns: { leftColumns: 0, rightColumns: 0 },
    initComplete: function () {
      initDataTableSearch(this.api());
    },
    destroy: true,
  });
  
  // ========================================
  // EXPORT EXCEL MOVEMENT LOG
  // ========================================
  $("#exportExcelMovementLog").on("click", function () {
    // Cek apakah ada data
    if (window.tableMovementLog.rows().count() === 0) {
      Swal.fire({
        icon: "warning",
        title: "Tidak Ada Data",
        text: "Tidak ada data movement log untuk di-export.",
      });
      return;
    }
    
    // Redirect ke endpoint export
    window.location.href = "API/export_excel_movement_log";
  });
  
  // ========================================
  // TAB NAVIGATION - RELOAD DATA
  // ========================================
  
  // Reload data saat tab Movement Log diklik
  $("#movement-log-tab").on("shown.bs.tab", function() {
    if (window.tableMovementLog) {
      window.tableMovementLog.ajax.reload(null, false);
    }
  });
  
  // Reload data saat tab Stock Details diklik
  $("#stock-data-tab").on("shown.bs.tab", function() {
    if (window.table) {
      window.table.ajax.reload(null, false);
    }
  });
