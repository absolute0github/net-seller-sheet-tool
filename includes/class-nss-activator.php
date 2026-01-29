<?php
/**
 * Plugin activation handler
 *
 * Creates database tables and sets up initial data
 */
class NSS_Activator {

    /**
     * Activate the plugin
     */
    public static function activate() {
        // Check PHP version
        if (version_compare(PHP_VERSION, '8.0', '<')) {
            wp_die('Net Seller Sheet requires PHP 8.0 or higher');
        }

        // Check WordPress version
        if (version_compare(get_bloginfo('version'), '6.0', '<')) {
            wp_die('Net Seller Sheet requires WordPress 6.0 or higher');
        }

        // Create database tables
        require_once NSS_PLUGIN_DIR . 'includes/database/class-nss-database.php';
        NSS_Database::create_tables();

        // Seed default data
        self::seed_default_data();

        // Flush rewrite rules
        flush_rewrite_rules();

        // Set activation flag
        update_option('nss_plugin_activated', true);
    }

    /**
     * Seed default property value tiers
     */
    private static function seed_default_data() {
        global $wpdb;

        $table = $wpdb->prefix . 'nss_property_value_rates';

        // Check if already seeded
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        if ($count > 0) {
            return;
        }

        $default_tiers = [
            [
                'tier_name' => 'Tier 1',
                'min_price' => 0,
                'max_price' => 250000,
                'rate' => 0.0050, // 0.5%
                'created_at' => current_time('mysql'),
            ],
            [
                'tier_name' => 'Tier 2',
                'min_price' => 250000,
                'max_price' => 500000,
                'rate' => 0.0040, // 0.4%
                'created_at' => current_time('mysql'),
            ],
            [
                'tier_name' => 'Tier 3',
                'min_price' => 500000,
                'max_price' => 1000000,
                'rate' => 0.0030, // 0.3%
                'created_at' => current_time('mysql'),
            ],
            [
                'tier_name' => 'Tier 4',
                'min_price' => 1000000,
                'max_price' => 999999999,
                'rate' => 0.0020, // 0.2%
                'created_at' => current_time('mysql'),
            ],
        ];

        foreach ($default_tiers as $tier) {
            $wpdb->insert($table, $tier, ['%s', '%f', '%f', '%f', '%s']);
        }
    }
}
