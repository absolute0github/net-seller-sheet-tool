<?php
/**
 * Tax proration calculator
 *
 * Calculates prorated taxes based on closing date
 */
class NSS_Tax_Proration {

    private $closing_date;
    private $property_county;
    private $sales_price;

    public function __construct($closing_date, $property_county, $sales_price) {
        $this->closing_date = $closing_date;
        $this->property_county = $property_county;
        $this->sales_price = $sales_price;
    }

    /**
     * Calculate prorated property tax
     *
     * @return array {
     *     'daily_rate': string,
     *     'days_owed': int,
     *     'prorated_amount': string
     * }
     */
    public function calculate_property_tax() {
        global $wpdb;

        // Get tax rate for county
        $tax_data = $wpdb->get_row($wpdb->prepare(
            "SELECT tax_rate FROM {$wpdb->prefix}nss_tax_rates
            WHERE county_name = %s AND is_active = 1",
            $this->property_county
        ));

        if (!$tax_data) {
            return [
                'daily_rate' => '0.00',
                'days_owed' => 0,
                'prorated_amount' => '0.00',
            ];
        }

        $tax_rate = $tax_data->tax_rate;

        // Calculate annual tax
        $annual_tax = NSS_Precision_Math::percentage(
            $this->sales_price,
            $tax_rate * 100
        );

        // Calculate daily rate
        $daily_rate = NSS_Precision_Math::divide($annual_tax, 365);

        // Calculate days owed (from Jan 1 to closing date)
        $days_owed = $this->calculate_days_owed();

        // Calculate prorated amount
        $prorated_amount = NSS_Precision_Math::multiply($daily_rate, $days_owed);

        return [
            'daily_rate' => $daily_rate,
            'days_owed' => $days_owed,
            'prorated_amount' => $prorated_amount,
        ];
    }

    /**
     * Calculate days owed from Jan 1 to closing date
     *
     * @return int
     */
    private function calculate_days_owed() {
        $closing = new DateTime($this->closing_date);
        $year_start = new DateTime('January 1, ' . $closing->format('Y'));

        // Days between Jan 1 and closing date (inclusive of closing date)
        $interval = $year_start->diff($closing);
        return $interval->days + 1;
    }

    /**
     * Get tax proration summary
     *
     * @return array
     */
    public function get_summary() {
        $tax_data = $this->calculate_property_tax();

        return [
            'type' => 'property_tax',
            'rate_percentage' => $this->get_tax_rate(),
            'annual_amount' => $this->get_annual_tax(),
            'daily_rate' => $tax_data['daily_rate'],
            'days_owed' => $tax_data['days_owed'],
            'prorated_amount' => $tax_data['prorated_amount'],
        ];
    }

    /**
     * Get tax rate for county
     *
     * @return string
     */
    private function get_tax_rate() {
        global $wpdb;

        $tax_data = $wpdb->get_row($wpdb->prepare(
            "SELECT tax_rate FROM {$wpdb->prefix}nss_tax_rates
            WHERE county_name = %s AND is_active = 1",
            $this->property_county
        ));

        return $tax_data ? $tax_data->tax_rate : '0';
    }

    /**
     * Get annual tax amount
     *
     * @return string
     */
    private function get_annual_tax() {
        $rate = $this->get_tax_rate();
        return NSS_Precision_Math::percentage($this->sales_price, $rate * 100);
    }
}
