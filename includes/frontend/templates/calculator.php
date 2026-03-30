<?php
/**
 * Calculator form template
 */

$is_logged_in = is_user_logged_in();
$user_id = get_current_user_id();
$can_save = $is_logged_in && current_user_can('create_nss_sheets');

// Check if this is a dev environment
$is_dev = (strpos(get_site_url(), '.local') !== false) || (defined('WP_DEBUG') && WP_DEBUG);
?>

<div class="nss-calculator-container">

    <!-- ── FORM VIEW ── -->
    <div id="nss-form-view">
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
                        <label for="annual_taxes">Annual Property Taxes ($)</label>
                        <input type="number" id="annual_taxes" name="annual_taxes" step="0.01" min="0" placeholder="e.g. 8000">
                    </div>
                    <div class="nss-form-group">
                        <label for="hoa_fees">HOA Fees</label>
                        <input type="number" id="hoa_fees" name="hoa_fees" step="0.01">
                    </div>
                </div>

                <div class="nss-form-row">
                    <div class="nss-form-group">
                        <label for="agent_commission_percentage">Agent Commission (%)</label>
                        <input type="number" id="agent_commission_percentage" name="agent_commission_percentage"
                               step="0.01" min="0" max="100" placeholder="e.g. 4">
                    </div>
                    <div class="nss-form-group">
                        <label for="closing_date">Closing Date</label>
                        <input type="date" id="closing_date" name="closing_date">
                    </div>
                </div>
            </div>

            <div class="nss-form-actions">
                <?php if ($is_dev): ?>
                    <button type="button" id="nss-fill-test-data" class="nss-button" style="background:#9c27b0;color:#fff;">Fill Test Data</button>
                <?php endif; ?>
                <button type="button" id="nss-calculate-btn" class="nss-button nss-button-primary">Calculate Net Proceeds</button>
                <button type="reset" class="nss-button nss-button-secondary">Clear</button>
            </div>
        </form>

        <?php if ($is_dev): ?>
        <script>
        jQuery(document).ready(function($) {
            $('#nss-fill-test-data').on('click', function() {
                var closingDate = new Date();
                closingDate.setDate(closingDate.getDate() + 30);
                var dateStr = closingDate.toISOString().split('T')[0];

                $('#property_address').val('123 Main Street');
                $('#property_city').val('Columbus');
                $('#property_state').val('OH');
                $('#property_county').val('Franklin');
                $('#property_zip').val('43215');
                $('#sales_price').val('350000');
                $('input[name="loan_payoff_1"]').val('180000');
                $('input[name="loan_payoff_2"]').val('25000');
                $('input[name="loan_payoff_3"]').val('');
                $('#annual_taxes').val('5200');
                $('#hoa_fees').val('250');
                $('#agent_commission_percentage').val('4');
                $('#closing_date').val(dateStr);
            });
        });
        </script>
        <?php endif; ?>

        <div id="nss-loading" class="nss-loading" style="display:none;">
            <p>Calculating&hellip;</p>
        </div>

        <div id="nss-error" class="nss-error" style="display:none;"></div>
    </div><!-- /#nss-form-view -->

    <!-- ── SHEET PREVIEW ── -->
    <div id="nss-sheet-preview" style="display:none;"></div>

</div>
