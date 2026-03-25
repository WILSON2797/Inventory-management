function initPageScripts() {
  console.log("recipient.js loaded");
  let isUpdating = false;
  
  // Fungsi helper untuk update tooltip
  function updateTooltip($label) {
    const $input = $label.find('.toggle-recipient-status');
    const isChecked = $input.is(':checked');
    const tooltipText = isChecked ? "Nonaktifkan Recipient" : "Aktifkan Recipient";
    
    // Dispose tooltip lama dan buat yang baru
    $label.tooltip('dispose');
    $label.attr('data-original-title', tooltipText)
          .attr('title', tooltipText)
          .tooltip({
            placement: 'top',
            trigger: 'hover'
          });
  }
  
  // Reset form setiap modal dibuka/ditutup
  $("#recipientsModal").on("shown.bs.modal hidden.bs.modal", function () {
    $("#recipientsForm")[0].reset();
  });
  
  // Submit form tambah/edit recipient
  $("#recipientsForm").on("submit", function (e) {
    e.preventDefault();
    const $submitBtn = $(this).find('button[type="submit"]');
    $submitBtn.prop("disabled", true);
    showLoading();
    $.ajax({
      url: "modules/submit_recipient",
      type: "POST",
      data: $(this).serialize(),
      dataType: "json",
      success: function (response) {
        hideLoading();
        $submitBtn.prop("disabled", false);
        if (response.status === "success") {
          $("#recipientsModal").modal("hide");
          $("#tabelrecipients").DataTable().ajax.reload();
          showSuccessToast(
            response.message || 'Recipient saved successfully.',
            'Berhasil'
          );
        } else {
          showErrorToast(
            response.message || 'Terjadi kesalahan saat menyimpan data recipient.',
            'Gagal'
          );
        }
      },
      error: function () {
        hideLoading();
        $submitBtn.prop("disabled", false);
        showErrorToast(
          'Terjadi kesalahan saat menyimpan data recipient.',
          'Gagal'
        );
      },
    });
  });
  
  // DataTable
  if (!$.fn.DataTable.isDataTable('#tabelrecipients')) {
    $("#tabelrecipients").DataTable({
      ajax: {
        url: "API/data_table_recipients",
        dataSrc: "data"
      },
      columns: [
        {
          data: null,
          render: (d, t, r, meta) => meta.row + meta.settings._iDisplayStart + 1
        },
        { data: "nama" },
        { data: "created_at" },
        {
          data: "status",
          render: function (data) {
            return data.toLowerCase() === "active"
              ? '<span class="badge bg-success">Active</span>'
              : '<span class="badge bg-dark">Inactive</span>';
          }
        },
        {
          data: "status",
          render: function (data, type, row) {
            const isActive = data.toLowerCase() === "active";
            return `
              <label class="toggle-switch">
                <input type="checkbox"
                      class="toggle-recipient-status"
                      data-id="${row.id}"
                      ${isActive ? "checked" : ""}>
                <span class="toggle-slider"></span>
              </label>`;
          }
        }
      ],
      order: [[0, "asc"]],
      initComplete: function () {
        initDataTableSearch(this.api());
      },
      drawCallback: function () {
        // Dispose semua tooltip sebelum re-init
        $('.toggle-switch').tooltip('dispose');
        
        // Inisialisasi tooltip untuk setiap toggle
        $('.toggle-switch').each(function () {
          updateTooltip($(this));
        });
      },
      destroy: true
    });
  }
  
  // Toggle Status Recipient
  $(document).off("change", ".toggle-recipient-status").on("change", ".toggle-recipient-status", function () {
    if (isUpdating) return;
    
    const input = $(this);
    const $label = input.closest('.toggle-switch');
    const id = input.data("id");
    const newStatus = input.is(":checked") ? "active" : "inactive";
    
    isUpdating = true;
    showLoading();
    
    $.ajax({
      url: "modules/update_recipient_status",
      type: "POST",
      data: { id, status: newStatus },
      dataType: "json",
      success: function (response) {
        hideLoading();
        if (response.status !== "success") {
          // Rollback toggle jika gagal
          input.prop("checked", !input.is(":checked"));
          updateTooltip($label); // Update tooltip setelah rollback
          Swal.fire("Gagal", response.message, "error");
        } else {
          // Update tooltip setelah berhasil
          updateTooltip($label);
          showSuccessToast(
            response.message || 'Status berhasil diperbarui.',
            'Berhasil'
          );
          // Reload tanpa reset pagination
          $("#tabelrecipients").DataTable().ajax.reload(null, false);
        }
      },
      error: function () {
        hideLoading();
        // Rollback toggle jika error
        input.prop("checked", !input.is(":checked"));
        updateTooltip($label); // Update tooltip setelah rollback
        showErrorToast(
          'Terjadi kesalahan saat memperbarui status recipient.',
          'Gagal'
        );
      },
      complete: function () {
        isUpdating = false;
      }
    });
  });
  
  // Export Excel
  $("#exportExcelrecipients").on("click", () => window.location.href = "../API/export_recipients");
}