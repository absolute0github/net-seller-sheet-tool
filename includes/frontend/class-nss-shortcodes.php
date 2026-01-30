<?php
/**
 * Frontend shortcodes
 *
 * Handles shortcode registration and rendering
 */
class NSS_Shortcodes {

    public function __construct() {
        add_shortcode('nss_calculator', [$this, 'calculator_shortcode']);
        add_shortcode('nss_my_sheets', [$this, 'my_sheets_shortcode']);
        add_shortcode('nss_sheet', [$this, 'single_sheet_shortcode']);
        add_shortcode('nss_profile', [$this, 'profile_shortcode']);
    }

    /**
     * Main calculator shortcode
     *
     * @param array $atts
     * @return string
     */
    public function calculator_shortcode($atts) {
        // Allow guests to use the calculator (but not save)
        ob_start();
        include NSS_PLUGIN_DIR . 'includes/frontend/templates/calculator.php';
        return ob_get_clean();
    }

    /**
     * My sheets shortcode
     *
     * @param array $atts
     * @return string
     */
    public function my_sheets_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>Please log in to view your sheets.</p>';
        }

        if (!current_user_can('view_nss_sheets')) {
            return '<p>You do not have permission to access this page.</p>';
        }

        $user_id = get_current_user_id();
        $sheets = NSS_Sheet::get_user_sheets($user_id);

        ob_start();
        include NSS_PLUGIN_DIR . 'includes/frontend/templates/my-sheets.php';
        return ob_get_clean();
    }

    /**
     * Single sheet shortcode
     *
     * @param array $atts
     * @return string
     */
    public function single_sheet_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>Please log in to view sheets.</p>';
        }

        $sheet_id = absint($atts['id'] ?? 0);
        if (!$sheet_id) {
            return '<p>Invalid sheet ID.</p>';
        }

        $sheet = new NSS_Sheet($sheet_id);
        if (!$sheet->get_data()) {
            return '<p>Sheet not found.</p>';
        }

        $user_id = get_current_user_id();
        if (!NSS_Security::can_edit_sheet($sheet_id, $user_id)) {
            return '<p>You do not have permission to view this sheet.</p>';
        }

        ob_start();
        include NSS_PLUGIN_DIR . 'includes/frontend/templates/sheet-detail.php';
        return ob_get_clean();
    }

    /**
     * User profile shortcode
     *
     * @param array $atts
     * @return string
     */
    public function profile_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>Please log in to view your profile.</p>';
        }

        ob_start();
        include NSS_PLUGIN_DIR . 'includes/frontend/templates/profile.php';
        return ob_get_clean();
    }
}
