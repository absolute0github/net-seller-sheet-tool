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
            property_state VARCHAR(2),
            property_county VARCHAR(100),
            property_zip VARCHAR(10),
            sales_price DECIMAL(18,2) NOT NULL,
            loan_payoff_1 DECIMAL(18,2),
            loan_payoff_2 DECIMAL(18,2),
            loan_payoff_3 DECIMAL(18,2),
            wire_fee DECIMAL(18,2),
            closing_date DATE,
            tax_info JSON,
            commission_structure JSON,
            hoa_fees DECIMAL(18,2),
            additional_fees JSON,
            owner_policy TINYINT(1) DEFAULT 0,
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

        // 3. Tax rates table
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}nss_tax_rates (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            county_name VARCHAR(100) NOT NULL,
            state VARCHAR(2),
            tax_rate DECIMAL(8,4) NOT NULL,
            tax_type ENUM('property_tax', 'sales_tax') DEFAULT 'property_tax',
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY county_name (county_name),
            KEY is_active (is_active)
        ) $charset_collate;";

        // 4. Property value rates (tiered pricing)
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

        // 5. Title closing fees table
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

        // 6. Title exam fees table
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}nss_title_exam_fees (
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

        // 7. Static title fees table (courier, deed prep, wire)
        $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}nss_static_title_fees (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            fee_type ENUM('courier', 'deed_prep', 'wire_transfer') NOT NULL UNIQUE,
            fee_amount DECIMAL(18,2) NOT NULL,
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY is_active (is_active)
        ) $charset_collate;";

        // Execute all SQL statements
        foreach ($sql as $statement) {
            dbDelta($statement);
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
            'nss_tax_rates',
            'nss_property_value_rates',
            'nss_title_closing_fees',
            'nss_title_exam_fees',
            'nss_static_title_fees',
        ];

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}{$table}");
        }

        delete_option('nss_db_version');
        delete_option('nss_plugin_activated');
    }
}
