<?php
/**
 * Admin settings page
 */

if (!current_user_can('manage_nss_settings')) {
    wp_die('You do not have permission to access this page');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nss_settings_submit'])) {
    if (!wp_verify_nonce($_POST['_wpnonce'], 'nss_settings')) {
        wp_die('Nonce verification failed');
    }

    // Save settings
    update_option('nss_company_default_name', sanitize_text_field($_POST['company_name'] ?? ''));
    update_option('nss_show_company_info', isset($_POST['show_company_info']) ? 1 : 0);

    // Address autocomplete settings
    update_option('nss_radar_api_key', sanitize_text_field($_POST['radar_api_key'] ?? ''));
    update_option('nss_enable_autocomplete', isset($_POST['enable_autocomplete']) ? 1 : 0);
    update_option('nss_restrict_ohio', isset($_POST['restrict_ohio']) ? 1 : 0);

    echo '<div class="notice notice-success"><p>Settings saved successfully.</p></div>';
}

$company_name = get_option('nss_company_default_name', '');
$show_company_info = get_option('nss_show_company_info', 0);
$radar_api_key = get_option('nss_radar_api_key', '');
$enable_autocomplete = get_option('nss_enable_autocomplete', 0);
$restrict_ohio = get_option('nss_restrict_ohio', 1);
?>

<div class="wrap">
    <h1>Net Seller Sheet Settings</h1>

    <form method="POST" action="">
        <?php wp_nonce_field('nss_settings'); ?>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="company_name">Default Company Name</label>
                </th>
                <td>
                    <input type="text" id="company_name" name="company_name" value="<?php echo esc_attr($company_name); ?>" class="regular-text">
                    <p class="description">Default company name for PDF generation</p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="show_company_info">Show Company Information</label>
                </th>
                <td>
                    <input type="checkbox" id="show_company_info" name="show_company_info" value="1" <?php checked($show_company_info); ?>>
                    <label for="show_company_info">Display company info in sheets</label>
                </td>
            </tr>
        </table>

        <h2>Address Autocomplete</h2>
        <p class="description">Configure address autocomplete powered by Radar.io. Get a free API key at <a href="https://radar.io" target="_blank">radar.io</a> (100,000 free requests/month).</p>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="radar_api_key">Radar.io API Key</label>
                </th>
                <td>
                    <input type="text" id="radar_api_key" name="radar_api_key" value="<?php echo esc_attr($radar_api_key); ?>" class="regular-text">
                    <p class="description">Your Radar.io publishable or secret API key</p>
                    <button type="button" id="nss-test-radar" class="button button-secondary" style="margin-top:6px;">Test Connection</button>
                    <span id="nss-test-radar-result" style="margin-left:10px;"></span>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="enable_autocomplete">Enable Autocomplete</label>
                </th>
                <td>
                    <input type="checkbox" id="enable_autocomplete" name="enable_autocomplete" value="1" <?php checked($enable_autocomplete); ?>>
                    <label for="enable_autocomplete">Enable address autocomplete on calculator form</label>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="restrict_ohio">Restrict to Ohio</label>
                </th>
                <td>
                    <input type="checkbox" id="restrict_ohio" name="restrict_ohio" value="1" <?php checked($restrict_ohio); ?>>
                    <label for="restrict_ohio">Only show Ohio addresses in autocomplete results</label>
                </td>
            </tr>
        </table>

        <input type="hidden" name="nss_settings_submit" value="1">
        <?php submit_button('Save Settings'); ?>
    </form>

    <hr>

    <h2>Import from Next.js Database</h2>
    <p>Use the form below to import data from your existing Next.js application:</p>

    <form id="nss-import-form" method="POST" action="">
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="db_host">Database Host</label>
                </th>
                <td>
                    <input type="text" id="db_host" name="db_host" class="regular-text" placeholder="localhost">
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="db_name">Database Name</label>
                </th>
                <td>
                    <input type="text" id="db_name" name="db_name" class="regular-text" placeholder="net_seller_sheet">
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="db_user">Database User</label>
                </th>
                <td>
                    <input type="text" id="db_user" name="db_user" class="regular-text">
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="db_pass">Database Password</label>
                </th>
                <td>
                    <input type="password" id="db_pass" name="db_pass" class="regular-text">
                </td>
            </tr>
        </table>

        <p class="description">Warning: This will import all data from your Next.js database into WordPress. Make sure you have a backup first.</p>
        <button type="submit" class="button button-primary">Start Import</button>
    </form>

    <hr>

    <h2>Plugin Information</h2>
    <table class="form-table">
        <tr>
            <th scope="row">Version</th>
            <td><?php echo esc_html(NSS_PLUGIN_VERSION); ?></td>
        </tr>
        <tr>
            <th scope="row">PHP Version</th>
            <td><?php echo esc_html(PHP_VERSION); ?></td>
        </tr>
        <tr>
            <th scope="row">WordPress Version</th>
            <td><?php echo esc_html(get_bloginfo('version')); ?></td>
        </tr>
        <tr>
            <th scope="row">Database Tables</th>
            <td>
                <?php
                global $wpdb;
                $tables = [
                    'nss_sheets',
                    'nss_conveyance_fees',
                    'nss_tax_rates',
                    'nss_property_value_rates',
                    'nss_title_closing_fees',
                    'nss_title_exam_fees',
                    'nss_static_title_fees',
                    'nss_zip_county',
                ];

                foreach ($tables as $table) {
                    $exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}{$table}'");
                    echo '<div>' . esc_html($table) . ': ' . ($exists ? '<span style="color:green;">✓</span>' : '<span style="color:red;">✗</span>') . '</div>';
                }
                ?>
            </td>
        </tr>
    </table>
</div>
