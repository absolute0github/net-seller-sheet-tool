<?php
/**
 * Plugin Name: Net Seller Sheet
 * Plugin URI: https://github.com/yourusername/net-seller-sheet
 * Description: Real estate net proceeds calculator with PDF generation
 * Version: 1.1.1
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: nss
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Tested up to: 6.4
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('NSS_PLUGIN_FILE', __FILE__);
define('NSS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('NSS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('NSS_PLUGIN_VERSION', '1.1.1');
define('NSS_PREFIX', 'nss');
define('NSS_DB_VERSION', 3);

// Load Composer autoloader
require_once NSS_PLUGIN_DIR . 'vendor/autoload.php';

// Load plugin classes
require_once NSS_PLUGIN_DIR . 'includes/class-nss-plugin.php';

// Initialize plugin on WordPress initialization
add_action('plugins_loaded', function () {
    $plugin = new NSS_Plugin();
    $plugin->run();
});

// Activation hook
register_activation_hook(__FILE__, function () {
    require_once NSS_PLUGIN_DIR . 'includes/class-nss-activator.php';
    NSS_Activator::activate();
});

// Deactivation hook
register_deactivation_hook(__FILE__, function () {
    require_once NSS_PLUGIN_DIR . 'includes/class-nss-deactivator.php';
    NSS_Deactivator::deactivate();
});

// Uninstall hook - calls uninstall.php
register_uninstall_hook(__FILE__, 'nss_uninstall');

function nss_uninstall() {
    require_once plugin_dir_path(__FILE__) . 'uninstall.php';
}
