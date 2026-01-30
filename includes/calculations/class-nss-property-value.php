<?php
/**
 * Property value tier calculator
 *
 * Handles tiered pricing structure based on property value
 */
class NSS_Property_Value {

    private $sales_price;

    public function __construct($sales_price) {
        $this->sales_price = $sales_price;
    }

    /**
     * Get applicable tier for sales price
     *
     * @return object|null
     */
    public function get_tier() {
        global $wpdb;

        $tier = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}nss_property_value_rates
            WHERE min_price <= %f AND max_price > %f AND is_active = 1
            LIMIT 1",
            $this->sales_price,
            $this->sales_price
        ));

        return $tier;
    }

    /**
     * Calculate commission based on tiered rate
     *
     * @return array {
     *     'tier_name': string,
     *     'rate': float,
     *     'amount': string
     * }
     */
    public function calculate_commission() {
        $tier = $this->get_tier();

        if (!$tier) {
            return [
                'tier_name' => 'Unknown',
                'rate' => 0,
                'amount' => '0.00',
            ];
        }

        // rate is stored as whole number (e.g., 3 = 3%)
        $commission = NSS_Precision_Math::percentage(
            $this->sales_price,
            $tier->rate
        );

        return [
            'tier_name' => $tier->tier_name,
            'rate' => (float) $tier->rate,
            'amount' => $commission,
        ];
    }

    /**
     * Get all tiers
     *
     * @return array
     */
    public static function get_all_tiers() {
        global $wpdb;

        $tiers = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}nss_property_value_rates
            WHERE is_active = 1
            ORDER BY min_price ASC"
        );

        return $tiers ?: [];
    }

    /**
     * Update a tier
     *
     * @param int $tier_id
     * @param array $data
     * @return bool
     */
    public static function update_tier($tier_id, $data) {
        global $wpdb;

        $updated = $wpdb->update(
            $wpdb->prefix . 'nss_property_value_rates',
            [
                'tier_name' => sanitize_text_field($data['tier_name']),
                'min_price' => floatval($data['min_price']),
                'max_price' => floatval($data['max_price']),
                'rate' => floatval($data['rate']),
                'is_active' => isset($data['is_active']) ? 1 : 0,
                'updated_at' => current_time('mysql'),
            ],
            ['id' => (int) $tier_id],
            ['%s', '%f', '%f', '%f', '%d', '%s'],
            ['%d']
        );

        return $updated !== false;
    }

    /**
     * Create new tier
     *
     * @param array $data
     * @return int|false
     */
    public static function create_tier($data) {
        global $wpdb;

        $inserted = $wpdb->insert(
            $wpdb->prefix . 'nss_property_value_rates',
            [
                'tier_name' => sanitize_text_field($data['tier_name']),
                'min_price' => floatval($data['min_price']),
                'max_price' => floatval($data['max_price']),
                'rate' => floatval($data['rate']),
                'is_active' => isset($data['is_active']) ? 1 : 0,
                'created_at' => current_time('mysql'),
            ],
            ['%s', '%f', '%f', '%f', '%d', '%s']
        );

        return $inserted ? $wpdb->insert_id : false;
    }

    /**
     * Delete tier
     *
     * @param int $tier_id
     * @return bool
     */
    public static function delete_tier($tier_id) {
        global $wpdb;

        return $wpdb->delete(
            $wpdb->prefix . 'nss_property_value_rates',
            ['id' => (int) $tier_id],
            ['%d']
        ) !== false;
    }
}
