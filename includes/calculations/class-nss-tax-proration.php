<?php
/**
 * Tax proration calculator
 *
 * Calculates prorated taxes based on user-supplied annual tax amount and closing date.
 * Returns two deduction line items:
 *   1. prorated_amount — taxes owed from Jan 1 to closing date
 *   2. tax_hold       — 50% of annual taxes (property tax currently due holdback)
 */
class NSS_Tax_Proration {

    private $closing_date;
    private $annual_taxes;

    public function __construct($closing_date, $annual_taxes) {
        $this->closing_date  = $closing_date;
        $this->annual_taxes  = $annual_taxes;
    }

    /**
     * Calculate prorated property tax and 50% holdback
     *
     * @return array {
     *     'annual_taxes':    string,
     *     'daily_rate':      string,
     *     'days_owed':       int,
     *     'prorated_amount': string,
     *     'tax_hold':        string
     * }
     */
    public function calculate_property_tax() {
        $annual_taxes = floatval($this->annual_taxes);

        if ($annual_taxes <= 0) {
            return [
                'annual_taxes'    => '0.00',
                'daily_rate'      => '0.00',
                'days_owed'       => 0,
                'prorated_amount' => '0.00',
                'tax_hold'        => '0.00',
            ];
        }

        // Line 1: Tax Proration = (annual_taxes / 365) × days from Jan 1 to closing
        $daily_rate      = NSS_Precision_Math::divide($annual_taxes, 365);
        $days_owed       = $this->calculate_days_owed();
        $prorated_amount = NSS_Precision_Math::multiply($daily_rate, $days_owed);

        // Line 2: Property Tax Hold = 50% of annual taxes
        $tax_hold = NSS_Precision_Math::multiply($annual_taxes, '0.50');

        return [
            'annual_taxes'    => number_format($annual_taxes, 2, '.', ''),
            'daily_rate'      => $daily_rate,
            'days_owed'       => $days_owed,
            'prorated_amount' => $prorated_amount,
            'tax_hold'        => $tax_hold,
        ];
    }

    /**
     * Calculate days owed from Jan 1 to closing date (inclusive)
     *
     * @return int
     */
    private function calculate_days_owed() {
        $closing    = new DateTime($this->closing_date);
        $year_start = new DateTime('January 1, ' . $closing->format('Y'));

        $interval = $year_start->diff($closing);
        return $interval->days + 1;
    }
}
