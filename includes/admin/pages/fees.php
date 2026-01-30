<?php
/**
 * Admin fees management page
 */

if (!current_user_can('manage_nss_fees')) {
    wp_die('You do not have permission to access this page');
}

$fee_type = sanitize_text_field($_GET['type'] ?? 'conveyance');
$valid_types = ['conveyance', 'tax_rate', 'property_value', 'title_closing', 'title_exam', 'static_fee'];

if (!in_array($fee_type, $valid_types)) {
    $fee_type = 'conveyance';
}

// Get fees for current type
$fees = NSS_Fee::get_active($fee_type);
?>

<div class="wrap">
    <h1>Fee Configuration</h1>

    <div class="nss-fee-tabs">
        <ul class="nav-tab-wrapper">
            <li><a href="<?php echo esc_url(admin_url('admin.php?page=nss-fees&type=conveyance')); ?>" class="nav-tab <?php echo $fee_type === 'conveyance' ? 'nav-tab-active' : ''; ?>">Conveyance Fees</a></li>
            <li><a href="<?php echo esc_url(admin_url('admin.php?page=nss-fees&type=tax_rate')); ?>" class="nav-tab <?php echo $fee_type === 'tax_rate' ? 'nav-tab-active' : ''; ?>">Tax Rates</a></li>
            <li><a href="<?php echo esc_url(admin_url('admin.php?page=nss-fees&type=property_value')); ?>" class="nav-tab <?php echo $fee_type === 'property_value' ? 'nav-tab-active' : ''; ?>">Property Value Tiers</a></li>
            <li><a href="<?php echo esc_url(admin_url('admin.php?page=nss-fees&type=title_closing')); ?>" class="nav-tab <?php echo $fee_type === 'title_closing' ? 'nav-tab-active' : ''; ?>">Title Closing Fees</a></li>
            <li><a href="<?php echo esc_url(admin_url('admin.php?page=nss-fees&type=title_exam')); ?>" class="nav-tab <?php echo $fee_type === 'title_exam' ? 'nav-tab-active' : ''; ?>">Title Exam Fees</a></li>
            <li><a href="<?php echo esc_url(admin_url('admin.php?page=nss-fees&type=static_fee')); ?>" class="nav-tab <?php echo $fee_type === 'static_fee' ? 'nav-tab-active' : ''; ?>">Static Fees</a></li>
        </ul>
    </div>

    <div class="nss-fee-content">
        <button class="button button-primary" id="nss-add-fee-btn">Add New <?php echo esc_html(ucfirst(str_replace('_', ' ', $fee_type))); ?></button>

        <?php if ($fees): ?>
            <table class="wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th width="50">ID</th>
                        <th>Details</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($fees as $fee): ?>
                        <tr>
                            <td><?php echo esc_html($fee->id); ?></td>
                            <td>
                                <?php if ($fee_type === 'conveyance'): ?>
                                    <strong><?php echo esc_html($fee->county_name); ?></strong><br>
                                    <?php echo NSS_Formatting::percentage($fee->fee_percentage); ?> or <?php echo NSS_Formatting::currency($fee->flat_fee); ?>
                                <?php elseif ($fee_type === 'tax_rate'): ?>
                                    <strong><?php echo esc_html($fee->county_name); ?></strong><br>
                                    <?php echo NSS_Formatting::percentage($fee->tax_rate); ?>
                                <?php elseif ($fee_type === 'property_value'): ?>
                                    <strong><?php echo esc_html($fee->tier_name); ?></strong><br>
                                    <?php echo NSS_Formatting::currency($fee->min_price); ?> - <?php echo NSS_Formatting::currency($fee->max_price); ?> @ <?php echo NSS_Formatting::percentage($fee->rate); ?>
                                <?php elseif (in_array($fee_type, ['title_closing', 'title_exam'])): ?>
                                    <strong><?php echo esc_html($fee->county_name); ?></strong><br>
                                    <?php echo NSS_Formatting::currency($fee->fee_amount); ?>
                                <?php elseif ($fee_type === 'static_fee'): ?>
                                    <strong><?php echo esc_html(ucfirst(str_replace('_', ' ', $fee->fee_type))); ?></strong><br>
                                    <?php echo NSS_Formatting::currency($fee->fee_amount); ?>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $fee->is_active ? '<span class="dashicons dashicons-yes"></span> Active' : '<span class="dashicons dashicons-no"></span> Inactive'; ?></td>
                            <td><?php echo NSS_Formatting::date($fee->created_at); ?></td>
                            <td>
                                <button class="button button-small nss-edit-fee" data-fee-id="<?php echo esc_attr($fee->id); ?>" data-fee-type="<?php echo esc_attr($fee_type); ?>">Edit</button>
                                <button class="button button-small nss-toggle-fee" data-fee-id="<?php echo esc_attr($fee->id); ?>" data-fee-type="<?php echo esc_attr($fee_type); ?>">
                                    <?php echo $fee->is_active ? 'Deactivate' : 'Activate'; ?>
                                </button>
                                <button class="button button-small button-link-delete nss-delete-fee" data-fee-id="<?php echo esc_attr($fee->id); ?>" data-fee-type="<?php echo esc_attr($fee_type); ?>">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No <?php echo esc_html(strtolower($fee_type)); ?> fees configured yet.</p>
        <?php endif; ?>
    </div>

    <!-- Fee Modal -->
    <div id="nss-fee-modal" class="nss-modal" style="display:none;">
        <div class="nss-modal-content">
            <span class="nss-modal-close">&times;</span>
            <h2 id="nss-modal-title">Add New Fee</h2>
            <form id="nss-fee-form">
                <input type="hidden" name="fee_id" id="nss-fee-id" value="">
                <input type="hidden" name="fee_type" id="nss-fee-type" value="<?php echo esc_attr($fee_type); ?>">

                <?php if ($fee_type === 'conveyance'): ?>
                    <p>
                        <label for="county_name">County Name *</label>
                        <input type="text" name="county_name" id="county_name" class="regular-text" required>
                    </p>
                    <p>
                        <label for="state">State</label>
                        <input type="text" name="state" id="state" class="small-text" maxlength="2" placeholder="OH">
                    </p>
                    <p>
                        <label for="fee_percentage">Fee Percentage (%)</label>
                        <input type="number" name="fee_percentage" id="fee_percentage" step="0.0001" min="0" class="small-text">
                    </p>
                    <p>
                        <label for="flat_fee">Flat Fee ($)</label>
                        <input type="number" name="flat_fee" id="flat_fee" step="0.01" min="0" class="small-text">
                    </p>
                    <p>
                        <label for="seller_pays_percentage">Seller Pays Percentage (0-1)</label>
                        <input type="number" name="seller_pays_percentage" id="seller_pays_percentage" step="0.01" min="0" max="1" value="1.0" class="small-text">
                    </p>

                <?php elseif ($fee_type === 'tax_rate'): ?>
                    <p>
                        <label for="county_name">County Name *</label>
                        <input type="text" name="county_name" id="county_name" class="regular-text" required>
                    </p>
                    <p>
                        <label for="state">State</label>
                        <input type="text" name="state" id="state" class="small-text" maxlength="2" placeholder="OH">
                    </p>
                    <p>
                        <label for="tax_rate">Tax Rate (%)</label>
                        <input type="number" name="tax_rate" id="tax_rate" step="0.0001" min="0" class="small-text" required>
                    </p>
                    <p>
                        <label for="tax_type">Tax Type</label>
                        <select name="tax_type" id="tax_type">
                            <option value="property_tax">Property Tax</option>
                            <option value="sales_tax">Sales Tax</option>
                        </select>
                    </p>

                <?php elseif ($fee_type === 'property_value'): ?>
                    <p>
                        <label for="tier_name">Tier Name *</label>
                        <input type="text" name="tier_name" id="tier_name" class="regular-text" required>
                    </p>
                    <p>
                        <label for="min_price">Min Price ($)</label>
                        <input type="number" name="min_price" id="min_price" step="0.01" min="0" class="small-text" required>
                    </p>
                    <p>
                        <label for="max_price">Max Price ($)</label>
                        <input type="number" name="max_price" id="max_price" step="0.01" min="0" class="small-text" required>
                    </p>
                    <p>
                        <label for="rate">Rate (%)</label>
                        <input type="number" name="rate" id="rate" step="0.0001" min="0" class="small-text" required>
                    </p>

                <?php elseif (in_array($fee_type, ['title_closing', 'title_exam'])): ?>
                    <p>
                        <label for="county_name">County Name *</label>
                        <input type="text" name="county_name" id="county_name" class="regular-text" required>
                    </p>
                    <p>
                        <label for="state">State</label>
                        <input type="text" name="state" id="state" class="small-text" maxlength="2" placeholder="OH">
                    </p>
                    <p>
                        <label for="fee_amount">Fee Amount ($)</label>
                        <input type="number" name="fee_amount" id="fee_amount" step="0.01" min="0" class="small-text" required>
                    </p>

                <?php elseif ($fee_type === 'static_fee'): ?>
                    <p>
                        <label for="static_fee_type">Fee Type *</label>
                        <select name="static_fee_type" id="static_fee_type" required>
                            <option value="courier">Courier</option>
                            <option value="deed_prep">Deed Prep</option>
                            <option value="wire_transfer">Wire Transfer</option>
                        </select>
                    </p>
                    <p>
                        <label for="fee_amount">Fee Amount ($)</label>
                        <input type="number" name="fee_amount" id="fee_amount" step="0.01" min="0" class="small-text" required>
                    </p>
                <?php endif; ?>

                <p>
                    <button type="submit" class="button button-primary">Save Fee</button>
                    <button type="button" class="button nss-modal-cancel">Cancel</button>
                </p>
            </form>
        </div>
    </div>

    <style>
        .nss-modal {
            position: fixed;
            z-index: 100000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .nss-modal-content {
            background-color: #fff;
            margin: 10% auto;
            padding: 20px;
            border: 1px solid #ccc;
            width: 500px;
            max-width: 90%;
            border-radius: 4px;
        }
        .nss-modal-close {
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .nss-modal-close:hover { color: #d63638; }
        #nss-fee-form label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
        }
        #nss-fee-form input,
        #nss-fee-form select {
            width: 100%;
            max-width: 300px;
        }
        #nss-fee-form p { margin-bottom: 15px; }
    </style>
</div>
