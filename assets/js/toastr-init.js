(function($) {
    'use strict';

    // Konfigurasi default Toastr
    toastr.options = {
        "closeButton": true,
        "debug": false,
        "newestOnTop": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "preventDuplicates": false,
        "onclick": null,
        "showDuration": "300",
        "hideDuration": "1000",
        "timeOut": "2000",
        "extendedTimeOut": "1000",
        "showEasing": "swing",
        "hideEasing": "linear",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut"
    };

    // ============================================
    // GLOBAL HELPER FUNCTIONS
    // ============================================

    /**
     * Show Success Toast
     * @param {string} message - Pesan yang akan ditampilkan
     * @param {string} title - Judul toast (optional)
     */
    window.showSuccessToast = function(message, title = 'Success!') {
        toastr.success(message, title);
    };

    /**
     * Show Info Toast
     * @param {string} message - Pesan yang akan ditampilkan
     * @param {string} title - Judul toast (optional)
     */
    window.showInfoToast = function(message, title = 'Informasi') {
        toastr.info(message, title);
    };

    /**
     * Show Warning Toast
     * @param {string} message - Pesan yang akan ditampilkan
     * @param {string} title - Judul toast (optional)
     */
    window.showWarningToast = function(message, title = 'Warning') {
        toastr.warning(message, title);
    };

    /**
     * Show Error Toast
     * @param {string} message - Pesan yang akan ditampilkan
     * @param {string} title - Judul toast (optional)
     */
    window.showErrorToast = function(message, title = 'Error') {
        toastr.error(message, title);
    };

    /**
     * Show Custom Toast dengan konfigurasi khusus
     * @param {string} type - Tipe toast (success/info/warning/error)
     * @param {string} message - Pesan yang akan ditampilkan
     * @param {string} title - Judul toast
     * @param {object} options - Konfigurasi tambahan (optional)
     */
    window.showCustomToast = function(type, message, title, options = {}) {
        const defaultOptions = {
            closeButton: true,
            progressBar: true,
            timeOut: 5000
        };
        
        const mergedOptions = Object.assign({}, defaultOptions, options);
        
        toastr[type](message, title, mergedOptions);
    };

    /**
     * Clear all toasts
     */
    window.clearAllToasts = function() {
        toastr.clear();
    };

    /**
     * Remove specific toast
     * @param {object} toast - Toast object yang akan dihapus
     */
    window.removeToast = function(toast) {
        toastr.remove(toast);
    };

    // ============================================
    // AJAX INTEGRATION
    // ============================================

    /**
     * Handle AJAX success response dengan toast
     * @param {object} response - Response dari server
     */
    window.handleAjaxSuccess = function(response) {
        if (response.success) {
            showSuccessToast(response.message || 'Operasi berhasil!');
        } else {
            showErrorToast(response.message || 'Operasi gagal!');
        }
    };

    /**
     * Handle AJAX error dengan toast
     * @param {object} xhr - XMLHttpRequest object
     */
    window.handleAjaxError = function (xhr, context = '') {
    console.group('⚠️ AJAX Error Detail');
    console.error('Status:', xhr.status);
    console.error('Response:', xhr.responseText);
    console.groupEnd();

    let errorMessage = '';

    switch (xhr.status) {
        case 0:
            errorMessage = 'Tidak dapat terhubung ke server. Periksa koneksi internet Anda.';
            break;
        case 400:
            errorMessage = 'Permintaan tidak valid.';
            break;
        case 401:
            errorMessage = 'Sesi Anda telah berakhir. Silakan login kembali.';
            break;
        case 403:
            errorMessage = 'Akses ditolak.';
            break;
        case 404:
            errorMessage = 'Data tidak ditemukan.';
            break;
        case 408:
            errorMessage = 'Permintaan terlalu lama. Coba lagi.';
            break;
        case 500:
        case 502:
        case 503:
            errorMessage = 'Terjadi kesalahan pada server. Silakan coba lagi nanti.';
            break;
        default:
            errorMessage = 'Terjadi kesalahan tak terduga.';
            break;
    }

    // Gunakan pesan khusus dari server kalau ada, tapi tetap dalam batas wajar
    if (xhr.responseJSON && xhr.responseJSON.message) {
        errorMessage = xhr.responseJSON.message;
    }

    // Tambahkan konteks bila dikirim dari fungsi tertentu
    if (context) {
        errorMessage = `${context} ${errorMessage}`;
    }

    showErrorToast(errorMessage);
};

    // ============================================
    // URL PARAMETER HANDLER
    // ============================================

    /**
     * Cek URL parameter untuk menampilkan toast
     * Contoh: ?message=success&text=Data berhasil disimpan
     */
    $(document).ready(function() {
        const urlParams = new URLSearchParams(window.location.search);
        const messageType = urlParams.get('message');
        const messageText = urlParams.get('text');
        
        if (messageType && messageText) {
            const decodedText = decodeURIComponent(messageText);
            
            switch(messageType) {
                case 'success':
                    showSuccessToast(decodedText);
                    break;
                case 'error':
                    showErrorToast(decodedText);
                    break;
                case 'warning':
                    showWarningToast(decodedText);
                    break;
                case 'info':
                    showInfoToast(decodedText);
                    break;
                case 'session_expired':
                    showWarningToast('Sesi Anda telah berakhir. Silakan login kembali.', 'Sesi Berakhir');
                    break;
            }
            
            // Clean URL (hapus parameter message)
            if (window.history.replaceState) {
                const cleanUrl = window.location.pathname;
                window.history.replaceState({}, document.title, cleanUrl);
            }
        }
    });

    // ============================================
    // SESSION TIMEOUT WARNING
    // ============================================

    /**
     * Warning toast sebelum session timeout
     * Dipanggil 2 menit sebelum timeout
     */
    window.showSessionWarning = function() {
        showWarningToast(
            'Sesi Anda akan berakhir dalam 2 menit. Silakan simpan pekerjaan Anda.',
            'Peringatan Sesi',
            {
                timeOut: 0,
                extendedTimeOut: 0,
                closeButton: true,
                tapToDismiss: false
            }
        );
    };

    console.log('Toastr initialized successfully!');

})(jQuery);