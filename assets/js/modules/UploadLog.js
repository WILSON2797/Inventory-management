// File Status Table
    if ($('#fileStatusTable').length) {
        const fileStatusTable = $('#fileStatusTable').DataTable({
            ajax: {
                url: '../API/get_queue_tasks',
                dataSrc: ''
            },
            columns: [
                {
                    data: null,
                    render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1
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
                    render: (data, type, row) => row.report_path ? `<a href="../modules/Download_ErrorReport?id=${row.id}" class="btn btn-sm btn-primary"><i class="fas fa-file"></i>Download</a>` : '-',
                    orderable: false
                }
            ],
            order: [[5, "desc"]],
            language: { url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json" },
            scrollX: true,
            fixedColumns: { leftColumns: 0, rightColumns: 1 },
            destroy: true
        });

        $("#refreshBtn").on("click", () => fileStatusTable.ajax.reload());
    }