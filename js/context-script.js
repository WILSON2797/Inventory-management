$(document).ready(function() {
    
    // =============================
    // Initialize Select2
    // =============================
    $('.select2').select2({
        placeholder: "Pilih opsi",
        allowClear: true,
        width: '100%'
    });
    
    // =============================
    // Form Submission Handler (DIPERBAIKI - Gabung jadi satu)
    // =============================
    var isSubmitting = false;
    
    $('#contextForm').on('submit', function(e) {
        // Prevent double submit
        if (isSubmitting) {
            e.preventDefault();
            return false;
        }
        
        // Validate before submit
        var warehouse = $('#warehouseSelect').val();
        var project = $('#projectSelect').val();
        
        if (!warehouse || !project) {
            e.preventDefault();
            alert('Silakan pilih Warehouse dan Project terlebih dahulu!');
            return false;
        }
        
        // Set submitting flag and show loading
        isSubmitting = true;
        $('#loadingOverlay').addClass('active');
        
        // Reset flag after 5 seconds (safety mechanism)
        setTimeout(function() {
            isSubmitting = false;
        }, 5000);
        
        // Allow form to submit
        return true;
    });
    
    // =============================
    // Select2 Focus/Blur Animations
    // =============================
    
    // When dropdown opens - highlight label
    $('.select2').on('select2:open', function() {
        $(this).closest('.form-group').find('.form-label').css('color', '#667eea');
    });
    
    // When dropdown closes - restore label color
    $('.select2').on('select2:close', function() {
        $(this).closest('.form-group').find('.form-label').css('color', '#334155');
    });
    
    // =============================
    // Visual Feedback on Selection
    // =============================
    
    // Add visual feedback when option is selected
    $('.select2').on('select2:select', function() {
        var $wrapper = $(this).closest('.input-wrapper');
        var $label = $(this).closest('.form-group').find('.form-label');
        
        // Add success indicator
        $wrapper.addClass('has-value');
        $label.css('color', '#10b981');
        
        // Restore after animation
        setTimeout(function() {
            $wrapper.removeClass('has-value');
            $label.css('color', '#334155');
        }, 500);
    });
    
    // =============================
    // Keyboard Navigation Enhancement
    // =============================
    
    // Tab navigation between selects
    $('#warehouseSelect').on('select2:select', function() {
        setTimeout(function() {
            $('#projectSelect').select2('open');
        }, 300);
    });
    
    // Enter key to submit form
    $('#contextForm').on('keypress', function(e) {
        if (e.which === 13 && !$(e.target).hasClass('select2-search__field')) {
            e.preventDefault();
            $(this).submit();
        }
    });
    
    // =============================
    // Auto-focus Enhancement
    // =============================
    
    // Auto-focus on warehouse select when page loads (optional)
    setTimeout(function() {
        // Uncomment line below if you want auto-open on page load
        // $('#warehouseSelect').select2('open');
    }, 500);
    
    // =============================
    // Button Hover Effects
    // =============================
    
    $('.btn-submit').on('mouseenter', function() {
        $(this).find('i').css('transform', 'translateX(5px)');
    }).on('mouseleave', function() {
        $(this).find('i').css('transform', 'translateX(0)');
    });
    
    // =============================
    // Custom Validation Messages
    // =============================
    
    function showValidationMessage(message, type) {
        var alertClass = type === 'error' ? 'alert-danger' : 'alert-success';
        var alert = $('<div class="alert ' + alertClass + ' alert-dismissible fade show mt-3" role="alert">' +
                     '<i class="fas fa-' + (type === 'error' ? 'exclamation-circle' : 'check-circle') + '"></i> ' +
                     message +
                     '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                     '</div>');
        
        $('.card-body').prepend(alert);
        
        setTimeout(function() {
            alert.fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    }
    
    // =============================
    // Handle Empty Selects
    // =============================
    
    $('.select2').on('select2:clear', function() {
        $(this).closest('.form-group').find('.form-label').css('color', '#334155');
    });
    
    // =============================
    // Responsive Mobile Adjustments
    // =============================
    
    if ($(window).width() < 576) {
        // Adjust select2 dropdown for mobile
        $('.select2').select2({
            placeholder: "Pilih opsi",
            allowClear: true,
            width: '100%',
            dropdownAutoWidth: true
        });
    }
    
    // =============================
    // Console Log for Debugging (only in development)
    // =============================
    
    if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
        console.log('Context Selection Page Loaded Successfully');
        console.log('Select2 Version:', $.fn.select2.defaults);
    }
    
});

/**
 * =============================
 * Additional Helper Functions
 * =============================
 */

// Function to reset form
function resetForm() {
    $('#warehouseSelect').val(null).trigger('change');
    $('#projectSelect').val(null).trigger('change');
    isSubmitting = false; // Reset submit flag
}

// Function to get selected values
function getSelectedValues() {
    return {
        warehouse: $('#warehouseSelect').val(),
        project: $('#projectSelect').val()
    };
}

// Function to check if form is valid
function isFormValid() {
    var values = getSelectedValues();
    return values.warehouse && values.project;
}

// Export functions for external use
window.ContextForm = {
    reset: resetForm,
    getValues: getSelectedValues,
    isValid: isFormValid
};