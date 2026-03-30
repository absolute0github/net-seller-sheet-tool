<?php
/**
 * Net Seller Sheet model
 *
 * Handles CRUD operations for sheets
 */
class NSS_Sheet {

    private $id;
    private $user_id;
    private $data = [];

    /**
     * Create new sheet instance or load existing
     *
     * @param int|null $sheet_id
     */
    public function __construct($sheet_id = null) {
        if ($sheet_id) {
            $this->load($sheet_id);
        }
    }

    /**
     * Load sheet from database
     *
     * @param int $sheet_id
     * @return bool
     */
    public function load($sheet_id) {
        global $wpdb;

        $sheet = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}nss_sheets WHERE id = %d",
            (int) $sheet_id
        ), ARRAY_A);

        if (!$sheet) {
            return false;
        }

        $this->id = $sheet['id'];
        $this->user_id = $sheet['user_id'];

        // Decode JSON fields
        if ($sheet['tax_info']) {
            $sheet['tax_info'] = json_decode($sheet['tax_info'], true);
        }
        if ($sheet['commission_structure']) {
            $sheet['commission_structure'] = json_decode($sheet['commission_structure'], true);
        }
        if ($sheet['additional_fees']) {
            $sheet['additional_fees'] = json_decode($sheet['additional_fees'], true);
        }

        $this->data = $sheet;
        return true;
    }

    /**
     * Create new sheet
     *
     * @param int $user_id
     * @param array $data
     * @return int|false Sheet ID or false on failure
     */
    public static function create($user_id, $data) {
        global $wpdb;

        // Prepare data
        $insert_data = [
            'user_id'              => (int) $user_id,
            'property_address'     => sanitize_text_field($data['property_address'] ?? ''),
            'property_city'        => sanitize_text_field($data['property_city'] ?? ''),
            'property_state'       => sanitize_text_field($data['property_state'] ?? ''),
            'property_county'      => sanitize_text_field($data['property_county'] ?? ''),
            'property_zip'         => sanitize_text_field($data['property_zip'] ?? ''),
            'sales_price'          => floatval($data['sales_price'] ?? 0),
            'loan_payoff_1'        => floatval($data['loan_payoff_1'] ?? 0),
            'loan_payoff_2'        => floatval($data['loan_payoff_2'] ?? 0),
            'loan_payoff_3'        => floatval($data['loan_payoff_3'] ?? 0),
            'closing_date'         => sanitize_text_field($data['closing_date'] ?? date('Y-m-d')),
            'hoa_fees'             => floatval($data['hoa_fees'] ?? 0),
            'tax_info'             => json_encode($data['tax_info'] ?? []),
            'commission_structure' => json_encode($data['commission_structure'] ?? []),
            'additional_fees'      => json_encode($data['additional_fees'] ?? []),
        ];

        $formats = ['%d', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%f', '%f', '%s', '%f', '%s', '%s', '%s'];

        $inserted = $wpdb->insert(
            $wpdb->prefix . 'nss_sheets',
            $insert_data,
            $formats
        );

        return $inserted ? $wpdb->insert_id : false;
    }

    /**
     * Update existing sheet
     *
     * @param array $data
     * @return bool
     */
    public function update($data) {
        if (!$this->id) {
            return false;
        }

        global $wpdb;

        $update_data = [
            'property_address'     => sanitize_text_field($data['property_address'] ?? $this->data['property_address']),
            'property_city'        => sanitize_text_field($data['property_city'] ?? $this->data['property_city']),
            'property_state'       => sanitize_text_field($data['property_state'] ?? $this->data['property_state']),
            'property_county'      => sanitize_text_field($data['property_county'] ?? $this->data['property_county']),
            'property_zip'         => sanitize_text_field($data['property_zip'] ?? $this->data['property_zip']),
            'sales_price'          => floatval($data['sales_price'] ?? $this->data['sales_price']),
            'loan_payoff_1'        => floatval($data['loan_payoff_1'] ?? $this->data['loan_payoff_1']),
            'loan_payoff_2'        => floatval($data['loan_payoff_2'] ?? $this->data['loan_payoff_2']),
            'loan_payoff_3'        => floatval($data['loan_payoff_3'] ?? $this->data['loan_payoff_3']),
            'closing_date'         => sanitize_text_field($data['closing_date'] ?? $this->data['closing_date']),
            'hoa_fees'             => floatval($data['hoa_fees'] ?? $this->data['hoa_fees']),
            'tax_info'             => json_encode($data['tax_info'] ?? $this->data['tax_info']),
            'commission_structure' => json_encode($data['commission_structure'] ?? $this->data['commission_structure']),
            'additional_fees'      => json_encode($data['additional_fees'] ?? $this->data['additional_fees']),
        ];

        $formats = ['%s', '%s', '%s', '%s', '%s', '%f', '%f', '%f', '%f', '%s', '%f', '%s', '%s', '%s'];

        $updated = $wpdb->update(
            $wpdb->prefix . 'nss_sheets',
            $update_data,
            ['id' => $this->id],
            $formats,
            ['%d']
        );

        if ($updated !== false) {
            $this->data = array_merge($this->data, $update_data);
            return true;
        }

        return false;
    }

    /**
     * Delete sheet (soft delete)
     *
     * @return bool
     */
    public function delete() {
        if (!$this->id) {
            return false;
        }

        global $wpdb;

        return $wpdb->update(
            $wpdb->prefix . 'nss_sheets',
            ['deleted_at' => current_time('mysql')],
            ['id' => $this->id],
            ['%s'],
            ['%d']
        ) !== false;
    }

    /**
     * Get sheet data
     *
     * @return array
     */
    public function get_data() {
        return $this->data;
    }

    /**
     * Get specific field
     *
     * @param string $field
     * @return mixed
     */
    public function get($field) {
        return $this->data[$field] ?? null;
    }

    /**
     * Get all sheets for user
     *
     * @param int $user_id
     * @param array $args
     * @return array
     */
    public static function get_user_sheets($user_id, $args = []) {
        global $wpdb;

        $limit = $args['limit'] ?? 50;
        $offset = $args['offset'] ?? 0;
        $orderby = $args['orderby'] ?? 'created_at';
        $order = $args['order'] ?? 'DESC';

        $sheets = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}nss_sheets
            WHERE user_id = %d AND deleted_at IS NULL
            ORDER BY {$orderby} {$order}
            LIMIT %d OFFSET %d",
            (int) $user_id,
            (int) $limit,
            (int) $offset
        ), ARRAY_A);

        return $sheets ?: [];
    }

    /**
     * Get all sheets (admin)
     *
     * @param array $args
     * @return array
     */
    public static function get_all_sheets($args = []) {
        global $wpdb;

        $limit = $args['limit'] ?? 50;
        $offset = $args['offset'] ?? 0;
        $orderby = $args['orderby'] ?? 'created_at';
        $order = $args['order'] ?? 'DESC';

        $sheets = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}nss_sheets
            WHERE deleted_at IS NULL
            ORDER BY {$orderby} {$order}
            LIMIT %d OFFSET %d",
            (int) $limit,
            (int) $offset
        ), ARRAY_A);

        return $sheets ?: [];
    }

    /**
     * Get sheet count for user
     *
     * @param int $user_id
     * @return int
     */
    public static function count_user_sheets($user_id) {
        global $wpdb;

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}nss_sheets
            WHERE user_id = %d AND deleted_at IS NULL",
            (int) $user_id
        ));
    }

    /**
     * Calculate net proceeds for sheet
     *
     * @return array Calculation results
     */
    public function calculate() {
        $calculator = new NSS_Calculator($this->data);
        return $calculator->calculate();
    }
}
