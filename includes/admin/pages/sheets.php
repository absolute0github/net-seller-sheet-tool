<?php
/**
 * Admin sheets management page
 */

if (!current_user_can('view_all_nss_sheets')) {
    wp_die('You do not have permission to access this page');
}

global $wpdb;

// Get all sheets
$sheets = NSS_Sheet::get_all_sheets([
    'limit' => 50,
    'offset' => 0,
]);

// Count total
$total_sheets = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}nss_sheets WHERE deleted_at IS NULL");
?>

<div class="wrap">
    <h1>All Sheets</h1>

    <div class="nss-sheets-list">
        <?php if ($sheets): ?>
            <table class="wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th width="50">ID</th>
                        <th>Address</th>
                        <th>County</th>
                        <th>Sales Price</th>
                        <th>Net Proceeds</th>
                        <th>User</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sheets as $sheet):
                        $user = get_user_by('id', $sheet['user_id']);
                    ?>
                        <tr>
                            <td><?php echo esc_html($sheet['id']); ?></td>
                            <td><?php echo esc_html($sheet['property_address']); ?></td>
                            <td><?php echo esc_html($sheet['property_county']); ?></td>
                            <td><?php echo NSS_Formatting::currency($sheet['sales_price']); ?></td>
                            <td><?php echo NSS_Formatting::currency($sheet['net_proceeds']); ?></td>
                            <td><?php echo esc_html($user->user_login ?? 'N/A'); ?></td>
                            <td><?php echo NSS_Formatting::date($sheet['created_at']); ?></td>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=nss-sheets&action=view&id=' . $sheet['id'])); ?>" class="button button-small">View</a>
                                <?php if (current_user_can('edit_all_nss_sheets')): ?>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=nss-sheets&action=edit&id=' . $sheet['id'])); ?>" class="button button-small">Edit</a>
                                <?php endif; ?>
                                <?php if (current_user_can('delete_all_nss_sheets')): ?>
                                    <button class="button button-small button-link-delete nss-delete-sheet" data-sheet-id="<?php echo esc_attr($sheet['id']); ?>">Delete</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No sheets found.</p>
        <?php endif; ?>
    </div>

    <div class="nss-pagination">
        <p>Showing <?php echo count($sheets); ?> of <?php echo esc_html($total_sheets); ?> sheets</p>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const deleteButtons = document.querySelectorAll('.nss-delete-sheet');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to delete this sheet?')) {
                const sheetId = this.dataset.sheetId;
                // AJAX delete call would go here
            }
        });
    });
});
</script>
