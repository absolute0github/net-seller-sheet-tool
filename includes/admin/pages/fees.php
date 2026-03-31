<?php
/**
 * Admin fees management page
 */

if (!current_user_can('manage_nss_fees')) {
    wp_die('You do not have permission to access this page');
}

$fee_type = sanitize_text_field($_GET['type'] ?? 'conveyance');
$valid_types = ['conveyance', 'property_value', 'title_closing', 'static_fee'];

if (!in_array($fee_type, $valid_types)) {
    $fee_type = 'conveyance';
}

// Get all fees for current type (active and inactive for admin view)
$fees = NSS_Fee::get_all($fee_type);
?>

<div class="wrap">
    <h1>Fee Configuration</h1>

    <div class="nss-fee-tabs">
        <ul class="nav-tab-wrapper">
            <li><a href="<?php echo esc_url(admin_url('admin.php?page=nss-fees&type=conveyance')); ?>" class="nav-tab <?php echo $fee_type === 'conveyance' ? 'nav-tab-active' : ''; ?>">Conveyance Fees</a></li>
            <li><a href="<?php echo esc_url(admin_url('admin.php?page=nss-fees&type=property_value')); ?>" class="nav-tab <?php echo $fee_type === 'property_value' ? 'nav-tab-active' : ''; ?>">Title Insurance Rates</a></li>
            <li><a href="<?php echo esc_url(admin_url('admin.php?page=nss-fees&type=title_closing')); ?>" class="nav-tab <?php echo $fee_type === 'title_closing' ? 'nav-tab-active' : ''; ?>">Title Closing Fees</a></li>
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
                                    $<?php echo esc_html(number_format((float)$fee->fee_percentage, 2)); ?>/thousand
                                <?php elseif ($fee_type === 'property_value'): ?>
                                    <strong><?php echo esc_html($fee->tier_name); ?></strong><br>
                                    <?php echo NSS_Formatting::currency($fee->min_price); ?> &ndash; <?php echo NSS_Formatting::currency($fee->max_price); ?> @ $<?php echo esc_html(number_format((float)$fee->rate, 2)); ?>/k
                                <?php elseif ($fee_type === 'title_closing'): ?>
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
                                <button class="button button-small nss-edit-fee"
                                    data-fee-id="<?php echo esc_attr($fee->id); ?>"
                                    data-fee-type="<?php echo esc_attr($fee_type); ?>"
                                    data-fee="<?php echo esc_attr(json_encode($fee)); ?>">Edit</button>
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
            <p>No <?php echo esc_html(strtolower(str_replace('_', ' ', $fee_type))); ?> fees configured yet.</p>
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
                        <label for="fee_percentage">Rate ($ per $1,000)</label>
                        <input type="number" name="fee_percentage" id="fee_percentage" step="0.01" min="0" class="small-text" placeholder="e.g. 3.00">
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
                        <label for="rate">Rate ($ per $1,000)</label>
                        <input type="number" name="rate" id="rate" step="0.01" min="0" class="small-text" required placeholder="e.g. 5.80">
                    </p>

                <?php elseif ($fee_type === 'title_closing'): ?>
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
                            <option value="other">Other&hellip;</option>
                        </select>
                    </p>
                    <p id="custom_fee_type_row" style="display:none;">
                        <label for="custom_fee_type">Custom Fee Name *</label>
                        <input type="text" name="custom_fee_type" id="custom_fee_type" class="regular-text" placeholder="e.g. Notary Fee">
                    </p>
                    <p>
                        <label for="fee_amount">Fee Amount ($)</label>
                        <input type="number" name="fee_amount" id="fee_amount" step="0.01" min="0" class="small-text" required>
                    </p>
                <?php endif; ?>

                <p>
                    <label>
                        <input type="checkbox" name="is_active" id="is_active" value="1" checked>
                        Active
                    </label>
                </p>

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
