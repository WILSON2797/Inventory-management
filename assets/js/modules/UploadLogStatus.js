function initPageScripts() {
    console.log("✅ user_setting.js loaded");

// File Status Table
    if ($('#fileStatusTable').length) {
        const fileStatusTable = $('#fileStatusTable').DataTable({
            ajax: {
                url: 'API/get_queue_tasks.php',
                dataSrc: '',
                beforeSend: showLoading,
                complete: hideLoading,
            },
            columns: [
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: (data, type, row, meta) => meta.row + 1,
                },
                { data: "task_type" },
                { data: "file_name" },
                {
                    data: "status",
                    render: function (data) {
                        const statusMap = {
                            'success': '<span class="badge bg-success">Success</span>',
                            'warning': '<span class="badge bg-warning">Warning</span>',
                            'error': '<span class="badge bg-danger">Error</span>'
                        };
                        return statusMap[data] || '<span class="badge bg-info">Pending</span>';
                    }
                },
                { data: "success_count", render: data => data || '0' },
                { data: "error_message", render: data => data || '-' },
                { data: "created_at" },
                { data: "username" },
                {
                    data: null,
                    render: (data, type, row) => row.report_path ? `
                    <a href="modules/Download_ErrorReport.php?id=${row.id}" 
                   class="btn btn-sm" 
                   data-bs-toggle="tooltip" 
                   data-bs-placement="top" 
                   title="Download Error Report">
                    <style="width:35px; height:35px;">
                            <i data-feather="file-text" class="text-success"></i>
                </a>` : '-',
                    orderable: false,
                    searchable: false
                }
            ],
            order: [[6, "desc"]],
            language: { url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json" },
            scrollX: true,
            fixedColumns: { leftColumns: 0, rightColumns: 1 },
            initComplete: function () {
                initDataTableSearch(this.api());
            },
            drawCallback : function () {
                feather.replace(); 
            },
            destroy: true
        });

        // ✅ Perbaikan: gunakan variabel yang benar (fileStatusTable)
        fileStatusTable.on('order.dt search.dt', function () {
            fileStatusTable
                .column(0, { search: 'applied', order: 'applied' })
                .nodes()
                .each(function (cell, i) {
                    cell.innerHTML = i + 1;
                });
        }).draw();


        $("#refreshBtn").on("click", () => fileStatusTable.ajax.reload());
    }
}