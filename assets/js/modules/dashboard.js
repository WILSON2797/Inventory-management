// ======================================================
// assets/js/modules/dashboard.js
// Modul khusus halaman dashboard (SPA friendly)
// ======================================================

function initPageScripts() {
    console.log("✅ dashboard.js loaded");

    // ======================================================
    // 1️⃣ Variabel Global
    // ======================================================
    let inventoryChart = null;
    let stockPieChart = null;

    // ======================================================
    // 2️⃣ Load Dashboard Data
    // ======================================================
    function loadDashboardData(whName = '') {
        console.log("🔄 Loading dashboard data for warehouse:", whName || "All");
        
        if (typeof window.showLoading === "function") {
            window.showLoading('Loading dashboard data...');
        }

        $.ajax({
            url: 'API/chart',
            type: 'GET',
            data: { wh_name: whName },
            dataType: 'json',
            success: function(response) {
                if (typeof window.hideLoading === "function") {
                    window.hideLoading();
                }

                if (response.status === 'success' && response.data.length > 0) {
                    const data = response.data[0];
                    console.log("✅ Dashboard data loaded:", data);
                    
                    // Update cards
                    updateSummaryCards(data);
                    
                    // Update charts
                    updateInventoryChart(data);
                    updateStockPieChart(data);
                    
                } else {
                    console.error("❌ No data received from server");
                    Swal.fire({
                        icon: 'warning',
                        title: 'No Data',
                        text: 'Tidak ada data untuk warehouse yang dipilih',
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            },
            error: function(xhr, status, error) {
                if (typeof window.hideLoading === "function") {
                    window.hideLoading();
                }
                
                console.error("❌ Failed to load dashboard data:", error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Gagal memuat data dashboard. Silakan refresh halaman.'
                });
            }
        });
    }

    // ======================================================
    // 3️⃣ Update Summary Cards
    // ======================================================
    function updateSummaryCards(data) {
        // Update dengan ID spesifik
        $('#totalInbound').text(formatNumber(data.total_inbound || 0));
        $('#totalAllocated').text(formatNumber(data.total_allocated || 0));
        $('#totalOutbound').text(formatNumber(data.total_out || 0));
        $('#totalOnHand').text(formatNumber(data.total_on_hand || 0));
        $('#totalBalance').text(formatNumber(data.total_balance || 0));
        
        console.log("✅ Summary cards updated");
    }

    // ======================================================
    // 4️⃣ Update Inventory Chart (Line/Bar Chart)
    // ======================================================
    function updateInventoryChart(data) {
        const ctx = document.getElementById('inventoryChart');
        if (!ctx) {
            console.warn("⚠️ inventoryChart canvas not found");
            return;
        }

        // Destroy existing chart
        if (inventoryChart) {
            inventoryChart.destroy();
        }

        // Create new chart
        inventoryChart = new Chart(ctx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: ['Inbound', 'Allocated', 'Outbound', 'On Hand', 'Balance'],
                datasets: [{
                    label: 'Quantity',
                    data: [
                        data.total_inbound || 0,
                        data.total_allocated || 0,
                        data.total_out || 0,
                        data.total_on_hand || 0,
                        data.total_balance || 0
                    ],
                    backgroundColor: [
                        'rgba(78, 115, 223, 0.8)',
                        'rgba(28, 200, 138, 0.8)',
                        'rgba(246, 194, 62, 0.8)',
                        'rgba(54, 185, 204, 0.8)',
                        'rgba(133, 135, 150, 0.8)'
                    ],
                    borderColor: [
                        'rgb(78, 115, 223)',
                        'rgb(28, 200, 138)',
                        'rgb(246, 194, 62)',
                        'rgb(54, 185, 204)',
                        'rgb(133, 135, 150)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        padding: 12,
                        titleFont: {
                            size: 14
                        },
                        bodyFont: {
                            size: 13
                        },
                        callbacks: {
                            label: function(context) {
                                return 'Total: ' + formatNumber(context.parsed.y);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return formatNumber(value);
                            }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        console.log("✅ Inventory chart updated");
    }

    // ======================================================
    // 5️⃣ Update Stock Pie Chart
    // ======================================================
    function updateStockPieChart(data) {
        const ctx = document.getElementById('stockPieChart');
        if (!ctx) {
            console.warn("⚠️ stockPieChart canvas not found");
            return;
        }

        // Destroy existing chart
        if (stockPieChart) {
            stockPieChart.destroy();
        }

        // Calculate percentages
        const total = (data.total_inbound || 0) + (data.total_allocated || 0) + (data.total_out || 0);
        const inStock = data.total_on_hand || 0;
        const allocated = data.total_allocated || 0;
        const outbound = data.total_out || 0;

        // Create new chart
        stockPieChart = new Chart(ctx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['In Stock', 'Allocated', 'Outbound'],
                datasets: [{
                    data: [inStock, allocated, outbound],
                    backgroundColor: [
                        'rgba(78, 115, 223, 0.8)',
                        'rgba(28, 200, 138, 0.8)',
                        'rgba(246, 194, 62, 0.8)'
                    ],
                    borderColor: [
                        'rgb(78, 115, 223)',
                        'rgb(28, 200, 138)',
                        'rgb(246, 194, 62)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        padding: 12,
                        titleFont: {
                            size: 14
                        },
                        bodyFont: {
                            size: 13
                        },
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return label + ': ' + formatNumber(value) + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });

        console.log("✅ Stock pie chart updated");
    }

    // ======================================================
    // 6️⃣ Warehouse Filter Change Event
    // ======================================================
    $('#whFilter').on('change', function() {
        const selectedWh = $(this).val();
        console.log("🏢 Warehouse filter changed to:", selectedWh || "All");
        loadDashboardData(selectedWh);
    });

    // ======================================================
    // 7️⃣ Helper Function - Format Number
    // ======================================================
    function formatNumber(num) {
        if (num === null || num === undefined) return '0';
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    // ======================================================
    // 8️⃣ Cleanup Function
    // ======================================================
    window.cleanupDashboard = function() {
        // Destroy charts
        if (inventoryChart) {
            inventoryChart.destroy();
            inventoryChart = null;
        }
        if (stockPieChart) {
            stockPieChart.destroy();
            stockPieChart = null;
        }
        
        // Remove event listeners
        $('#whFilter').off('change');
        
        console.log("🧹 Dashboard cleanup completed");
    };

    // ======================================================
    // 9️⃣ Initialize Dashboard
    // ======================================================
    function initDashboard() {
        console.log("🚀 Initializing dashboard...");
        
        // Load data berdasarkan warehouse yang dipilih di dropdown
        const selectedWh = $('#whFilter').val();
        loadDashboardData(selectedWh);
    }

    // ======================================================
    // 🔟 Auto Initialize on Page Load
    // ======================================================
    // Delay sedikit untuk memastikan Chart.js sudah loaded
    setTimeout(function() {
        if (typeof Chart === 'undefined') {
            console.error("❌ Chart.js library not loaded!");
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Chart.js library tidak ditemukan. Silakan periksa koneksi internet Anda.'
            });
        } else {
            initDashboard();
        }
    }, 100);

    console.log("✅ All dashboard event handlers initialized");
}

// Export function untuk SPA
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { initPageScripts };
}