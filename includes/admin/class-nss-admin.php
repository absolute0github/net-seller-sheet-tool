<?php
/**
 * Admin interface handler
 *
 * Manages admin pages, AJAX handlers, and UI components
 */
class NSS_Admin {

    public function __construct() {
        // Admin AJAX handlers
        add_action('wp_ajax_nss_create_fee', [$this, 'ajax_create_fee']);
        add_action('wp_ajax_nss_update_fee', [$this, 'ajax_update_fee']);
        add_action('wp_ajax_nss_delete_fee', [$this, 'ajax_delete_fee']);
        add_action('wp_ajax_nss_toggle_fee_status', [$this, 'ajax_toggle_fee_status']);
        add_action('wp_ajax_nss_get_stats', [$this, 'ajax_get_stats']);
    }

    /**
     * Create fee via AJAX
     */
    public function ajax_create_fee() {
        if (!NSS_Security::verify_nonce('_nonce', 'nss_admin_nonce')) {
            wp_send_json_error('Unauthorized', 403);
        }

        if (!NSS_Security::check_capability('manage_nss_fees')) {
            wp_send_json_error('Insufficient permissions', 403);
        }

        $fee_type = sanitize_text_field($_POST['fee_type'] ?? '');
        $data = $this->get_post_data();

        if (empty($fee_type)) {
            wp_send_json_error('Fee type is required');
        }

        $sanitized = NSS_Security::sanitize_fee_data($data, $fee_type);
        $errors = $this->validate_fee_data($sanitized, $fee_type);

        if (!empty($errors)) {
            wp_send_json_error($errors);
        }

        $fee = new NSS_Fee($fee_type);
        $id = $fee->save($sanitized);

        if ($id) {
            wp_send_json_success(['id' => $id, 'message' => 'Fee created successfully']);
        } else {
            wp_send_json_error('Failed to create fee');
        }
    }

    /**
     * Update fee via AJAX
     */
    public function ajax_update_fee() {
        if (!NSS_Security::verify_nonce('_nonce', 'nss_admin_nonce')) {
            wp_send_json_error('Unauthorized', 403);
        }

        if (!NSS_Security::check_capability('manage_nss_fees')) {
            wp_send_json_error('Insufficient permissions', 403);
        }

        $fee_id = intval($_POST['fee_id'] ?? 0);
        $fee_type = sanitize_text_field($_POST['fee_type'] ?? '');
        $data = $this->get_post_data();

        if ($fee_id <= 0 || empty($fee_type)) {
            wp_send_json_error('Invalid fee ID or type');
        }

        $sanitized = NSS_Security::sanitize_fee_data($data, $fee_type);
        $errors = $this->validate_fee_data($sanitized, $fee_type);

        if (!empty($errors)) {
            wp_send_json_error($errors);
        }

        $fee = new NSS_Fee($fee_type, $fee_id);
        if ($fee->save($sanitized)) {
            wp_send_json_success(['message' => 'Fee updated successfully']);
        } else {
            wp_send_json_error('Failed to update fee');
        }
    }

    /**
     * Delete fee via AJAX
     */
    public function ajax_delete_fee() {
        if (!NSS_Security::verify_nonce('_nonce', 'nss_admin_nonce')) {
            wp_send_json_error('Unauthorized', 403);
        }

        if (!NSS_Security::check_capability('manage_nss_fees')) {
            wp_send_json_error('Insufficient permissions', 403);
        }

        $fee_id = intval($_POST['fee_id'] ?? 0);
        $fee_type = sanitize_text_field($_POST['fee_type'] ?? '');

        if ($fee_id <= 0 || empty($fee_type)) {
            wp_send_json_error('Invalid fee ID or type');
        }

        $fee = new NSS_Fee($fee_type, $fee_id);
        if ($fee->delete()) {
            wp_send_json_success(['message' => 'Fee deleted successfully']);
        } else {
            wp_send_json_error('Failed to delete fee');
        }
    }

    /**
     * Toggle fee status via AJAX
     */
    public function ajax_toggle_fee_status() {
        if (!NSS_Security::verify_nonce('_nonce', 'nss_admin_nonce')) {
            wp_send_json_error('Unauthorized', 403);
        }

        if (!NSS_Security::check_capability('manage_nss_fees')) {
            wp_send_json_error('Insufficient permissions', 403);
        }

        $fee_id = intval($_POST['fee_id'] ?? 0);
        $fee_type = sanitize_text_field($_POST['fee_type'] ?? '');

        if ($fee_id <= 0 || empty($fee_type)) {
            wp_send_json_error('Invalid fee ID or type');
        }

        $fee = new NSS_Fee($fee_type, $fee_id);
        if ($fee->toggle_active()) {
            wp_send_json_success(['message' => 'Fee status updated']);
        } else {
            wp_send_json_error('Failed to update fee status');
        }
    }

    /**
     * Get dashboard statistics via AJAX
     */
    public function ajax_get_stats() {
        if (!NSS_Security::verify_nonce('_nonce', 'nss_admin_nonce')) {
            wp_send_json_error('Unauthorized', 403);
        }

        if (!NSS_Security::check_capability('view_all_nss_sheets')) {
            wp_send_json_error('Insufficient permissions', 403);
        }

        global $wpdb;

        $stats = [
            'total_sheets' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}nss_sheets WHERE deleted_at IS NULL"),
            'total_users' => (int) $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}nss_sheets WHERE deleted_at IS NULL"),
            'total_sales_price' => $wpdb->get_var("SELECT SUM(sales_price) FROM {$wpdb->prefix}nss_sheets WHERE deleted_at IS NULL"),
            'total_net_proceeds' => $wpdb->get_var("SELECT SUM(net_proceeds) FROM {$wpdb->prefix}nss_sheets WHERE deleted_at IS NULL"),
        ];

        wp_send_json_success($stats);
    }

    /**
     * Get post data
     *
     * @return array
     */
    private function get_post_data() {
        return isset($_POST) ? wp_unslash($_POST) : [];
    }

    /**
     * Validate fee data
     *
     * @param array $data
     * @param string $fee_type
     * @return array Errors
     */
    private function validate_fee_data($data, $fee_type) {
        $errors = [];

        switch ($fee_type) {
            case 'conveyance':
                if (empty($data['county_name'])) {
                    $errors['county_name'] = 'County name is required';
                }
                if ($data['fee_percentage'] <= 0 && $data['flat_fee'] <= 0) {
                    $errors['fees'] = 'Either percentage or flat fee must be greater than 0';
                }
                break;

            case 'tax_rate':
                if (empty($data['county_name'])) {
                    $errors['county_name'] = 'County name is required';
                }
                if ($data['tax_rate'] < 0) {
                    $errors['tax_rate'] = 'Tax rate must be >= 0';
                }
                break;

            case 'property_value':
                if (empty($data['tier_name'])) {
                    $errors['tier_name'] = 'Tier name is required';
                }
                if ($data['min_price'] < 0 || $data['max_price'] < 0) {
                    $errors['price_range'] = 'Prices must be >= 0';
                }
                if ($data['min_price'] >= $data['max_price']) {
                    $errors['price_range'] = 'Min price must be less than max price';
                }
                if ($data['rate'] < 0) {
                    $errors['rate'] = 'Rate must be >= 0';
                }
                break;

            case 'title_closing':
            case 'title_exam':
                if (empty($data['county_name'])) {
                    $errors['county_name'] = 'County name is required';
                }
                if ($data['fee_amount'] < 0) {
                    $errors['fee_amount'] = 'Fee must be >= 0';
                }
                break;

            case 'static_fee':
                if (empty($data['fee_type'])) {
                    $errors['fee_type'] = 'Fee type is required';
                }
                if ($data['fee_amount'] < 0) {
                    $errors['fee_amount'] = 'Fee must be >= 0';
                }
                break;
        }

        return $errors;
    }
}
