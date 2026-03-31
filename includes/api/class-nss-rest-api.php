<?php
/**
 * REST API endpoints
 *
 * Handles all REST API routes and endpoints
 */
class NSS_REST_API {

    const NAMESPACE = 'nss/v1';

    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);

        // Register AJAX endpoints
        add_action('wp_ajax_nss_calculate', [$this, 'ajax_calculate']);
        add_action('wp_ajax_nopriv_nss_calculate', [$this, 'ajax_calculate']); // Allow guests to calculate
        add_action('wp_ajax_nss_save_sheet', [$this, 'ajax_save_sheet']);
        add_action('wp_ajax_nss_delete_sheet', [$this, 'ajax_delete_sheet']);
        add_action('wp_ajax_nss_download_pdf', [$this, 'ajax_download_pdf']);
        add_action('wp_ajax_nss_update_profile', [$this, 'ajax_update_profile']);
    }

    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Sheets endpoints
        register_rest_route(self::NAMESPACE, '/sheets', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_sheets'],
                'permission_callback' => [$this, 'check_sheets_permission'],
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'create_sheet'],
                'permission_callback' => [$this, 'check_create_sheet_permission'],
            ],
        ]);

        // Single sheet endpoints
        register_rest_route(self::NAMESPACE, '/sheets/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_sheet'],
                'permission_callback' => [$this, 'check_sheet_permission'],
            ],
            [
                'methods' => 'PATCH',
                'callback' => [$this, 'update_sheet'],
                'permission_callback' => [$this, 'check_sheet_permission'],
            ],
            [
                'methods' => 'DELETE',
                'callback' => [$this, 'delete_sheet'],
                'permission_callback' => [$this, 'check_sheet_permission'],
            ],
        ]);

        // PDF endpoint
        register_rest_route(self::NAMESPACE, '/sheets/(?P<id>\d+)/pdf', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_sheet_pdf'],
                'permission_callback' => [$this, 'check_sheet_permission'],
            ],
        ]);

        // Profile endpoint
        register_rest_route(self::NAMESPACE, '/profile', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_profile'],
                'permission_callback' => [$this, 'check_profile_permission'],
            ],
            [
                'methods' => 'PATCH',
                'callback' => [$this, 'update_profile'],
                'permission_callback' => [$this, 'check_profile_permission'],
            ],
        ]);
    }

    /**
     * Get user's sheets
     */
    public function get_sheets($request) {
        $user_id = get_current_user_id();
        $sheets = NSS_Sheet::get_user_sheets($user_id);

        return new WP_REST_Response($sheets, 200);
    }

    /**
     * Get single sheet
     */
    public function get_sheet($request) {
        $sheet_id = (int) $request['id'];
        $sheet = new NSS_Sheet($sheet_id);
        $data = $sheet->get_data();

        if (!$data) {
            return new WP_Error('not_found', 'Sheet not found', ['status' => 404]);
        }

        return new WP_REST_Response($data, 200);
    }

    /**
     * Create new sheet
     */
    public function create_sheet($request) {
        $user_id = get_current_user_id();
        $params = $request->get_json_params();

        $sanitized = NSS_Security::sanitize_sheet_data($params);
        $errors = NSS_Security::validate_sheet_data($sanitized);

        if (!empty($errors)) {
            return new WP_Error('validation_failed', 'Validation failed', ['status' => 400, 'errors' => $errors]);
        }

        $sheet_id = NSS_Sheet::create($user_id, $sanitized);

        if (!$sheet_id) {
            return new WP_Error('save_failed', 'Failed to save sheet', ['status' => 500]);
        }

        $sheet = new NSS_Sheet($sheet_id);
        return new WP_REST_Response(['id' => $sheet_id, 'data' => $sheet->get_data()], 201);
    }

    /**
     * Update sheet
     */
    public function update_sheet($request) {
        $sheet_id = (int) $request['id'];
        $sheet = new NSS_Sheet($sheet_id);

        if (!$sheet->get_data()) {
            return new WP_Error('not_found', 'Sheet not found', ['status' => 404]);
        }

        $params = $request->get_json_params();
        if (!$sheet->update($params)) {
            return new WP_Error('save_failed', 'Failed to update sheet', ['status' => 500]);
        }

        return new WP_REST_Response($sheet->get_data(), 200);
    }

    /**
     * Delete sheet
     */
    public function delete_sheet($request) {
        $sheet_id = (int) $request['id'];
        $sheet = new NSS_Sheet($sheet_id);

        if (!$sheet->get_data()) {
            return new WP_Error('not_found', 'Sheet not found', ['status' => 404]);
        }

        if (!$sheet->delete()) {
            return new WP_Error('delete_failed', 'Failed to delete sheet', ['status' => 500]);
        }

        return new WP_REST_Response(['message' => 'Sheet deleted'], 200);
    }

    /**
     * Get sheet PDF
     */
    public function get_sheet_pdf($request) {
        $sheet_id = (int) $request['id'];
        $sheet = new NSS_Sheet($sheet_id);

        if (!$sheet->get_data()) {
            return new WP_Error('not_found', 'Sheet not found', ['status' => 404]);
        }

        // Generate PDF
        $generator = new NSS_PDF_Generator($sheet);
        $pdf_path = $generator->generate();

        if (!$pdf_path) {
            return new WP_Error('pdf_failed', 'Failed to generate PDF', ['status' => 500]);
        }

        return new WP_REST_Response(['pdf_url' => $pdf_path], 200);
    }

    /**
     * Get user profile
     */
    public function get_profile($request) {
        $user_id = get_current_user_id();
        $user = get_user_by('id', $user_id);

        return new WP_REST_Response([
            'id' => $user_id,
            'email' => $user->user_email,
            'username' => $user->user_login,
            'company_name' => get_user_meta($user_id, 'nss_company', true),
            'company_logo' => get_user_meta($user_id, 'nss_company_logo_url', true),
        ], 200);
    }

    /**
     * Update user profile
     */
    public function update_profile($request) {
        $user_id = get_current_user_id();
        $params = $request->get_json_params();

        if (isset($params['company_name'])) {
            update_user_meta($user_id, 'nss_company', sanitize_text_field($params['company_name']));
        }

        return new WP_REST_Response(['message' => 'Profile updated'], 200);
    }

    // Permission callbacks

    public function check_sheets_permission() {
        return current_user_can('view_nss_sheets');
    }

    public function check_create_sheet_permission() {
        return current_user_can('create_nss_sheets');
    }

    public function check_sheet_permission() {
        return is_user_logged_in();
    }

    public function check_profile_permission() {
        return is_user_logged_in();
    }

    // AJAX handlers

    public function ajax_calculate() {
        check_ajax_referer('nss_frontend_nonce', 'nonce');

        // Allow guests to calculate (no capability check needed)
        $data      = json_decode(stripslashes($_POST['sheet_data']), true);
        $sanitized = NSS_Security::sanitize_sheet_data($data);
        $calculator = new NSS_Calculator($sanitized);
        $results = $calculator->calculate();

        wp_send_json_success($results);
    }

    public function ajax_save_sheet() {
        check_ajax_referer('nss_frontend_nonce', 'nonce');

        if (!current_user_can('create_nss_sheets')) {
            wp_send_json_error(['message' => 'Permission denied. Please ensure you are logged in.'], 403);
        }

        $user_id = get_current_user_id();
        $data = json_decode(stripslashes($_POST['sheet_data']), true);

        $sanitized = NSS_Security::sanitize_sheet_data($data);
        $sheet_id = NSS_Sheet::create($user_id, $sanitized);

        if ($sheet_id) {
            wp_send_json_success(['id' => $sheet_id]);
        } else {
            wp_send_json_error(['message' => 'Failed to save sheet']);
        }
    }

    public function ajax_delete_sheet() {
        check_ajax_referer('nss_frontend_nonce', 'nonce');

        $sheet_id = intval($_POST['sheet_id']);
        $user_id = get_current_user_id();

        if (!NSS_Security::can_delete_sheet($sheet_id, $user_id)) {
            wp_send_json_error('Permission denied', 403);
        }

        $sheet = new NSS_Sheet($sheet_id);
        if ($sheet->delete()) {
            wp_send_json_success(['message' => 'Sheet deleted']);
        } else {
            wp_send_json_error('Failed to delete sheet');
        }
    }

    public function ajax_download_pdf() {
        $sheet_id = intval($_GET['sheet_id'] ?? 0);
        $user_id = get_current_user_id();

        if (!$sheet_id) {
            wp_die('No sheet ID provided. Please save the sheet first.');
        }

        if (!$user_id) {
            wp_die('You must be logged in to download PDFs.');
        }

        if (!current_user_can('download_nss_pdf')) {
            wp_die('You do not have permission to download PDFs. Please contact the administrator.');
        }

        // Check sheet ownership
        global $wpdb;
        $sheet_owner = $wpdb->get_var($wpdb->prepare(
            "SELECT user_id FROM {$wpdb->prefix}nss_sheets WHERE id = %d",
            $sheet_id
        ));

        if (!$sheet_owner) {
            wp_die('Sheet not found.');
        }

        if ($sheet_owner != $user_id && !current_user_can('edit_all_nss_sheets')) {
            wp_die('You can only download your own sheets.');
        }

        $sheet = new NSS_Sheet($sheet_id);
        $generator = new NSS_PDF_Generator($sheet);
        $generator->download();
    }

    public function ajax_update_profile() {
        check_ajax_referer('nss_profile_nonce', 'nonce');

        $user_id = get_current_user_id();
        $data = wp_unslash($_POST);

        if (isset($data['company_name'])) {
            update_user_meta($user_id, 'nss_company', sanitize_text_field($data['company_name']));
        }

        if (isset($_FILES['company_logo'])) {
            $attachment_id = media_handle_upload('company_logo', 0);
            if (!is_wp_error($attachment_id)) {
                update_user_meta($user_id, 'nss_company_logo_id', $attachment_id);
                $attachment_url = wp_get_attachment_url($attachment_id);
                update_user_meta($user_id, 'nss_company_logo_url', $attachment_url);
            }
        }

        wp_send_json_success(['message' => 'Profile updated']);
    }
}
