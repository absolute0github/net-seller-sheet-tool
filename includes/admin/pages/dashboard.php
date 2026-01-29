<?php
/**
 * Admin dashboard page
 */

if (!current_user_can('manage_nss_settings')) {
    wp_die('You do not have permission to access this page');
}

global $wpdb;

// Get statistics
$stats = [
    'total_sheets' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}nss_sheets WHERE deleted_at IS NULL"),
    'total_users' => (int) $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}nss_sheets WHERE deleted_at IS NULL"),
    'total_sales_price' => (float) $wpdb->get_var("SELECT SUM(sales_price) FROM {$wpdb->prefix}nss_sheets WHERE deleted_at IS NULL"),
    'total_net_proceeds' => (float) $wpdb->get_var("SELECT SUM(net_proceeds) FROM {$wpdb->prefix}nss_sheets WHERE deleted_at IS NULL"),
];

// Get recent sheets
$recent_sheets = $wpdb->get_results(
    "SELECT s.id, s.property_address, s.sales_price, u.user_login
    FROM {$wpdb->prefix}nss_sheets s
    LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID
    WHERE s.deleted_at IS NULL
    ORDER BY s.created_at DESC
    LIMIT 10"
);
?>

<div class="wrap">
    <h1>Net Seller Sheet Dashboard</h1>

    <div class="nss-dashboard-stats">
        <div class="stat-box">
            <h3>Total Sheets</h3>
            <p class="stat-value"><?php echo esc_html($stats['total_sheets']); ?></p>
        </div>

        <div class="stat-box">
            <h3>Total Users</h3>
            <p class="stat-value"><?php echo esc_html($stats['total_users']); ?></p>
        </div>

        <div class="stat-box">
            <h3>Total Sales Price</h3>
            <p class="stat-value"><?php echo NSS_Formatting::currency($stats['total_sales_price']); ?></p>
        </div>

        <div class="stat-box">
            <h3>Total Net Proceeds</h3>
            <p class="stat-value"><?php echo NSS_Formatting::currency($stats['total_net_proceeds']); ?></p>
        </div>
    </div>

    <div class="nss-recent-sheets">
        <h2>Recent Sheets</h2>
        <?php if ($recent_sheets): ?>
            <table class="wp-list-table widefat">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Address</th>
                        <th>Sales Price</th>
                        <th>User</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_sheets as $sheet): ?>
                        <tr>
                            <td><?php echo esc_html($sheet->id); ?></td>
                            <td><?php echo esc_html($sheet->property_address); ?></td>
                            <td><?php echo NSS_Formatting::currency($sheet->sales_price); ?></td>
                            <td><?php echo esc_html($sheet->user_login ?? 'N/A'); ?></td>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=nss-sheets&action=view&id=' . $sheet->id)); ?>">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No sheets yet.</p>
        <?php endif; ?>
    </div>

    <div class="nss-quick-links">
        <h2>Quick Links</h2>
        <ul>
            <li><a href="<?php echo esc_url(admin_url('admin.php?page=nss-sheets')); ?>">View All Sheets</a></li>
            <li><a href="<?php echo esc_url(admin_url('admin.php?page=nss-fees')); ?>">Manage Fees</a></li>
            <li><a href="<?php echo esc_url(admin_url('admin.php?page=nss-settings')); ?>">Settings</a></li>
        </ul>
    </div>
</div>
