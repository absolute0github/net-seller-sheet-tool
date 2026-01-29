<?php
/**
 * PDF Template for Net Proceeds Sheet
 *
 * This template generates the HTML that will be converted to PDF
 */

$user_id = $this->sheet_data['user_id'];
$user = get_user_by('id', $user_id);
$company_name = get_user_meta($user_id, 'nss_company', true);
$company_logo = get_user_meta($user_id, 'nss_company_logo_url', true);
$results = $this->sheet->calculate();

?><!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Net Proceeds Sheet</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }

        .header {
            border-bottom: 3px solid #0073aa;
            padding-bottom: 20px;
            margin-bottom: 30px;
            position: relative;
        }

        .logo {
            max-width: 150px;
            max-height: 100px;
            margin-bottom: 10px;
        }

        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #0073aa;
            margin-bottom: 5px;
        }

        .document-title {
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            margin: 30px 0 20px 0;
        }

        .section {
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            background: #f0f7ff;
            padding: 8px 12px;
            margin-bottom: 12px;
            border-left: 4px solid #0073aa;
        }

        .property-info {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }

        .info-row {
            display: table-row;
        }

        .info-label {
            display: table-cell;
            width: 30%;
            font-weight: bold;
            padding: 6px;
            border-bottom: 1px solid #eee;
        }

        .info-value {
            display: table-cell;
            width: 70%;
            padding: 6px;
            border-bottom: 1px solid #eee;
        }

        .calculation-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .calculation-table thead {
            background: #f0f7ff;
        }

        .calculation-table th {
            padding: 10px;
            text-align: left;
            font-weight: bold;
            border-bottom: 2px solid #0073aa;
        }

        .calculation-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #ddd;
        }

        .calculation-table tr:last-child td {
            border-bottom: none;
        }

        .amount {
            text-align: right;
            font-weight: bold;
            color: #0073aa;
        }

        .section-total {
            background: #f9f9f9;
            font-weight: bold;
        }

        .grand-total {
            background: #f0f7ff;
            font-weight: bold;
            font-size: 14px;
        }

        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #999;
            text-align: center;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <?php if ($company_logo): ?>
            <img src="<?php echo esc_attr($company_logo); ?>" alt="Logo" class="logo">
        <?php endif; ?>
        <?php if ($company_name): ?>
            <div class="company-name"><?php echo esc_html($company_name); ?></div>
        <?php endif; ?>
        <div style="color: #999; font-size: 12px;">
            <?php echo esc_html($user->user_email); ?>
        </div>
    </div>

    <!-- Document Title -->
    <div class="document-title">NET PROCEEDS CALCULATION SHEET</div>

    <!-- Property Information Section -->
    <div class="section">
        <div class="section-title">Property Information</div>
        <div class="property-info">
            <div class="info-row">
                <div class="info-label">Property Address:</div>
                <div class="info-value"><?php echo esc_html($this->sheet_data['property_address']); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">City, State, ZIP:</div>
                <div class="info-value"><?php echo esc_html($this->sheet_data['property_city'] . ', ' . $this->sheet_data['property_state'] . ' ' . $this->sheet_data['property_zip']); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">County:</div>
                <div class="info-value"><?php echo esc_html($this->sheet_data['property_county']); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Closing Date:</div>
                <div class="info-value"><?php echo NSS_Formatting::date($this->sheet_data['closing_date']); ?></div>
            </div>
        </div>
    </div>

    <!-- Calculation Summary -->
    <div class="section">
        <div class="section-title">Calculation Summary</div>
        <table class="calculation-table">
            <tbody>
                <!-- Sales Price -->
                <tr>
                    <td><strong>Sales Price:</strong></td>
                    <td class="amount"><?php echo NSS_Formatting::currency($results['net_proceeds']['sales_price']); ?></td>
                </tr>

                <!-- Loan Payoffs -->
                <tr class="section-header">
                    <td colspan="2"><strong>Deductions</strong></td>
                </tr>
                <tr>
                    <td style="padding-left: 20px;">Loan Payoff #1:</td>
                    <td class="amount"><?php echo NSS_Formatting::currency($results['loan_payoffs']['payoff_1']); ?></td>
                </tr>
                <tr>
                    <td style="padding-left: 20px;">Loan Payoff #2:</td>
                    <td class="amount"><?php echo NSS_Formatting::currency($results['loan_payoffs']['payoff_2']); ?></td>
                </tr>
                <tr>
                    <td style="padding-left: 20px;">Loan Payoff #3:</td>
                    <td class="amount"><?php echo NSS_Formatting::currency($results['loan_payoffs']['payoff_3']); ?></td>
                </tr>
                <tr>
                    <td style="padding-left: 20px;">Wire Fee:</td>
                    <td class="amount"><?php echo NSS_Formatting::currency($results['loan_payoffs']['wire_fee']); ?></td>
                </tr>
                <tr class="section-total">
                    <td style="padding-left: 20px;">Total Loan Costs:</td>
                    <td class="amount"><?php echo NSS_Formatting::currency($results['loan_payoffs']['total']); ?></td>
                </tr>

                <!-- Conveyance Fees -->
                <tr>
                    <td style="padding-left: 20px;">Conveyance Fees:</td>
                    <td class="amount"><?php echo NSS_Formatting::currency($results['conveyance_fees']['seller_amount']); ?></td>
                </tr>

                <!-- Tax Proration -->
                <tr>
                    <td style="padding-left: 20px;">Property Tax Proration:</td>
                    <td class="amount"><?php echo NSS_Formatting::currency($results['tax_proration']['prorated_amount']); ?></td>
                </tr>

                <!-- Commission -->
                <tr>
                    <td style="padding-left: 20px;">Commission (<?php echo esc_html($results['commission']['tier_name']); ?>):</td>
                    <td class="amount"><?php echo NSS_Formatting::currency($results['commission']['amount']); ?></td>
                </tr>

                <!-- Title Fees -->
                <tr>
                    <td style="padding-left: 20px;">Title Closing Fee:</td>
                    <td class="amount"><?php echo NSS_Formatting::currency($results['title_fees']['closing_fee']); ?></td>
                </tr>
                <tr>
                    <td style="padding-left: 20px;">Title Exam Fee:</td>
                    <td class="amount"><?php echo NSS_Formatting::currency($results['title_fees']['exam_fee']); ?></td>
                </tr>
                <tr>
                    <td style="padding-left: 20px;">Courier Fee:</td>
                    <td class="amount"><?php echo NSS_Formatting::currency($results['title_fees']['courier_fee']); ?></td>
                </tr>
                <tr>
                    <td style="padding-left: 20px;">Deed Preparation Fee:</td>
                    <td class="amount"><?php echo NSS_Formatting::currency($results['title_fees']['deed_prep_fee']); ?></td>
                </tr>
                <?php if ($results['title_fees']['owner_policy_fee'] != '0.00'): ?>
                    <tr>
                        <td style="padding-left: 20px;">Owner's Policy:</td>
                        <td class="amount"><?php echo NSS_Formatting::currency($results['title_fees']['owner_policy_fee']); ?></td>
                    </tr>
                <?php endif; ?>

                <!-- Recording Fees -->
                <tr>
                    <td style="padding-left: 20px;">Recording Fees:</td>
                    <td class="amount"><?php echo NSS_Formatting::currency($results['recording_fees']['total']); ?></td>
                </tr>

                <!-- Total Deductions -->
                <tr class="grand-total">
                    <td><strong>Total Deductions:</strong></td>
                    <td class="amount"><strong><?php echo NSS_Formatting::currency($results['total_deductions']); ?></strong></td>
                </tr>

                <!-- Net Proceeds -->
                <tr class="grand-total" style="font-size: 16px; height: 40px;">
                    <td><strong>NET PROCEEDS:</strong></td>
                    <td class="amount" style="font-size: 16px;"><strong><?php echo NSS_Formatting::currency($results['net_proceeds']['net_amount']); ?></strong></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>This document was generated by <?php echo esc_html(get_bloginfo('name')); ?> on <?php echo date('M d, Y \a\t g:i A'); ?></p>
    </div>
</body>
</html>
