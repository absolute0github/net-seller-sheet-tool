<?php
/**
 * Main plugin orchestrator class
 *
 * Handles initialization of all plugin components, hooks, and features
 */
class NSS_Plugin {

    private static $instance = null;
    private $loader = null;

    /**
     * Singleton pattern - get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor - load dependencies
     */
    public function __construct() {
        $this->load_dependencies();
    }

    /**
     * Load required plugin files
     */
    private function load_dependencies() {
        // Database
        require_once NSS_PLUGIN_DIR . 'includes/database/class-nss-database.php';

        // Models
        require_once NSS_PLUGIN_DIR . 'includes/models/class-nss-sheet.php';
        require_once NSS_PLUGIN_DIR . 'includes/models/class-nss-fee.php';

        // Calculations
        require_once NSS_PLUGIN_DIR . 'includes/calculations/class-nss-precision-math.php';
        require_once NSS_PLUGIN_DIR . 'includes/calculations/class-nss-calculator.php';
        require_once NSS_PLUGIN_DIR . 'includes/calculations/class-nss-tax-proration.php';
        require_once NSS_PLUGIN_DIR . 'includes/calculations/class-nss-property-value.php';

        // Helpers
        require_once NSS_PLUGIN_DIR . 'includes/helpers/class-nss-security.php';
        require_once NSS_PLUGIN_DIR . 'includes/helpers/class-nss-formatting.php';

        // PDF
        require_once NSS_PLUGIN_DIR . 'includes/pdf/class-nss-pdf-generator.php';

        // Admin
        require_once NSS_PLUGIN_DIR . 'includes/admin/class-nss-admin.php';

        // Frontend
        require_once NSS_PLUGIN_DIR . 'includes/frontend/class-nss-shortcodes.php';

        // API
        require_once NSS_PLUGIN_DIR . 'includes/api/class-nss-rest-api.php';

        // Updates
        require_once NSS_PLUGIN_DIR . 'includes/class-nss-updater.php';
    }

    /**
     * Run the plugin
     */
    public function run() {
        // Initialize database
        NSS_Database::initialize();

        // Register custom capabilities
        $this->register_capabilities();

        // Initialize components
        new NSS_Admin();
        new NSS_Shortcodes();
        new NSS_REST_API();
        new NSS_Updater();

        // Action hooks
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('admin_menu', [$this, 'register_admin_menus']);

        // Address autocomplete AJAX handlers
        add_action('wp_ajax_nss_address_autocomplete', [$this, 'ajax_address_autocomplete']);
        add_action('wp_ajax_nss_lookup_county', [$this, 'ajax_lookup_county']);

        // Load text domain
        add_action('plugins_loaded', [$this, 'load_textdomain']);
    }

    /**
     * Register custom capabilities
     */
    private function register_capabilities() {
        $admin_role = get_role('administrator');
        $user_role = get_role('subscriber');

        if (!$admin_role || !$user_role) {
            return;
        }

        // User capabilities
        $user_caps = [
            'view_nss_sheets',
            'create_nss_sheets',
            'edit_nss_sheets',
            'delete_nss_sheets',
            'download_nss_pdf',
        ];

        // Admin capabilities
        $admin_caps = array_merge($user_caps, [
            'manage_nss_fees',
            'view_all_nss_sheets',
            'edit_all_nss_sheets',
            'delete_all_nss_sheets',
            'manage_nss_settings',
        ]);

        // Add capabilities to roles
        foreach ($user_caps as $cap) {
            if (!$user_role->has_cap($cap)) {
                $user_role->add_cap($cap);
            }
        }

        foreach ($admin_caps as $cap) {
            if (!$admin_role->has_cap($cap)) {
                $admin_role->add_cap($cap);
            }
        }
    }

    /**
     * Register admin menus
     */
    public function register_admin_menus() {
        add_menu_page(
            'Net Seller Sheet',
            'Net Seller Sheet',
            'manage_nss_settings',
            'nss-dashboard',
            [$this, 'render_dashboard'],
            'dashicons-calculator',
            30
        );

        add_submenu_page(
            'nss-dashboard',
            'All Sheets',
            'All Sheets',
            'view_all_nss_sheets',
            'nss-sheets',
            [$this, 'render_sheets_page']
        );

        add_submenu_page(
            'nss-dashboard',
            'Fee Configuration',
            'Fee Configuration',
            'manage_nss_fees',
            'nss-fees',
            [$this, 'render_fees_page']
        );

        add_submenu_page(
            'nss-dashboard',
            'Settings',
            'Settings',
            'manage_nss_settings',
            'nss-settings',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Render dashboard page
     */
    public function render_dashboard() {
        include NSS_PLUGIN_DIR . 'includes/admin/pages/dashboard.php';
    }

    /**
     * Render sheets page
     */
    public function render_sheets_page() {
        include NSS_PLUGIN_DIR . 'includes/admin/pages/sheets.php';
    }

    /**
     * Render fees page
     */
    public function render_fees_page() {
        include NSS_PLUGIN_DIR . 'includes/admin/pages/fees.php';
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        include NSS_PLUGIN_DIR . 'includes/admin/pages/settings.php';
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        // Only enqueue on frontend
        if (is_admin()) {
            return;
        }

        // Styles
        wp_enqueue_style(
            'nss-frontend',
            NSS_PLUGIN_URL . 'assets/css/frontend.css',
            [],
            NSS_PLUGIN_VERSION
        );

        // Scripts
        wp_enqueue_script(
            'decimal-js',
            NSS_PLUGIN_URL . 'assets/js/vendor/decimal.min.js',
            [],
            '10.4.3',
            true
        );

        wp_enqueue_script(
            'nss-calculator',
            NSS_PLUGIN_URL . 'assets/js/frontend/calculator.js',
            ['decimal-js', 'jquery'],
            NSS_PLUGIN_VERSION,
            true
        );

        wp_localize_script('nss-calculator', 'nssData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('nss_frontend_nonce'),
        ]);

        // Address autocomplete script (conditional)
        $autocomplete_enabled = get_option('nss_enable_autocomplete', 0);
        $radar_api_key = get_option('nss_radar_api_key', '');

        if ($autocomplete_enabled && !empty($radar_api_key)) {
            wp_enqueue_script(
                'nss-address-autocomplete',
                NSS_PLUGIN_URL . 'assets/js/frontend/address-autocomplete.js',
                ['jquery'],
                NSS_PLUGIN_VERSION,
                true
            );

            wp_localize_script('nss-address-autocomplete', 'nssAutocomplete', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('nss_frontend_nonce'),
                'enabled' => true,
            ]);
        }
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only on NSS admin pages
        if (strpos($hook, 'nss-') === false) {
            return;
        }

        // Styles
        wp_enqueue_style(
            'nss-admin',
            NSS_PLUGIN_URL . 'assets/css/admin.css',
            ['wp-admin'],
            NSS_PLUGIN_VERSION
        );

        // Scripts
        wp_enqueue_script(
            'nss-admin',
            NSS_PLUGIN_URL . 'assets/js/admin/admin.js',
            ['jquery', 'wp-util'],
            NSS_PLUGIN_VERSION,
            true
        );

        wp_localize_script('nss-admin', 'nssAdmin', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('nss_admin_nonce'),
        ]);
    }

    /**
     * Load text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'nss',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }

    /**
     * AJAX handler for address autocomplete
     * Proxies requests to Radar.io API
     */
    public function ajax_address_autocomplete() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'nss_frontend_nonce')) {
            wp_send_json_error(['message' => 'Invalid nonce'], 403);
        }

        $query = sanitize_text_field($_POST['query'] ?? '');
        if (empty($query) || strlen($query) < 3) {
            wp_send_json_error(['message' => 'Query too short']);
        }

        $api_key = get_option('nss_radar_api_key', '');
        if (empty($api_key)) {
            wp_send_json_error(['message' => 'API key not configured']);
        }

        $restrict_ohio = get_option('nss_restrict_ohio', 1);

        // Build Radar.io API request
        $api_url = 'https://api.radar.io/v1/search/autocomplete';
        $params = [
            'query' => $query,
            'limit' => 5,
        ];

        if ($restrict_ohio) {
            $params['country'] = 'US';
            $params['layers'] = 'address';
        }

        $request_url = $api_url . '?' . http_build_query($params);

        $response = wp_remote_get($request_url, [
            'headers' => [
                'Authorization' => $api_key,
            ],
            'timeout' => 10,
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => 'API request failed']);
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!$data || !isset($data['addresses'])) {
            wp_send_json_error(['message' => 'Invalid API response']);
        }

        // Transform addresses for frontend
        $addresses = [];
        foreach ($data['addresses'] as $addr) {
            // Filter to Ohio only if restriction is enabled
            if ($restrict_ohio && isset($addr['state']) && $addr['state'] !== 'OH') {
                continue;
            }

            $addresses[] = [
                'street' => $addr['addressLabel'] ?? $addr['formattedAddress'] ?? '',
                'city' => $addr['city'] ?? '',
                'state' => $addr['state'] ?? '',
                'zip' => $addr['postalCode'] ?? '',
                'county' => $addr['county'] ?? '',
                'formatted_address' => $addr['formattedAddress'] ?? '',
            ];
        }

        wp_send_json_success(['addresses' => $addresses]);
    }

    /**
     * AJAX handler for county lookup by ZIP code
     * Fallback when Radar.io doesn't return county
     */
    public function ajax_lookup_county() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'nss_frontend_nonce')) {
            wp_send_json_error(['message' => 'Invalid nonce'], 403);
        }

        $zip = sanitize_text_field($_POST['zip'] ?? '');
        if (empty($zip) || strlen($zip) < 5) {
            wp_send_json_error(['message' => 'Invalid ZIP code']);
        }

        // Clean ZIP to first 5 digits
        $zip = substr(preg_replace('/[^0-9]/', '', $zip), 0, 5);

        global $wpdb;
        $table_name = $wpdb->prefix . 'nss_zip_county';

        $county = $wpdb->get_var($wpdb->prepare(
            "SELECT county_name FROM {$table_name} WHERE zip_code = %s LIMIT 1",
            $zip
        ));

        if ($county) {
            wp_send_json_success(['county' => $county, 'zip' => $zip]);
        } else {
            wp_send_json_error(['message' => 'County not found for ZIP code']);
        }
    }
}
