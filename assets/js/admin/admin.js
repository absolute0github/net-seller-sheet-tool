/**
 * Net Seller Sheet Admin JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        initFeeHandlers();
        initSheetHandlers();
        initStats();
    });

    /**
     * Initialize fee management handlers
     */
    function initFeeHandlers() {
        // Delete fee
        $(document).on('click', '.nss-delete-fee', function(e) {
            e.preventDefault();

            if (!confirm('Are you sure you want to delete this fee?')) {
                return;
            }

            const feeId = $(this).data('fee-id');
            const feeType = $(this).data('fee-type');
            const btn = $(this);

            $.ajax({
                url: nssAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'nss_delete_fee',
                    fee_id: feeId,
                    fee_type: feeType,
                    _nonce: nssAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        btn.closest('tr').fadeOut(function() {
                            $(this).remove();
                        });
                        alert(response.data.message);
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                error: function() {
                    alert('AJAX request failed');
                }
            });
        });

        // Toggle fee status
        $(document).on('click', '.nss-toggle-fee', function(e) {
            e.preventDefault();

            const feeId = $(this).data('fee-id');
            const feeType = $(this).data('fee-type');
            const btn = $(this);

            $.ajax({
                url: nssAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'nss_toggle_fee_status',
                    fee_id: feeId,
                    fee_type: feeType,
                    _nonce: nssAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Toggle button text
                        const newText = btn.text() === 'Activate' ? 'Deactivate' : 'Activate';
                        btn.text(newText);
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                error: function() {
                    alert('AJAX request failed');
                }
            });
        });
    }

    /**
     * Initialize sheet handlers
     */
    function initSheetHandlers() {
        $(document).on('click', '.nss-delete-sheet', function(e) {
            e.preventDefault();

            if (!confirm('Are you sure you want to delete this sheet?')) {
                return;
            }

            const sheetId = $(this).data('sheet-id');
            // Delete logic here
        });
    }

    /**
     * Load and display statistics
     */
    function initStats() {
        // Load stats for dashboard
        if ($('.nss-dashboard-stats').length) {
            $.ajax({
                url: nssAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'nss_get_stats',
                    _nonce: nssAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Update stats if needed
                    }
                }
            });
        }
    }

})(jQuery);
