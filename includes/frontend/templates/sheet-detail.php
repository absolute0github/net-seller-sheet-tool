<?php
/**
 * Sheet detail view template
 */

$data = $sheet->get_data();
$results = $sheet->calculate();
?>

<div class="nss-sheet-detail-container">
    <div class="nss-sheet-header">
        <h2><?php echo esc_html($data['property_address']); ?></h2>
        <p class="nss-sheet-meta">
            County: <?php echo esc_html($data['property_county']); ?> |
            Created: <?php echo NSS_Formatting::date($data['created_at']); ?>
        </p>
    </div>

    <div class="nss-sheet-content">
        <div class="nss-section">
            <h3>Property Information</h3>
            <dl class="nss-definition-list">
                <dt>Address:</dt>
                <dd><?php echo esc_html($data['property_address']); ?></dd>
                <dt>County:</dt>
                <dd><?php echo esc_html($data['property_county']); ?></dd>
                <dt>Sales Price:</dt>
                <dd><?php echo NSS_Formatting::currency($data['sales_price']); ?></dd>
            </dl>
        </div>

        <div class="nss-section">
            <h3>Calculation Summary</h3>
            <table class="nss-results-table">
                <tbody>
                    <tr>
                        <td>Sales Price</td>
                        <td class="nss-amount"><?php echo NSS_Formatting::currency($results['net_proceeds']['sales_price']); ?></td>
                    </tr>
                    <tr class="section-header">
                        <td colspan="2">Deductions</td>
                    </tr>
                    <tr>
                        <td>Loan Payoffs</td>
                        <td class="nss-amount"><?php echo NSS_Formatting::currency($results['loan_payoffs']['total']); ?></td>
                    </tr>
                    <tr>
                        <td>Conveyance Fees</td>
                        <td class="nss-amount"><?php echo NSS_Formatting::currency($results['conveyance_fees']['seller_amount']); ?></td>
                    </tr>
                    <tr>
                        <td>Tax Proration</td>
                        <td class="nss-amount"><?php echo NSS_Formatting::currency($results['tax_proration']['prorated_amount']); ?></td>
                    </tr>
                    <tr>
                        <td>Commission</td>
                        <td class="nss-amount"><?php echo NSS_Formatting::currency($results['commission']['amount']); ?></td>
                    </tr>
                    <tr>
                        <td>Title Fees</td>
                        <td class="nss-amount"><?php echo NSS_Formatting::currency($results['title_fees']['total']); ?></td>
                    </tr>
                    <tr>
                        <td>Recording Fees</td>
                        <td class="nss-amount"><?php echo NSS_Formatting::currency($results['recording_fees']['total']); ?></td>
                    </tr>
                    <tr class="total-row">
                        <td>Total Deductions</td>
                        <td class="nss-amount"><?php echo NSS_Formatting::currency($results['total_deductions']); ?></td>
                    </tr>
                    <tr class="net-proceeds-row">
                        <td><strong>Net Proceeds</strong></td>
                        <td class="nss-amount"><strong><?php echo NSS_Formatting::currency($results['net_proceeds']['net_amount']); ?></strong></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="nss-sheet-actions">
            <a href="#" class="nss-button nss-button-primary nss-edit-sheet" data-sheet-id="<?php echo esc_attr($sheet_id); ?>">Edit Sheet</a>
            <a href="#" class="nss-button nss-button-secondary nss-download-pdf" data-sheet-id="<?php echo esc_attr($sheet_id); ?>">Download PDF</a>
            <a href="<?php echo do_shortcode('[nss_my_sheets]'); ?>" class="nss-button nss-button-secondary">Back to Sheets</a>
        </div>
    </div>
</div>
