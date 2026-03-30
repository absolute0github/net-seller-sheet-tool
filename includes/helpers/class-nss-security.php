<?php
/**
 * Security helper class
 *
 * Handles sanitization, validation, and security checks
 */
class NSS_Security {

    /**
     * Verify nonce
     *
     * @param string $nonce_field
     * @param string $action
     * @return bool
     */
    public static function verify_nonce($nonce_field = '_nonce', $action = '') {
        if (empty($_REQUEST[$nonce_field])) {
            return false;
        }

        return wp_verify_nonce($_REQUEST[$nonce_field], $action) !== false;
    }

    /**
     * Check user capability
     *
     * @param string $capability
     * @return bool
     */
    public static function check_capability($capability) {
        return current_user_can($capability);
    }

    /**
     * Check if user can edit sheet
     *
     * @param int $sheet_id
     * @param int $user_id
     * @return bool
     */
    public static function can_edit_sheet($sheet_id, $user_id) {
        if (!current_user_can('edit_nss_sheets')) {
            return false;
        }

        global $wpdb;

        $sheet = $wpdb->get_row($wpdb->prepare(
            "SELECT user_id FROM {$wpdb->prefix}nss_sheets WHERE id = %d",
            (int) $sheet_id
        ));

        if (!$sheet) {
            return false;
        }

        // Admin can edit all, user can only edit own
        return current_user_can('edit_all_nss_sheets') || $sheet->user_id == $user_id;
    }

    /**
     * Check if user can delete sheet
     *
     * @param int $sheet_id
     * @param int $user_id
     * @return bool
     */
    public static function can_delete_sheet($sheet_id, $user_id) {
        if (!current_user_can('delete_nss_sheets')) {
            return false;
        }

        global $wpdb;

        $sheet = $wpdb->get_row($wpdb->prepare(
            "SELECT user_id FROM {$wpdb->prefix}nss_sheets WHERE id = %d",
            (int) $sheet_id
        ));

        if (!$sheet) {
            return false;
        }

        return current_user_can('delete_all_nss_sheets') || $sheet->user_id == $user_id;
    }

    /**
     * Sanitize sheet data
     *
     * @param array $data
     * @return array
     */
    public static function sanitize_sheet_data($data) {
        return [
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
            'tax_info'             => [
                'annual_taxes' => floatval($data['annual_taxes'] ?? 0),
            ],
            'commission_structure' => [
                'agent_commission_percentage' => floatval($data['agent_commission_percentage'] ?? 0),
            ],
        ];
    }

    /**
     * Sanitize fee data
     *
     * @param array $data
     * @param string $fee_type
     * @return array
     */
    public static function sanitize_fee_data($data, $fee_type) {
        switch ($fee_type) {
            case 'conveyance':
                return [
                    'county_name' => sanitize_text_field($data['county_name'] ?? ''),
                    'state' => sanitize_text_field($data['state'] ?? ''),
                    'fee_percentage' => floatval($data['fee_percentage'] ?? 0),
                    'flat_fee' => floatval($data['flat_fee'] ?? 0),
                    'seller_pays_percentage' => floatval($data['seller_pays_percentage'] ?? 1.0),
                    'is_active' => isset($data['is_active']) ? 1 : 0,
                ];

            case 'tax_rate':
                return [
                    'county_name' => sanitize_text_field($data['county_name'] ?? ''),
                    'state' => sanitize_text_field($data['state'] ?? ''),
                    'tax_rate' => floatval($data['tax_rate'] ?? 0),
                    'tax_type' => in_array($data['tax_type'] ?? '', ['property_tax', 'sales_tax']) ? $data['tax_type'] : 'property_tax',
                    'is_active' => isset($data['is_active']) ? 1 : 0,
                ];

            case 'property_value':
                return [
                    'tier_name' => sanitize_text_field($data['tier_name'] ?? ''),
                    'min_price' => floatval($data['min_price'] ?? 0),
                    'max_price' => floatval($data['max_price'] ?? 0),
                    'rate' => floatval($data['rate'] ?? 0),
                    'is_active' => isset($data['is_active']) ? 1 : 0,
                ];

            case 'title_closing':
            case 'title_exam':
                return [
                    'county_name' => sanitize_text_field($data['county_name'] ?? ''),
                    'state' => sanitize_text_field($data['state'] ?? ''),
                    'fee_amount' => floatval($data['fee_amount'] ?? 0),
                    'is_active' => isset($data['is_active']) ? 1 : 0,
                ];

            case 'static_fee':
                $static_type = $data['static_fee_type'] ?? $data['fee_type'] ?? '';
                return [
                    'fee_type' => in_array($static_type, ['courier', 'deed_prep', 'wire_transfer']) ? $static_type : '',
                    'fee_amount' => floatval($data['fee_amount'] ?? 0),
                    'is_active' => isset($data['is_active']) ? 1 : 0,
                ];

            default:
                return [];
        }
    }

    /**
     * Validate sheet data
     *
     * @param array $data
     * @return array Array of errors, empty if valid
     */
    public static function validate_sheet_data($data) {
        $errors = [];

        if (empty($data['property_address'])) {
            $errors['property_address'] = 'Property address is required';
        }

        if (empty($data['property_county'])) {
            $errors['property_county'] = 'County is required';
        }

        if (floatval($data['sales_price'] ?? 0) <= 0) {
            $errors['sales_price'] = 'Sales price must be greater than 0';
        }

        if (!empty($data['closing_date'])) {
            if (!strtotime($data['closing_date'])) {
                $errors['closing_date'] = 'Invalid closing date';
            }
        }

        return $errors;
    }

    /**
     * Escape text for HTML output
     *
     * @param string $text
     * @return string
     */
    public static function escape_text($text) {
        return esc_html($text);
    }

    /**
     * Escape URL
     *
     * @param string $url
     * @return string
     */
    public static function escape_url($url) {
        return esc_url($url);
    }

    /**
     * Escape attribute
     *
     * @param string $attr
     * @return string
     */
    public static function escape_attr($attr) {
        return esc_attr($attr);
    }
}
