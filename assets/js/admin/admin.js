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
     * Show admin notice
     *
     * @param {string} message
     * @param {string} type - 'success', 'error', 'warning', 'info'
     */
    function showNotice(message, type) {
        type = type || 'success';
        const $notice = $('<div class="notice notice-' + type + ' is-dismissible nss-notice"><p>' + message + '</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss</span></button></div>');

        // Remove existing notices
        $('.nss-notice').remove();

        // Add notice after the page title
        $('.wrap h1').first().after($notice);

        // Scroll to top to see notice
        $('html, body').animate({ scrollTop: 0 }, 200);

        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);

        // Manual dismiss
        $notice.on('click', '.notice-dismiss', function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        });
    }

    /**
     * Initialize fee management handlers
     */
    function initFeeHandlers() {
        const $modal = $('#nss-fee-modal');
        const $form = $('#nss-fee-form');

        // Toggle custom fee name input when "Other..." is selected
        $(document).on('change', '#static_fee_type', function() {
            if ($(this).val() === 'other') {
                $('#custom_fee_type_row').show();
                $('#custom_fee_type').prop('required', true);
            } else {
                $('#custom_fee_type_row').hide();
                $('#custom_fee_type').prop('required', false).val('');
            }
        });

        // Open modal for adding new fee
        $(document).on('click', '#nss-add-fee-btn', function(e) {
            e.preventDefault();
            $('#nss-modal-title').text('Add New Fee');
            $('#nss-fee-id').val('');
            $form[0].reset();
            // Ensure is_active is checked for new fees
            $('#is_active').prop('checked', true);
            // Reset seller_pays_percentage to default
            $('#seller_pays_percentage').val('1.0');
            // Hide custom fee type row
            $('#custom_fee_type_row').hide();
            $('#custom_fee_type').prop('required', false);
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
                    $modal.hide();
                    if (response.success) {
                        showNotice(response.data.message, 'success');
                        // Reload after short delay so user sees the notice
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        let errorMsg = '';
                        if (typeof response.data === 'object') {
                            errorMsg = Object.values(response.data).join(', ');
                        } else {
                            errorMsg = response.data;
                        }
                        showNotice(errorMsg, 'error');
                    }
                },
                error: function() {
                    $modal.hide();
                    showNotice('Request failed. Please try again.', 'error');
                }
            });
        });

        // Edit fee - open modal with data
        $(document).on('click', '.nss-edit-fee', function(e) {
            e.preventDefault();
            const feeId = $(this).data('fee-id');
            const feeData = $(this).data('fee');

            $('#nss-modal-title').text('Edit Fee');
            $('#nss-fee-id').val(feeId);

            // Reset form first
            $form[0].reset();

            // Populate form fields from fee data
            if (feeData) {
                // Common fields
                if (feeData.county_name) $('#county_name').val(feeData.county_name);
                if (feeData.state) $('#state').val(feeData.state);

                // Conveyance fields
                if (feeData.fee_percentage !== undefined) $('#fee_percentage').val(feeData.fee_percentage);
                if (feeData.flat_fee !== undefined) $('#flat_fee').val(feeData.flat_fee);
                if (feeData.seller_pays_percentage !== undefined) $('#seller_pays_percentage').val(feeData.seller_pays_percentage);

                // Tax rate fields
                if (feeData.tax_rate !== undefined) $('#tax_rate').val(feeData.tax_rate);
                if (feeData.tax_type) $('#tax_type').val(feeData.tax_type);

                // Property value fields
                if (feeData.tier_name) $('#tier_name').val(feeData.tier_name);
                if (feeData.min_price !== undefined) $('#min_price').val(feeData.min_price);
                if (feeData.max_price !== undefined) $('#max_price').val(feeData.max_price);
                if (feeData.rate !== undefined) $('#rate').val(feeData.rate);

                // Title closing/exam and static fee fields
                if (feeData.fee_amount !== undefined) $('#fee_amount').val(feeData.fee_amount);
                if (feeData.fee_type) {
                    // Check if the fee_type exists as an option in the dropdown (includes dynamic custom types)
                    var $select = $('#static_fee_type');
                    if ($select.find('option[value="' + feeData.fee_type + '"]').length) {
                        $select.val(feeData.fee_type);
                        $('#custom_fee_type_row').hide();
                        $('#custom_fee_type').prop('required', false);
                    } else {
                        $select.val('other');
                        $('#custom_fee_type').val(feeData.fee_type);
                        $('#custom_fee_type_row').show();
                        $('#custom_fee_type').prop('required', true);
                    }
                }

                // Active status
                $('#is_active').prop('checked', feeData.is_active == 1);
            }

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
                        showNotice(response.data.message, 'success');
                    } else {
                        showNotice(response.data, 'error');
                    }
                },
                error: function() {
                    showNotice('Request failed. Please try again.', 'error');
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
                        showNotice(response.data.message, 'success');
                        // Reload after short delay
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        showNotice(response.data, 'error');
                    }
                },
                error: function() {
                    showNotice('Request failed. Please try again.', 'error');
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
