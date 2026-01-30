/**
 * Net Seller Sheet Frontend Calculator
 *
 * Handles form submission, validation, and AJAX communication
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        initCalculator();
        initMySheets();
        initSheetDetail();
    });

    /**
     * Initialize calculator form
     */
    function initCalculator() {
        if (!$('#nss-calculator-form').length) {
            return;
        }

        // Calculate button
        $(document).on('click', '#nss-calculate-btn', function(e) {
            e.preventDefault();

            const form = $('#nss-calculator-form');
            const formData = form.serializeArray();
            const data = {};

            $.each(formData, function(i, field) {
                data[field.name] = field.value;
            });

            // Validate required fields
            if (!data.property_address || !data.property_county || !data.sales_price) {
                showError('Please fill in all required fields');
                return;
            }

            calculateNet(data);
        });

        // Save sheet button
        $(document).on('click', '#nss-save-btn', function(e) {
            e.preventDefault();
            saveSheet();
        });

        // Download PDF button
        $(document).on('click', '#nss-download-pdf-btn', function(e) {
            e.preventDefault();
            downloadPDF();
        });
    }

    /**
     * Initialize my sheets list
     */
    function initMySheets() {
        $(document).on('click', '.nss-delete-sheet', function(e) {
            e.preventDefault();

            if (!confirm('Are you sure you want to delete this sheet?')) {
                return;
            }

            const sheetId = $(this).data('sheet-id');
            deleteSheet(sheetId);
        });

        $(document).on('click', '.nss-view-sheet', function(e) {
            e.preventDefault();
            const sheetId = $(this).data('sheet-id');
            window.location.href = '#'; // Load sheet detail
        });
    }

    /**
     * Initialize sheet detail view
     */
    function initSheetDetail() {
        $(document).on('click', '.nss-download-pdf', function(e) {
            e.preventDefault();
            const sheetId = $(this).data('sheet-id');
            downloadPDF(sheetId);
        });
    }

    /**
     * Calculate net proceeds via AJAX
     */
    function calculateNet(data) {
        showLoading();
        hideError();

        $.ajax({
            url: nssData.ajax_url,
            type: 'POST',
            data: {
                action: 'nss_calculate',
                nonce: data.nonce,
                sheet_data: JSON.stringify(data)
            },
            success: function(response) {
                hideLoading();

                if (response.success) {
                    displayResults(response.data);
                    showSuccessMessage('Calculation complete!');
                    // Show save button only if user can save
                    // PDF button shows after saving
                    if (nssData.can_save) {
                        $('#nss-save-btn').show();
                        $('#nss-download-pdf-btn').hide(); // Hidden until saved
                    }
                    // Show guest notice if not logged in
                    $('#nss-guest-notice').show();
                } else {
                    showError(response.data.message || 'Calculation error');
                }
            },
            error: function(xhr, status, error) {
                hideLoading();
                showError('Error: ' + error);
            }
        });
    }

    /**
     * Display calculation results
     */
    function displayResults(results) {
        const r = results;

        $('#result-sales-price').text(formatCurrency(r.net_proceeds.sales_price));

        let deductionsHtml = '';
        deductionsHtml += '<div class="result-item"><label>Loan Payoffs:</label><span>' + formatCurrency(r.loan_payoffs.total) + '</span></div>';
        deductionsHtml += '<div class="result-item"><label>Conveyance:</label><span>' + formatCurrency(r.conveyance_fees.seller_amount) + '</span></div>';
        deductionsHtml += '<div class="result-item"><label>Tax Proration:</label><span>' + formatCurrency(r.tax_proration.prorated_amount) + '</span></div>';
        deductionsHtml += '<div class="result-item"><label>Commission:</label><span>' + formatCurrency(r.commission.amount) + '</span></div>';
        deductionsHtml += '<div class="result-item"><label>Title Fees:</label><span>' + formatCurrency(r.title_fees.total) + '</span></div>';
        deductionsHtml += '<div class="result-item"><label>Recording Fees:</label><span>' + formatCurrency(r.recording_fees.total) + '</span></div>';

        $('#result-deductions').html(deductionsHtml);
        $('#result-total-deductions').text(formatCurrency(r.total_deductions));
        $('#result-net-proceeds').text(formatCurrency(r.net_proceeds.net_amount));

        $('#nss-results').show();
    }

    // Store the current sheet ID after saving
    var currentSheetId = null;

    /**
     * Save sheet to database
     */
    function saveSheet() {
        const form = $('#nss-calculator-form');
        const formData = form.serializeArray();
        const data = {};

        $.each(formData, function(i, field) {
            data[field.name] = field.value;
        });

        $.ajax({
            url: nssData.ajax_url,
            type: 'POST',
            data: {
                action: 'nss_save_sheet',
                nonce: nssData.nonce,
                sheet_data: JSON.stringify(data)
            },
            success: function(response) {
                if (response.success) {
                    currentSheetId = response.data.id;
                    alert('Sheet saved successfully!');
                    // Enable PDF download now that we have a sheet ID
                    $('#nss-download-pdf-btn').prop('disabled', false).show();
                } else {
                    showError(response.data.message || 'Error saving sheet');
                }
            },
            error: function(xhr) {
                var msg = 'Error saving sheet';
                if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    msg = xhr.responseJSON.data.message;
                }
                showError(msg);
            }
        });
    }

    /**
     * Download PDF
     */
    function downloadPDF(sheetId) {
        var id = sheetId || currentSheetId;
        if (!id) {
            alert('Please save the sheet first before downloading PDF.');
            return;
        }
        const pdfUrl = nssData.ajax_url + '?action=nss_download_pdf&sheet_id=' + id + '&nonce=' + nssData.nonce;
        window.location.href = pdfUrl;
    }

    /**
     * Delete sheet
     */
    function deleteSheet(sheetId) {
        $.ajax({
            url: nssData.ajax_url,
            type: 'POST',
            data: {
                action: 'nss_delete_sheet',
                sheet_id: sheetId,
                nonce: nssData.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    showError('Error deleting sheet');
                }
            },
            error: function() {
                showError('Error deleting sheet');
            }
        });
    }

    /**
     * Format number as currency
     */
    function formatCurrency(value) {
        const num = parseFloat(value);
        return '$' + num.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    /**
     * Show loading message
     */
    function showLoading() {
        $('#nss-loading').show();
        $('#nss-results').hide();
    }

    /**
     * Hide loading message
     */
    function hideLoading() {
        $('#nss-loading').hide();
    }

    /**
     * Show error message
     */
    function showError(message) {
        $('#nss-error').text(message).show();
    }

    /**
     * Hide error message
     */
    function hideError() {
        $('#nss-error').hide();
    }

    /**
     * Show success message
     */
    function showSuccessMessage(message) {
        // Create and show a temporary success message
        const msg = $('<div class="nss-message success">' + message + '</div>');
        $('body').prepend(msg);
        setTimeout(function() {
            msg.fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    }

})(jQuery);
