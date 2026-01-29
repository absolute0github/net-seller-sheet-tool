<?php
/**
 * My sheets list template
 */
?>

<div class="nss-my-sheets-container">
    <div class="nss-my-sheets-header">
        <h2>My Sheets</h2>
        <a href="<?php echo do_shortcode('[nss_calculator]'); ?>" class="nss-button nss-button-primary">Create New Sheet</a>
    </div>

    <?php if ($sheets): ?>
        <table class="nss-sheets-table">
            <thead>
                <tr>
                    <th>Property Address</th>
                    <th>County</th>
                    <th>Sales Price</th>
                    <th>Net Proceeds</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sheets as $sheet): ?>
                    <tr>
                        <td><?php echo esc_html($sheet['property_address']); ?></td>
                        <td><?php echo esc_html($sheet['property_county']); ?></td>
                        <td><?php echo NSS_Formatting::currency($sheet['sales_price']); ?></td>
                        <td><?php echo NSS_Formatting::currency($sheet['net_proceeds']); ?></td>
                        <td><?php echo NSS_Formatting::date($sheet['created_at']); ?></td>
                        <td>
                            <a href="#" class="nss-button-small nss-view-sheet" data-sheet-id="<?php echo esc_attr($sheet['id']); ?>">View</a>
                            <a href="#" class="nss-button-small nss-edit-sheet" data-sheet-id="<?php echo esc_attr($sheet['id']); ?>">Edit</a>
                            <a href="#" class="nss-button-small nss-delete-sheet" data-sheet-id="<?php echo esc_attr($sheet['id']); ?>">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="nss-empty-state">
            <p>You haven't created any sheets yet.</p>
            <a href="<?php echo do_shortcode('[nss_calculator]'); ?>" class="nss-button nss-button-primary">Create Your First Sheet</a>
        </div>
    <?php endif; ?>
</div>
