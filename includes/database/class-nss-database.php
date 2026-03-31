<?php
/**
 * Database management class
 *
 * Creates and manages custom database tables
 */
class NSS_Database {

    /**
     * Initialize database (check version and create tables if needed)
     */
    public static function initialize() {
        $current_version = get_option('nss_db_version');

        if ($current_version !== NSS_DB_VERSION) {
            self::create_tables();
            update_option('nss_db_version', NSS_DB_VERSION);
            self::seed_ohio_title_insurance_rates();
        }
    }

    /**
     * Create custom database tables
     */
    public static function create_tables() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();
        $sql = [];

        // 1. Sheets table
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}nss_sheets (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            property_address VARCHAR(255) NOT NULL,
            property_city VARCHAR(100),
            property_state VARCHAR(100),
            property_county VARCHAR(100),
            property_zip VARCHAR(10),
            sales_price DECIMAL(18,2) NOT NULL,
            loan_payoff_1 DECIMAL(18,2),
            loan_payoff_2 DECIMAL(18,2),
            loan_payoff_3 DECIMAL(18,2),
            closing_date DATE,
            tax_info JSON,
            commission_structure JSON,
            hoa_fees DECIMAL(18,2),
            additional_fees JSON,
            net_proceeds DECIMAL(18,2),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at DATETIME,
            KEY user_id (user_id),
            KEY property_county (property_county),
            KEY created_at (created_at),
            FOREIGN KEY (user_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE
        ) $charset_collate;";

        // 2. Conveyance fees table
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}nss_conveyance_fees (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            county_name VARCHAR(100) NOT NULL UNIQUE,
            state VARCHAR(2),
            fee_percentage DECIMAL(8,4) NOT NULL,
            flat_fee DECIMAL(18,2),
            seller_pays_percentage DECIMAL(3,2) DEFAULT 1.0,
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY is_active (is_active)
        ) $charset_collate;";

        // 3. Property value rates (title insurance tiers)
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}nss_property_value_rates (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tier_name VARCHAR(100) NOT NULL,
            min_price DECIMAL(18,2) NOT NULL,
            max_price DECIMAL(18,2) NOT NULL,
            rate DECIMAL(8,4) NOT NULL,
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY min_price (min_price),
            KEY is_active (is_active)
        ) $charset_collate;";

        // 4. Title closing fees table
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}nss_title_closing_fees (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            county_name VARCHAR(100) NOT NULL,
            state VARCHAR(2),
            fee_amount DECIMAL(18,2) NOT NULL,
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY county_name (county_name),
            KEY is_active (is_active)
        ) $charset_collate;";

        // 5. Static title fees table (courier, deed prep, wire)
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}nss_static_title_fees (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            fee_type ENUM('courier', 'deed_prep', 'wire_transfer') NOT NULL UNIQUE,
            fee_amount DECIMAL(18,2) NOT NULL,
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY is_active (is_active)
        ) $charset_collate;";

        // 6. ZIP to County mapping table (for Ohio)
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}nss_zip_county (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            zip_code VARCHAR(5) NOT NULL,
            county_name VARCHAR(100) NOT NULL,
            city VARCHAR(100),
            state VARCHAR(2) DEFAULT 'OH',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            KEY zip_code (zip_code),
            KEY county_name (county_name)
        ) $charset_collate;";

        // Execute all SQL statements
        foreach ($sql as $statement) {
            dbDelta($statement);
        }
    }

    /**
     * Seed Ohio OTIRB Standard Owner's Policy title insurance rates (effective Jan 1, 2026)
     *
     * The rate column stores dollars per $1,000 of coverage (e.g., 5.80 = $5.80/k).
     * Truncates existing tiers first so old commission % tiers are replaced.
     */
    public static function seed_ohio_title_insurance_rates() {
        global $wpdb;
        $table = $wpdb->prefix . 'nss_property_value_rates';

        $wpdb->query("TRUNCATE TABLE {$table}");

        $ohio_tiers = [
            ['tier_name' => 'Up to $250,000',         'min_price' => 0.00,        'max_price' => 250000.00,    'rate' => 5.80],
            ['tier_name' => '$250,001-$500,000',       'min_price' => 250000.00,   'max_price' => 500000.00,    'rate' => 4.10],
            ['tier_name' => '$500,001-$1,000,000',     'min_price' => 500000.00,   'max_price' => 1000000.00,   'rate' => 3.20],
            ['tier_name' => '$1,000,001-$5,000,000',   'min_price' => 1000000.00,  'max_price' => 5000000.00,   'rate' => 3.10],
            ['tier_name' => '$5,000,001-$10,000,000',  'min_price' => 5000000.00,  'max_price' => 10000000.00,  'rate' => 2.90],
            ['tier_name' => 'Over $10,000,000',        'min_price' => 10000000.00, 'max_price' => 999999999.99, 'rate' => 2.60],
        ];

        foreach ($ohio_tiers as $tier) {
            $wpdb->insert(
                $table,
                [
                    'tier_name'  => $tier['tier_name'],
                    'min_price'  => $tier['min_price'],
                    'max_price'  => $tier['max_price'],
                    'rate'       => $tier['rate'],
                    'is_active'  => 1,
                    'created_at' => current_time('mysql'),
                ],
                ['%s', '%f', '%f', '%f', '%d', '%s']
            );
        }
    }

    /**
     * Delete all custom tables (for uninstall)
     */
    public static function drop_tables() {
        global $wpdb;

        $tables = [
            'nss_sheets',
            'nss_conveyance_fees',
            'nss_property_value_rates',
            'nss_title_closing_fees',
            'nss_static_title_fees',
            'nss_zip_county',
        ];

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}{$table}");
        }

        delete_option('nss_db_version');
        delete_option('nss_plugin_activated');
    }
}
