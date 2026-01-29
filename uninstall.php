<?php
/**
 * Plugin uninstall handler
 *
 * Cleans up all plugin data from database on uninstall
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'includes/database/class-nss-database.php';

// Only proceed if the class exists
if (class_exists('NSS_Database')) {
    // Drop all custom tables
    NSS_Database::drop_tables();
}

// Remove plugin options
delete_option('nss_plugin_activated');
delete_option('nss_db_version');

// Remove user meta (company info)
delete_user_meta(null, 'nss_company', null);
delete_user_meta(null, 'nss_company_logo_id', null);
delete_user_meta(null, 'nss_company_logo_url', null);

// Remove capabilities from roles
$admin_role = get_role('administrator');
$user_role = get_role('subscriber');

if ($admin_role || $user_role) {
    $caps = [
        'view_nss_sheets',
        'create_nss_sheets',
        'edit_nss_sheets',
        'delete_nss_sheets',
        'download_nss_pdf',
        'manage_nss_fees',
        'view_all_nss_sheets',
        'edit_all_nss_sheets',
        'delete_all_nss_sheets',
        'manage_nss_settings',
    ];

    if ($admin_role) {
        foreach ($caps as $cap) {
            $admin_role->remove_cap($cap);
        }
    }

    if ($user_role) {
        foreach ($caps as $cap) {
            $user_role->remove_cap($cap);
        }
    }
}
