<?php
/**
 * Calculator form template
 */

$is_logged_in = is_user_logged_in();
$user_id = get_current_user_id();
$user_meta = $is_logged_in ? get_userdata($user_id) : null;
$company_name = $is_logged_in ? get_user_meta($user_id, 'nss_company', true) : '';
$can_save = $is_logged_in && current_user_can('create_nss_sheets');

// Check if this is a dev environment
$is_dev = (strpos(get_site_url(), '.local') !== false) || (defined('WP_DEBUG') && WP_DEBUG);
?>

<div class="nss-calculator-container">
    <div class="nss-calculator-header">
        <h2>Net Proceeds Calculator</h2>
        <p>Calculate your net proceeds from a real estate sale</p>
    </div>

    <form id="nss-calculator-form" class="nss-form">
        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('nss_frontend_nonce'); ?>">

        <div class="nss-form-section">
            <h3>Property Information</h3>

            <div class="nss-form-group">
                <label for="property_address">Property Address</label>
                <input type="text" id="property_address" name="property_address" required>
            </div>

            <div class="nss-form-row">
                <div class="nss-form-group">
                    <label for="property_city">City</label>
                    <input type="text" id="property_city" name="property_city">
                </div>
                <div class="nss-form-group">
                    <label for="property_state">State</label>
                    <input type="text" id="property_state" name="property_state">
                </div>
                <div class="nss-form-group">
                    <label for="property_county">County</label>
                    <input type="text" id="property_county" name="property_county" required>
                </div>
                <div class="nss-form-group">
                    <label for="property_zip">ZIP Code</label>
                    <input type="text" id="property_zip" name="property_zip">
                </div>
            </div>
        </div>

        <div class="nss-form-section">
            <h3>Financial Information</h3>

            <div class="nss-form-group">
                <label for="sales_price">Sales Price</label>
                <input type="number" id="sales_price" name="sales_price" step="0.01" required>
            </div>

            <div class="nss-form-group">
                <label>Loan Payoffs</label>
                <div class="nss-loan-payoffs">
                    <input type="number" name="loan_payoff_1" placeholder="Loan 1" step="0.01">
                    <input type="number" name="loan_payoff_2" placeholder="Loan 2" step="0.01">
                    <input type="number" name="loan_payoff_3" placeholder="Loan 3" step="0.01">
                </div>
            </div>

            <div class="nss-form-row">
                <div class="nss-form-group">
                    <label for="wire_fee">Wire Fee</label>
                    <input type="number" id="wire_fee" name="wire_fee" step="0.01">
                </div>
                <div class="nss-form-group">
                    <label for="hoa_fees">HOA Fees</label>
                    <input type="number" id="hoa_fees" name="hoa_fees" step="0.01">
                </div>
            </div>

            <div class="nss-form-row">
                <div class="nss-form-group">
                    <label for="closing_date">Closing Date</label>
                    <input type="date" id="closing_date" name="closing_date">
                </div>
                <div class="nss-form-group">
                    <label for="owner_policy">
                        <input type="checkbox" id="owner_policy" name="owner_policy">
                        Owner's Policy
                    </label>
                </div>
            </div>
        </div>

        <div class="nss-form-actions">
            <?php if ($is_dev): ?>
                <button type="button" id="nss-fill-test-data" class="nss-button" style="background:#9c27b0;color:#fff;">Fill Test Data</button>
            <?php endif; ?>
            <button type="button" id="nss-calculate-btn" class="nss-button nss-button-primary">Calculate</button>
            <button type="reset" class="nss-button nss-button-secondary">Clear</button>
            <?php if ($can_save): ?>
                <button type="button" id="nss-save-btn" class="nss-button nss-button-secondary" style="display:none;">Save Sheet</button>
                <button type="button" id="nss-download-pdf-btn" class="nss-button nss-button-secondary" style="display:none;">Download PDF</button>
            <?php endif; ?>
        </div>
    </form>

    <?php if ($is_dev): ?>
    <script>
    jQuery(document).ready(function($) {
        $('#nss-fill-test-data').on('click', function() {
            // Generate a closing date 30 days from now
            var closingDate = new Date();
            closingDate.setDate(closingDate.getDate() + 30);
            var dateStr = closingDate.toISOString().split('T')[0];

            // Fill test data
            $('#property_address').val('123 Main Street');
            $('#property_city').val('Columbus');
            $('#property_state').val('OH');
            $('#property_county').val('Franklin');
            $('#property_zip').val('43215');
            $('#sales_price').val('350000');
            $('input[name="loan_payoff_1"]').val('180000');
            $('input[name="loan_payoff_2"]').val('25000');
            $('input[name="loan_payoff_3"]').val('');
            $('#wire_fee').val('35');
            $('#hoa_fees').val('250');
            $('#closing_date').val(dateStr);
            $('#owner_policy').prop('checked', true);
        });
    });
    </script>
    <?php endif; ?>

    <?php if (!$is_logged_in): ?>
    <div class="nss-guest-notice" id="nss-guest-notice" style="display:none;">
        <p>Want to save your calculations and download PDFs?
            <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>">Log in</a> or
            <a href="<?php echo esc_url(wp_registration_url()); ?>">create an account</a>.
        </p>
    </div>
    <?php endif; ?>

    <div id="nss-results" class="nss-results" style="display:none;">
        <h3>Net Proceeds Summary</h3>

        <div class="nss-results-grid">
            <div class="nss-result-item">
                <label>Sales Price</label>
                <span id="result-sales-price" class="nss-result-value">$0.00</span>
            </div>

            <div class="nss-result-section">
                <h4>Deductions</h4>
                <div id="result-deductions"></div>
                <div class="nss-result-item total">
                    <label>Total Deductions</label>
                    <span id="result-total-deductions" class="nss-result-value">$0.00</span>
                </div>
            </div>

            <div class="nss-result-item highlight">
                <label>Net Proceeds</label>
                <span id="result-net-proceeds" class="nss-result-value">$0.00</span>
            </div>
        </div>
    </div>

    <div id="nss-loading" class="nss-loading" style="display:none;">
        <p>Calculating...</p>
    </div>

    <div id="nss-error" class="nss-error" style="display:none;"></div>
</div>
