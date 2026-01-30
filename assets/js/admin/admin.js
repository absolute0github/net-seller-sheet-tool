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
        const $modal = $('#nss-fee-modal');
        const $form = $('#nss-fee-form');

        // Open modal for adding new fee
        $(document).on('click', '#nss-add-fee-btn', function(e) {
            e.preventDefault();
            $('#nss-modal-title').text('Add New Fee');
            $('#nss-fee-id').val('');
            $form[0].reset();
            $modal.show();
        });

        // Close modal
        $(document).on('click', '.nss-modal-close, .nss-modal-cancel', function(e) {
            e.preventDefault();
            $modal.hide();
        });

        // Close modal on outside click
        $(window).on('click', function(e) {
            if ($(e.target).is($modal)) {
                $modal.hide();
            }
        });

        // Submit fee form
        $form.on('submit', function(e) {
            e.preventDefault();

            const feeId = $('#nss-fee-id').val();
            const action = feeId ? 'nss_update_fee' : 'nss_create_fee';
            const formData = $form.serialize();

            $.ajax({
                url: nssAdmin.ajax_url,
                type: 'POST',
                data: formData + '&action=' + action + '&_nonce=' + nssAdmin.nonce,
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        let errorMsg = 'Error: ';
                        if (typeof response.data === 'object') {
                            errorMsg += Object.values(response.data).join(', ');
                        } else {
                            errorMsg += response.data;
                        }
                        alert(errorMsg);
                    }
                },
                error: function() {
                    alert('AJAX request failed');
                }
            });
        });

        // Edit fee - open modal with data
        $(document).on('click', '.nss-edit-fee', function(e) {
            e.preventDefault();
            const $row = $(this).closest('tr');
            const feeId = $(this).data('fee-id');

            $('#nss-modal-title').text('Edit Fee');
            $('#nss-fee-id').val(feeId);

            // Note: For full edit support, you'd need to fetch fee data via AJAX
            // For now, this opens the modal for the user to re-enter data
            $modal.show();
        });

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
