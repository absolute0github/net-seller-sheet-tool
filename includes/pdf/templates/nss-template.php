<?php
/**
 * PDF Template for Net Proceeds Sheet
 */

$results    = $this->sheet->calculate();
$sheet_data = $this->sheet_data;

$closing_date = !empty($sheet_data['closing_date'])
    ? date('m/d/Y', strtotime($sheet_data['closing_date']))
    : '—';

$tax_info = $sheet_data['tax_info'] ?? [];
if (is_string($tax_info)) {
    $tax_info = json_decode($tax_info, true) ?? [];
}
$annual_taxes = floatval($tax_info['annual_taxes'] ?? 0);

$close_dt    = !empty($sheet_data['closing_date']) ? new DateTime($sheet_data['closing_date']) : new DateTime();
$jan1_label  = '01/01/' . $close_dt->format('Y');
$close_label = $close_dt->format('m/d/Y');

$atg_logo = NSS_PLUGIN_DIR . 'assets/images/atg-logo.jpg';

function nss_pdf_amount($value, $show_zero = false) {
    $f = floatval($value);
    if ($f <= 0 && !$show_zero) return '&mdash;';
    return '$&nbsp;' . number_format($f, 2);
}
?><!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Seller Net Sheet</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
    font-family: Arial, sans-serif;
    font-size: 9pt;
    color: #222;
    background: #fff;
}

/* ── Header: real table so mPDF renders side-by-side ── */
.header-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 0;
}
.header-logo-td {
    width: 100px;
    background: #ffffff;
    padding: 8px 10px;
    vertical-align: middle;
    border: none;
}
.header-logo-td img {
    width: 82px;
    height: auto;
    display: block;
}
.header-title-td {
    background-color: #003366;
    padding: 10px 16px;
    vertical-align: middle;
    border: none;
}
.header-main-title {
    color: #ffffff;
    font-size: 18pt;
    font-weight: bold;
    letter-spacing: 0.5px;
    line-height: 1.1;
}
.header-sub-title {
    color: #a8c8f0;
    font-size: 9pt;
    margin-top: 3px;
}

/* ── Accent stripe ── */
.accent-bar {
    height: 5px;
    background: linear-gradient(to right, #cc0000, #ffffff, #003399);
    margin-bottom: 8px;
}

/* ── Property info ── */
.property-block {
    margin-bottom: 8px;
    font-size: 9pt;
    overflow: hidden;
}
.property-block .for-label {
    font-weight: bold;
    color: #003366;
}
.property-block .closing-right {
    float: right;
    font-size: 9pt;
    color: #444;
}

/* ── Main table ── */
.calc-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 9pt;
    page-break-inside: avoid;
}
.calc-table th {
    background-color: #003366;
    color: #fff;
    padding: 5px 8px;
    text-align: right;
    font-weight: bold;
    font-size: 9pt;
    border: 1px solid #003366;
}
.calc-table th.col-desc { text-align: left; }
.calc-table td {
    padding: 2px 8px;
    border-bottom: 1px solid #ebebeb;
    vertical-align: middle;
}
.calc-table td.col-desc  { padding-left: 8px; }
.calc-table td.col-debit  { text-align: right; white-space: nowrap; }
.calc-table td.col-credit { text-align: right; white-space: nowrap; }
.calc-table td.indented   { padding-left: 20px; }

/* Section header rows */
.section-header td {
    background-color: #f0f4f9;
    font-weight: bold;
    color: #003366;
    padding: 4px 8px;
    border-top: 1px solid #c0cfe0;
    border-bottom: 1px solid #c0cfe0;
    font-size: 9pt;
}

/* Subtotal row */
.subtotal-row td {
    background-color: #e6edf5;
    font-weight: bold;
    border-top: 2px solid #003366;
    border-bottom: 1px solid #003366;
    padding: 5px 8px;
}

/* Net proceeds row */
.net-row td {
    background-color: #003366;
    color: #ffffff;
    font-weight: bold;
    font-size: 11pt;
    padding: 8px 8px;
    border-top: 3px solid #cc0000;
}

/* Footer */
.disclaimer {
    margin-top: 8px;
    font-size: 7.5pt;
    color: #666;
    font-style: italic;
    border-top: 1px solid #ccc;
    padding-top: 6px;
}
.generated-note {
    text-align: right;
    font-size: 7pt;
    color: #999;
    margin-top: 4px;
}
</style>
</head>
<body>

<!-- Header -->
<table class="header-table" cellspacing="0" cellpadding="0">
    <tr>
        <td class="header-logo-td">
            <img src="<?php echo esc_attr($atg_logo); ?>" alt="ATG Logo">
        </td>
        <td class="header-title-td">
            <div class="header-main-title">Affiliates Title Group, LLC</div>
            <div class="header-sub-title">Seller Net Sheet &mdash; Estimated Net Proceeds</div>
        </td>
    </tr>
</table>
<div class="accent-bar"></div>

<!-- Property Info -->
<div class="property-block">
    <span class="closing-right">Closing Date: <strong><?php echo esc_html($closing_date); ?></strong></span>
    <span class="for-label">For:</span>
    <strong><?php echo esc_html($sheet_data['property_address']); ?></strong>
    <?php if (!empty($sheet_data['property_city'])): ?>
        &nbsp;<?php echo esc_html($sheet_data['property_city'] . ', ' . $sheet_data['property_state'] . ' ' . $sheet_data['property_zip']); ?>
    <?php endif; ?>
    <br>
    <span class="for-label">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
    <?php echo esc_html($sheet_data['property_county']); ?> County
</div>

<!-- Calculation Table -->
<table class="calc-table">
    <thead>
        <tr>
            <th class="col-desc" style="width:60%;">Description</th>
            <th style="width:20%;">Debit</th>
            <th style="width:20%;">Credit</th>
        </tr>
    </thead>
    <tbody>

        <!-- FINANCIAL -->
        <tr class="section-header"><td colspan="3">Financial</td></tr>
        <tr>
            <td class="col-desc indented">Sale Price of the Property</td>
            <td class="col-debit">&mdash;</td>
            <td class="col-credit"><?php echo nss_pdf_amount($results['net_proceeds']['sales_price'], true); ?></td>
        </tr>
        <tr>
            <td class="col-desc indented">Seller&rsquo;s Owner&rsquo;s Title Policy (OTIRB)</td>
            <td class="col-debit"><?php echo nss_pdf_amount($results['title_fees']['owner_policy_fee']); ?></td>
            <td class="col-credit">&mdash;</td>
        </tr>

        <!-- PRORATIONS -->
        <tr class="section-header"><td colspan="3">Prorations / Adjustments</td></tr>
        <tr>
            <td class="col-desc indented">
                County Taxes
                <?php if ($annual_taxes > 0): ?>
                    &nbsp;<small style="color:#666;"><?php echo esc_html($jan1_label . ' – ' . $close_label); ?></small>
                <?php endif; ?>
            </td>
            <td class="col-debit"><?php echo nss_pdf_amount($results['tax_proration']['prorated_amount']); ?></td>
            <td class="col-credit">&mdash;</td>
        </tr>
        <tr>
            <td class="col-desc indented">Property Tax Hold (50% of Annual Taxes)</td>
            <td class="col-debit"><?php echo nss_pdf_amount($results['tax_proration']['tax_hold']); ?></td>
            <td class="col-credit">&mdash;</td>
        </tr>

        <!-- TITLE CHARGES -->
        <tr class="section-header"><td colspan="3">Title Charges &amp; Escrow / Settlement Charges</td></tr>
        <tr>
            <td class="col-desc indented">Closing Fee</td>
            <td class="col-debit"><?php echo nss_pdf_amount($results['title_fees']['closing_fee']); ?></td>
            <td class="col-credit">&mdash;</td>
        </tr>
        <?php if (floatval($results['title_fees']['courier_fee']) > 0): ?>
        <tr>
            <td class="col-desc indented">Courier Fee</td>
            <td class="col-debit"><?php echo nss_pdf_amount($results['title_fees']['courier_fee']); ?></td>
            <td class="col-credit">&mdash;</td>
        </tr>
        <?php endif; ?>
        <?php if (floatval($results['title_fees']['deed_prep_fee']) > 0): ?>
        <tr>
            <td class="col-desc indented">Deed Preparation Fee</td>
            <td class="col-debit"><?php echo nss_pdf_amount($results['title_fees']['deed_prep_fee']); ?></td>
            <td class="col-credit">&mdash;</td>
        </tr>
        <?php endif; ?>
        <?php if (floatval($results['title_fees']['wire_transfer_fee']) > 0): ?>
        <tr>
            <td class="col-desc indented">Wire Transfer Fee</td>
            <td class="col-debit"><?php echo nss_pdf_amount($results['title_fees']['wire_transfer_fee']); ?></td>
            <td class="col-credit">&mdash;</td>
        </tr>
        <?php endif; ?>

        <!-- REAL ESTATE CHARGES -->
        <tr class="section-header"><td colspan="3">Real Estate Charges</td></tr>
        <tr>
            <td class="col-desc indented">
                Real Estate Broker Fee
                <?php if ($results['commission']['rate'] > 0): ?>
                    <small style="color:#666;">(<?php echo esc_html(number_format((float)$results['commission']['rate'], 1)); ?>%)</small>
                <?php endif; ?>
            </td>
            <td class="col-debit"><?php echo nss_pdf_amount($results['commission']['amount']); ?></td>
            <td class="col-credit">&mdash;</td>
        </tr>

        <!-- GOVERNMENT TRANSFER -->
        <tr class="section-header"><td colspan="3">Government Transfer Charges</td></tr>
        <tr>
            <td class="col-desc indented">Deed Transfer Tax / Conveyance Fee</td>
            <td class="col-debit"><?php echo nss_pdf_amount($results['conveyance_fees']['seller_amount']); ?></td>
            <td class="col-credit">&mdash;</td>
        </tr>

        <!-- PAYOFFS -->
        <tr class="section-header"><td colspan="3">Payoffs</td></tr>
        <?php
        $any_payoff = false;
        foreach (['payoff_1' => 'Mortgage #1', 'payoff_2' => 'Mortgage #2', 'payoff_3' => 'Mortgage #3'] as $key => $label):
            if (floatval($results['loan_payoffs'][$key]) > 0):
                $any_payoff = true;
        ?>
        <tr>
            <td class="col-desc indented"><?php echo esc_html($label); ?></td>
            <td class="col-debit"><?php echo nss_pdf_amount($results['loan_payoffs'][$key]); ?></td>
            <td class="col-credit">&mdash;</td>
        </tr>
        <?php endif; endforeach; ?>
        <?php if (!$any_payoff): ?>
        <tr>
            <td class="col-desc indented" style="color:#999;">Mortgage(s)</td>
            <td class="col-debit">&mdash;</td>
            <td class="col-credit">&mdash;</td>
        </tr>
        <?php endif; ?>

        <!-- HOA -->
        <?php if (!empty($sheet_data['hoa_fees']) && floatval($sheet_data['hoa_fees']) > 0): ?>
        <tr class="section-header"><td colspan="3">HomeOwners Association</td></tr>
        <tr>
            <td class="col-desc indented">HOA Fees &mdash; ESTIMATE</td>
            <td class="col-debit"><?php echo nss_pdf_amount($sheet_data['hoa_fees']); ?></td>
            <td class="col-credit">&mdash;</td>
        </tr>
        <?php endif; ?>

        <!-- SUBTOTALS -->
        <tr class="subtotal-row">
            <td class="col-desc">Subtotals</td>
            <td style="text-align:right;"><?php echo nss_pdf_amount($results['total_deductions'], true); ?></td>
            <td style="text-align:right;"><?php echo nss_pdf_amount($results['net_proceeds']['sales_price'], true); ?></td>
        </tr>

        <!-- NET PROCEEDS -->
        <tr class="net-row">
            <td class="col-desc">NET PROCEEDS TO SELLER</td>
            <td>&nbsp;</td>
            <td style="text-align:right;"><?php echo nss_pdf_amount($results['net_proceeds']['net_amount'], true); ?></td>
        </tr>

    </tbody>
</table>

<div class="disclaimer">
    * This estimate is an approximation and is not guaranteed. The estimate is based on information provided.
    Additional charges may occur based on the details of the sale &mdash; mobile notary fees, seller credits,
    recording fees, courier fees, etc. Final figures will be provided at closing.
</div>
<div class="generated-note">
    Generated by Affiliates Title Group, LLC &mdash; <?php echo date('M d, Y'); ?>
</div>

</body>
</html>
