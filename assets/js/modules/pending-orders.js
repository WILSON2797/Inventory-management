/**
 * Pending Orders Notification Module
 * Handles notification badge and modal for pending material requests
 */

const PendingOrdersModule = (function() {
    'use strict';
    
    // Configuration
    const CONFIG = {
        apiUrl: 'API/get_pending_requests.php',
        refreshInterval: 300000, // 5 minutes
        elements: {
            badge: 'pendingOrderBadge',
            modal: 'pendingOrdersModal',
            loading: 'pendingOrdersLoading',
            tableContainer: 'pendingOrdersTable',
            tableBody: 'pendingOrdersTableBody',
            emptyState: 'pendingOrdersEmpty',
            errorState: 'pendingOrdersError',
            errorMessage: 'pendingOrdersErrorMessage',
            refreshBtn: 'refreshPendingOrders'
        }
    };
    
    // Cache DOM elements
    let elements = {};
    
    /**
     * Initialize DOM element cache
     */
    function cacheDOMElements() {
        Object.keys(CONFIG.elements).forEach(key => {
            elements[key] = document.getElementById(CONFIG.elements[key]);
        });
    }
    
    /**
     * Load pending orders from API
     * @returns {Promise<Array>} Array of pending orders
     */
    async function loadPendingOrders() {
        try {
            const response = await fetch(CONFIG.apiUrl, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });
            
            // Check if response is JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Server returned non-JSON response. Please check API endpoint.');
            }
            
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.message || `HTTP error! status: ${response.status}`);
            }
            
            if (data.success) {
                return data.data || [];
            } else {
                throw new Error(data.message || 'Failed to load pending requests');
            }
        } catch (error) {
            console.error('Error loading pending orders:', error);
            throw error;
        }
    }
    
    /**
     * Update notification badge
     * @param {number} count - Number of pending orders
     */
    function updateBadge(count) {
        if (!elements.badge) return;
        
        if (count > 0) {
            elements.badge.textContent = count > 99 ? '99+' : count;
            elements.badge.style.display = 'block';
        } else {
            elements.badge.style.display = 'none';
        }
    }
    
    /**
     * Show loading state
     */
    function showLoading() {
        if (elements.loading) elements.loading.style.display = 'block';
        if (elements.tableContainer) elements.tableContainer.style.display = 'none';
        if (elements.emptyState) elements.emptyState.style.display = 'none';
        if (elements.errorState) elements.errorState.style.display = 'none';
    }
    
    /**
     * Show error state
     * @param {string} message - Error message to display
     */
    function showError(message) {
        if (elements.loading) elements.loading.style.display = 'none';
        if (elements.tableContainer) elements.tableContainer.style.display = 'none';
        if (elements.emptyState) elements.emptyState.style.display = 'none';
        if (elements.errorState) {
            elements.errorState.style.display = 'block';
            if (elements.errorMessage) {
                elements.errorMessage.textContent = message || 'An error occurred while loading pending requests.';
            }
        }
    }
    
    /**
     * Show empty state
     */
    function showEmpty() {
        if (elements.loading) elements.loading.style.display = 'none';
        if (elements.tableContainer) elements.tableContainer.style.display = 'none';
        if (elements.errorState) elements.errorState.style.display = 'none';
        if (elements.emptyState) elements.emptyState.style.display = 'block';
    }
    
    /**
     * Populate table with orders
     * @param {Array} orders - Array of pending orders
     */
    function populateTable(orders) {
        if (elements.loading) elements.loading.style.display = 'none';
        if (elements.errorState) elements.errorState.style.display = 'none';
        
        if (!orders || orders.length === 0) {
            showEmpty();
            return;
        }
        
        if (elements.emptyState) elements.emptyState.style.display = 'none';
        if (elements.tableContainer) elements.tableContainer.style.display = 'block';
        
        if (!elements.tableBody) return;
        
        // Clear existing rows
        elements.tableBody.innerHTML = '';
        
        // Populate rows
        orders.forEach(order => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td><strong>${escapeHtml(order.request_number)}</strong></td>
                <td>${escapeHtml(order.request_date)}</td>
                <td><span class="badge bg-info">${escapeHtml(order.request_stock_type)}</span></td>
                <td>${escapeHtml(order.request_by)}</td>
                <td>${escapeHtml(order.wh_name)}</td>
                <td>${order.project_name ? escapeHtml(order.project_name) : '-'}</td>
                <td><strong>${escapeHtml(order.total_qty)}</strong></td>
                <td><span class="badge bg-warning text-dark">${escapeHtml(order.status)}</span></td>
                <td>${order.remarks ? escapeHtml(order.remarks) : '-'}</td>
            `;
            elements.tableBody.appendChild(row);
        });
        
        // Reinitialize feather icons
        if (typeof feather !== 'undefined') {
            feather.replace();
        }
    }
    
    /**
     * Escape HTML to prevent XSS
     * @param {string} text - Text to escape
     * @returns {string} Escaped text
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    /**
     * Load and update badge only (for background refresh)
     */
    async function updateBadgeOnly() {
        try {
            const orders = await loadPendingOrders();
            updateBadge(orders.length);
        } catch (error) {
            console.error('Failed to update badge:', error);
            // Don't show error to user for background updates
        }
    }
    
    /**
     * Load and populate modal
     */
    async function loadModal() {
        showLoading();
        
        try {
            const orders = await loadPendingOrders();
            updateBadge(orders.length);
            populateTable(orders);
        } catch (error) {
            console.error('Failed to load modal:', error);
            showError(error.message);
        }
    }
    
    /**
     * Handle refresh button click
     */
    async function handleRefresh() {
        const btn = elements.refreshBtn;
        if (!btn) return;
        
        const originalHtml = btn.innerHTML;
        
        // Disable button and show loading
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Refreshing...';
        
        try {
            await loadModal();
        } finally {
            // Re-enable button
            btn.disabled = false;
            btn.innerHTML = originalHtml;
            
            // Reinitialize feather icons
            if (typeof feather !== 'undefined') {
                feather.replace();
            }
        }
    }
    
    /**
     * Setup event listeners
     */
    function setupEventListeners() {
        // Modal show event
        if (elements.modal) {
            elements.modal.addEventListener('show.bs.modal', loadModal);
        }
        
        // Refresh button click
        if (elements.refreshBtn) {
            elements.refreshBtn.addEventListener('click', handleRefresh);
        }
    }
    
    /**
     * Start auto-refresh interval
     */
    function startAutoRefresh() {
        setInterval(() => {
            updateBadgeOnly();
        }, CONFIG.refreshInterval);
    }
    
    /**
     * Initialize module
     */
    function init() {
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
            return;
        }
        
        cacheDOMElements();
        setupEventListeners();
        
        // Initial load
        updateBadgeOnly();
        
        // Start auto-refresh
        startAutoRefresh();
        
        console.log('Pending Orders Module initialized');
    }
    
    // Public API
    return {
        init: init,
        refresh: updateBadgeOnly,
        loadModal: loadModal
    };
})();

// Auto-initialize when script loads
PendingOrdersModule.init();