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
</div>
