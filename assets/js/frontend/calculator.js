/**
 * Net Seller Sheet Frontend Calculator
 *
 * Handles form submission, AJAX calculation, and the sheet preview.
 */

(function($) {
    'use strict';

    // Sheet ID stored after a save (so PDF download doesn't re-save)
    var currentSheetId = null;
    // Last successful results (for PDF re-download from preview)
    var lastFormData = null;

    $(document).ready(function() {
        initCalculator();
        initMySheets();
        initSheetDetail();
    });

    // ── Calculator form ────────────────────────────────────────────────────────

    function initCalculator() {
        if (!$('#nss-calculator-form').length) return;

        $(document).on('click', '#nss-calculate-btn', function(e) {
            e.preventDefault();

            var form     = $('#nss-calculator-form');
            var formData = {};
            $.each(form.serializeArray(), function(i, f) { formData[f.name] = f.value; });

            if (!formData.property_address || !formData.property_county || !formData.sales_price) {
                showError('Please fill in all required fields (Address, County, Sales Price).');
                return;
            }

            lastFormData = formData;
            currentSheetId = null; // reset so a new save happens if needed

            calculateNet(formData);
        });

        // Back / Edit buttons (delegated — rendered dynamically)
        $(document).on('click', '#nss-back-btn, #nss-back-btn-bottom', function() {
            showFormView();
        });

        // Download PDF button in preview
        $(document).on('click', '#nss-preview-pdf-btn', function() {
            downloadOrSaveAndDownload();
        });
    }

    // ── My Sheets list ────────────────────────────────────────────────────────

    function initMySheets() {
        $(document).on('click', '.nss-delete-sheet', function(e) {
            e.preventDefault();
            if (!confirm('Are you sure you want to delete this sheet?')) return;
            deleteSheet($(this).data('sheet-id'));
        });
    }

    // ── Sheet detail view ─────────────────────────────────────────────────────

    function initSheetDetail() {
        $(document).on('click', '.nss-download-pdf', function(e) {
            e.preventDefault();
            downloadPDF($(this).data('sheet-id'));
        });
    }

    // ── AJAX: Calculate ───────────────────────────────────────────────────────

    function calculateNet(data) {
        showLoading();
        hideError();

        $.ajax({
            url:  nssData.ajax_url,
            type: 'POST',
            data: {
                action:     'nss_calculate',
                nonce:      data.nonce,
                sheet_data: JSON.stringify(data),
            },
            success: function(response) {
                hideLoading();
                if (response.success) {
                    showSheetPreview(data, response.data);
                } else {
                    showError(response.data.message || 'Calculation error.');
                }
            },
            error: function(xhr, status, error) {
                hideLoading();
                showError('Request failed: ' + error);
            },
        });
    }

    // ── AJAX: Save sheet ──────────────────────────────────────────────────────

    function saveSheet(onSuccess, onError) {
        if (currentSheetId) {
            onSuccess(currentSheetId);
            return;
        }

        $.ajax({
            url:  nssData.ajax_url,
            type: 'POST',
            data: {
                action:     'nss_save_sheet',
                nonce:      nssData.nonce,
                sheet_data: JSON.stringify(lastFormData),
            },
            success: function(response) {
                if (response.success) {
                    currentSheetId = response.data.id;
                    onSuccess(currentSheetId);
                } else {
                    var msg = (response.data && response.data.message) ? response.data.message : 'Error saving sheet.';
                    showPreviewError(msg);
                    if (onError) onError(msg);
                }
            },
            error: function(xhr) {
                var msg = 'Error saving sheet.';
                if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    msg = xhr.responseJSON.data.message;
                } else if (xhr.responseText) {
                    msg = 'Save failed (status ' + xhr.status + '). Please try again.';
                }
                showPreviewError(msg);
                if (onError) onError(msg);
            },
        });
    }

    function downloadOrSaveAndDownload() {
        var $btn = $('#nss-preview-pdf-btn');
        $btn.prop('disabled', true).text('Saving\u2026');

        saveSheet(
            function(id) {
                $btn.prop('disabled', false).text('Download PDF');
                downloadPDF(id);
            },
            function() {
                $btn.prop('disabled', false).text('Download PDF');
            }
        );
    }

    // ── AJAX: Download PDF ────────────────────────────────────────────────────

    function downloadPDF(sheetId) {
        if (!sheetId) {
            alert('Please save the sheet first before downloading the PDF.');
            return;
        }
        window.open(
            nssData.ajax_url + '?action=nss_download_pdf&sheet_id=' + sheetId + '&nonce=' + nssData.nonce,
            '_blank'
        );
    }

    // ── AJAX: Delete sheet ────────────────────────────────────────────────────

    function deleteSheet(sheetId) {
        $.ajax({
            url:  nssData.ajax_url,
            type: 'POST',
            data: { action: 'nss_delete_sheet', sheet_id: sheetId, nonce: nssData.nonce },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    showError('Error deleting sheet.');
                }
            },
            error: function() { showError('Error deleting sheet.'); },
        });
    }

    // ── View transitions ──────────────────────────────────────────────────────

    function showFormView() {
        $('#nss-sheet-preview').hide().empty();
        $('#nss-form-view').show();
        $('html, body').animate({ scrollTop: $('#nss-form-view').offset().top - 40 }, 300);
    }

    // ── Sheet preview renderer ────────────────────────────────────────────────

    function showSheetPreview(formData, r) {
        var html = buildPreviewHtml(formData, r);
        $('#nss-sheet-preview').html(html).show();
        $('#nss-form-view').hide();
        $('html, body').animate({ scrollTop: $('#nss-sheet-preview').offset().top - 40 }, 300);
    }

    function buildPreviewHtml(fd, r) {
        var closingDate  = fd.closing_date ? formatDateMDY(fd.closing_date) : '\u2014';
        var jan1Label    = fd.closing_date ? '01/01/' + fd.closing_date.substring(0, 4) : '';
        var closeLabel   = fd.closing_date ? formatDateMDY(fd.closing_date) : '';

        var addressLine = escHtml(fd.property_address || '');
        var cityParts   = [];
        if (fd.property_city)  cityParts.push(escHtml(fd.property_city));
        if (fd.property_state) cityParts[cityParts.length - 1] += ', ' + escHtml(fd.property_state);
        if (fd.property_zip)   cityParts.push(escHtml(fd.property_zip));
        var cityLine    = cityParts.join(' ');
        var countyText  = fd.property_county ? escHtml(fd.property_county) + ' County' : '';

        // Commission rate label
        var commRate = parseFloat(r.commission.rate) || 0;
        var commLabel = commRate > 0
            ? 'Real Estate Broker Fee <small style="color:#aac;">(' + commRate.toFixed(1) + '%)</small>'
            : 'Real Estate Broker Fee';

        // Proration date range label
        var prorationDates = (jan1Label && closeLabel)
            ? ' <small style="color:#aac;">' + jan1Label + ' &ndash; ' + closeLabel + '</small>'
            : '';

        // Payoff rows
        var payoffRows = '';
        var anyPayoff  = false;
        var payoffs = [
            { key: r.loan_payoffs.payoff_1, label: 'Mortgage #1' },
            { key: r.loan_payoffs.payoff_2, label: 'Mortgage #2' },
            { key: r.loan_payoffs.payoff_3, label: 'Mortgage #3' },
        ];
        $.each(payoffs, function(i, p) {
            if (parseFloat(p.key) > 0) {
                anyPayoff = true;
                payoffRows += row(p.label, pAmt(p.key), '\u2014');
            }
        });
        if (!anyPayoff) {
            payoffRows = '<tr><td class="nss-pt-desc nss-pt-indent" style="color:#aaa;">Mortgage(s)</td>'
                + '<td class="nss-pt-debit">\u2014</td><td class="nss-pt-credit">\u2014</td></tr>';
        }

        // Optional title-charge rows
        var courierRow  = parseFloat(r.title_fees.courier_fee)       > 0 ? row('Courier Fee',          pAmt(r.title_fees.courier_fee),       '\u2014') : '';
        var deedPrepRow = parseFloat(r.title_fees.deed_prep_fee)      > 0 ? row('Deed Preparation Fee', pAmt(r.title_fees.deed_prep_fee),      '\u2014') : '';
        var wireRow     = parseFloat(r.title_fees.wire_transfer_fee)  > 0 ? row('Wire Transfer Fee',    pAmt(r.title_fees.wire_transfer_fee),  '\u2014') : '';
        var customFeeRows = '';
        if (r.title_fees.custom_fees && r.title_fees.custom_fees.length) {
            $.each(r.title_fees.custom_fees, function(i, f) {
                var label = f.label.replace(/_/g, ' ').replace(/\b\w/g, function(c) { return c.toUpperCase(); });
                customFeeRows += row(label, pAmt(f.amount), '\u2014');
            });
        }

        // HOA section (conditional)
        var hoaSection = '';
        if (parseFloat(fd.hoa_fees) > 0) {
            hoaSection = sectionRow('HomeOwners Association')
                + row('HOA Fees \u2014 ESTIMATE', pAmt(fd.hoa_fees), '\u2014');
        }

        // PDF / action buttons
        var bottomAction = nssData.can_save
            ? '<button type="button" class="nss-button nss-button-primary" id="nss-preview-pdf-btn">Download PDF</button>'
            : '<span class="nss-preview-guest">'
                + 'Want to download PDFs? <a href="' + nssData.login_url + '">Log in</a>'
                + ' or <a href="' + nssData.register_url + '">create an account</a>.'
                + '</span>';

        return ''
            // Top action bar
            + '<div class="nss-preview-topbar">'
            +   '<button type="button" class="nss-button nss-button-secondary" id="nss-back-btn">&#8592; Edit</button>'
            + '</div>'

            // ATG header
            + '<div class="nss-preview-header-bar">'
            +   '<div class="nss-preview-header-inner">'
            +     '<div class="nss-preview-logo-cell">'
            +       '<img src="' + nssData.plugin_url + 'assets/images/atg-logo.jpg" alt="ATG Logo">'
            +     '</div>'
            +     '<div class="nss-preview-title-cell">'
            +       '<div class="nss-preview-title">Affiliates Title Group, LLC</div>'
            +       '<div class="nss-preview-subtitle">Seller Net Sheet &mdash; Estimated Net Proceeds</div>'
            +     '</div>'
            +   '</div>'
            + '</div>'
            + '<div class="nss-preview-accent"></div>'

            // Property info
            + '<div class="nss-preview-property">'
            +   '<span class="nss-preview-closing">Closing Date: <strong>' + closingDate + '</strong></span>'
            +   '<span class="nss-preview-for">For:</span> <strong>' + addressLine + '</strong>'
            +   (cityLine ? '&nbsp; ' + cityLine : '')
            +   '<br><span class="nss-preview-for">&nbsp;</span>' + countyText
            + '</div>'

            // Debit / Credit table
            + '<table class="nss-preview-table">'
            +   '<thead><tr>'
            +     '<th class="nss-pt-desc">Description</th>'
            +     '<th>Debit</th>'
            +     '<th>Credit</th>'
            +   '</tr></thead>'
            +   '<tbody>'

            // Financial
            + sectionRow('Financial')
            + row('Sale Price of the Property',             '\u2014',                           pAmt(r.net_proceeds.sales_price, true))
            + row('Seller\'s Owner\'s Title Policy (OTIRB)', pAmt(r.title_fees.owner_policy_fee), '\u2014')

            // Prorations
            + sectionRow('Prorations / Adjustments')
            + '<tr><td class="nss-pt-desc nss-pt-indent">County Taxes' + prorationDates + '</td>'
            +   '<td class="nss-pt-debit">' + pAmt(r.tax_proration.prorated_amount) + '</td>'
            +   '<td class="nss-pt-credit">\u2014</td></tr>'
            + row('Property Tax Hold (50% of Annual Taxes)', pAmt(r.tax_proration.tax_hold),    '\u2014')

            // Title Charges
            + sectionRow('Title Charges &amp; Escrow / Settlement Charges')
            + row('Closing Fee', pAmt(r.title_fees.closing_fee), '\u2014')
            + courierRow + deedPrepRow + wireRow + customFeeRows

            // Real Estate Charges
            + sectionRow('Real Estate Charges')
            + '<tr><td class="nss-pt-desc nss-pt-indent">' + commLabel + '</td>'
            +   '<td class="nss-pt-debit">' + pAmt(r.commission.amount) + '</td>'
            +   '<td class="nss-pt-credit">\u2014</td></tr>'

            // Government Transfer
            + sectionRow('Government Transfer Charges')
            + row('Deed Transfer Tax / Conveyance Fee', pAmt(r.conveyance_fees.seller_amount), '\u2014')

            // Payoffs
            + sectionRow('Payoffs')
            + payoffRows

            // HOA (conditional)
            + hoaSection

            // Subtotals
            + '<tr class="nss-pt-subtotal">'
            +   '<td class="nss-pt-desc">Subtotals</td>'
            +   '<td style="text-align:right;">' + pAmt(r.total_deductions, true)           + '</td>'
            +   '<td style="text-align:right;">' + pAmt(r.net_proceeds.sales_price, true)   + '</td>'
            + '</tr>'

            // Net Proceeds
            + '<tr class="nss-pt-net">'
            +   '<td class="nss-pt-desc">NET PROCEEDS TO SELLER</td>'
            +   '<td>&nbsp;</td>'
            +   '<td style="text-align:right;">' + pAmt(r.net_proceeds.net_amount, true)    + '</td>'
            + '</tr>'

            +   '</tbody>'
            + '</table>'

            // Disclaimer
            + '<div class="nss-preview-disclaimer">'
            + '* This estimate is an approximation and is not guaranteed. The estimate is based on information provided. '
            + 'Additional charges may occur based on the details of the sale &mdash; mobile notary fees, seller credits, '
            + 'recording fees, courier fees, etc. Final figures will be provided at closing.'
            + '</div>'

            // Bottom action bar
            + '<div class="nss-preview-bottombar">'
            +   '<button type="button" class="nss-button nss-button-secondary" id="nss-back-btn-bottom">&#8592; Edit</button>'
            +   bottomAction
            + '</div>';
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /** Format a currency amount; returns an em-dash for zero/empty unless showZero */
    function pAmt(value, showZero) {
        var f = parseFloat(value) || 0;
        if (f <= 0 && !showZero) return '\u2014';
        return '$ ' + f.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    /** Build a standard indented data row */
    function row(label, debit, credit) {
        return '<tr>'
            + '<td class="nss-pt-desc nss-pt-indent">' + label + '</td>'
            + '<td class="nss-pt-debit">'  + debit  + '</td>'
            + '<td class="nss-pt-credit">' + credit + '</td>'
            + '</tr>';
    }

    /** Build a section header row */
    function sectionRow(label) {
        return '<tr class="nss-pt-section"><td colspan="3">' + label + '</td></tr>';
    }

    /** Convert YYYY-MM-DD to MM/DD/YYYY */
    function formatDateMDY(dateStr) {
        if (!dateStr) return '\u2014';
        var parts = dateStr.split('-');
        if (parts.length !== 3) return dateStr;
        return parts[1] + '/' + parts[2] + '/' + parts[0];
    }

    /** Escape HTML special characters */
    function escHtml(str) {
        return $('<div>').text(String(str)).html();
    }

    function showLoading() {
        $('#nss-loading').show();
    }

    function hideLoading() {
        $('#nss-loading').hide();
    }

    function showError(message) {
        $('#nss-error').text(message).show();
        $('html, body').animate({ scrollTop: $('#nss-error').offset().top - 40 }, 200);
    }

    /** Show an error visible while the preview is active */
    function showPreviewError(message) {
        var $err = $('#nss-preview-error');
        if (!$err.length) {
            $err = $('<div id="nss-preview-error" class="nss-error" style="margin:8px 0;"></div>');
            $('#nss-sheet-preview').prepend($err);
        }
        $err.text(message).show();
        $('html, body').animate({ scrollTop: $err.offset().top - 40 }, 200);
    }

    function hideError() {
        $('#nss-error').hide();
    }

})(jQuery);
