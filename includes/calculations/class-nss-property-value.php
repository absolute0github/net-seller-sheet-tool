<?php
/**
 * Title insurance calculator
 *
 * Calculates Ohio OTIRB Standard Owner's Policy premium using a cumulative
 * tiered rate structure. The rate column in nss_property_value_rates stores
 * dollars per $1,000 of coverage (e.g., 5.80 = $5.80 per $1,000).
 */
class NSS_Property_Value {

    private $sales_price;

    public function __construct($sales_price) {
        $this->sales_price = $sales_price;
    }

    /**
     * Calculate Ohio OTIRB title insurance premium (cumulative tiered)
     *
     * Coverage is rounded up to the nearest $1,000.
     * Each tier's rate applies only to the portion of coverage within that tier.
     * Minimum premium is $225.
     *
     * @param float|string $sales_price
     * @return array {
     *     'premium':   string,
     *     'breakdown': array
     * }
     */
    public function calculate_title_insurance($sales_price) {
        $tiers = self::get_all_tiers();

        if (empty($tiers)) {
            return ['premium' => '225.00', 'breakdown' => []];
        }

        // Round coverage up to nearest $1,000
        $coverage  = (float) ceil(floatval($sales_price) / 1000) * 1000;
        $remaining = $coverage;

        $total_premium = '0.00';
        $breakdown     = [];

        foreach ($tiers as $tier) {
            if ($remaining <= 0) {
                break;
            }

            $tier_min = floatval($tier->min_price);
            $tier_max = floatval($tier->max_price);
            $rate     = floatval($tier->rate);

            $tier_capacity = $tier_max - $tier_min;
            $tier_coverage = min($remaining, $tier_capacity);

            if ($tier_coverage <= 0) {
                continue;
            }

            $thousands    = NSS_Precision_Math::divide($tier_coverage, 1000);
            $tier_premium = NSS_Precision_Math::multiply($thousands, $rate);

            $total_premium = NSS_Precision_Math::add($total_premium, $tier_premium);

            $breakdown[] = [
                'tier_name' => $tier->tier_name,
                'coverage'  => $tier_coverage,
                'rate'      => $rate,
                'premium'   => $tier_premium,
            ];

            $remaining -= $tier_coverage;
        }

        // Apply $225 minimum premium
        if (floatval($total_premium) < 225.00) {
            $total_premium = '225.00';
        }

        return [
            'premium'   => $total_premium,
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Get all active tiers ordered by min_price ASC
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
                'tier_name'  => sanitize_text_field($data['tier_name']),
                'min_price'  => floatval($data['min_price']),
                'max_price'  => floatval($data['max_price']),
                'rate'       => floatval($data['rate']),
                'is_active'  => isset($data['is_active']) ? 1 : 0,
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
                'tier_name'  => sanitize_text_field($data['tier_name']),
                'min_price'  => floatval($data['min_price']),
                'max_price'  => floatval($data['max_price']),
                'rate'       => floatval($data['rate']),
                'is_active'  => isset($data['is_active']) ? 1 : 0,
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
