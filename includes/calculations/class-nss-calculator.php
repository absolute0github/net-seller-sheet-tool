<?php
/**
 * Main calculator orchestrator
 *
 * Coordinates all financial calculations for net proceeds sheet
 */
class NSS_Calculator {

    private $sheet_data;
    private $results = [];

    public function __construct($sheet_data) {
        $this->sheet_data = $sheet_data;
    }

    /**
     * Execute all calculations
     *
     * @return array Complete calculation results
     */
    public function calculate() {
        $this->calculate_loan_payoffs();
        $this->calculate_conveyance_fees();
        $this->calculate_tax_proration();
        $this->calculate_commission();
        $this->calculate_title_fees();
        $this->calculate_recording_fees();
        $this->calculate_total_deductions();
        $this->calculate_net_proceeds();

        return $this->results;
    }

    /**
     * Calculate total loan payoffs including wire fees
     */
    private function calculate_loan_payoffs() {
        $payoffs = [
            $this->sheet_data['loan_payoff_1'] ?? 0,
            $this->sheet_data['loan_payoff_2'] ?? 0,
            $this->sheet_data['loan_payoff_3'] ?? 0,
        ];

        $total_payoffs = NSS_Precision_Math::sum(
            array_filter($payoffs, fn($v) => $v > 0)
        );

        $wire_fee = $this->sheet_data['wire_fee'] ?? 0;
        $total_loan_costs = NSS_Precision_Math::add($total_payoffs, $wire_fee);

        $this->results['loan_payoffs'] = [
            'payoff_1' => isset($this->sheet_data['loan_payoff_1']) ? (string) $this->sheet_data['loan_payoff_1'] : '0.00',
            'payoff_2' => isset($this->sheet_data['loan_payoff_2']) ? (string) $this->sheet_data['loan_payoff_2'] : '0.00',
            'payoff_3' => isset($this->sheet_data['loan_payoff_3']) ? (string) $this->sheet_data['loan_payoff_3'] : '0.00',
            'wire_fee' => (string) $wire_fee,
            'total' => $total_loan_costs,
        ];
    }

    /**
     * Calculate conveyance (recording) fees by county
     */
    private function calculate_conveyance_fees() {
        global $wpdb;

        $county = $this->sheet_data['property_county'] ?? '';
        $sales_price = $this->sheet_data['sales_price'];

        $fee_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}nss_conveyance_fees
            WHERE county_name = %s AND is_active = 1",
            $county
        ));

        if (!$fee_data) {
            $this->results['conveyance_fees'] = [
                'rate' => 0,
                'amount' => '0.00',
                'seller_percentage' => 1.0,
                'seller_amount' => '0.00',
            ];
            return;
        }

        // Calculate fee
        if ($fee_data->fee_percentage > 0) {
            $total_fee = NSS_Precision_Math::percentage(
                $sales_price,
                $fee_data->fee_percentage * 100
            );
        } else {
            $total_fee = $fee_data->flat_fee;
        }

        // Seller pays percentage
        $seller_amount = NSS_Precision_Math::multiply(
            $total_fee,
            $fee_data->seller_pays_percentage
        );

        $this->results['conveyance_fees'] = [
            'rate' => (float) $fee_data->fee_percentage,
            'amount' => $total_fee,
            'seller_percentage' => (float) $fee_data->seller_pays_percentage,
            'seller_amount' => $seller_amount,
        ];
    }

    /**
     * Calculate prorated taxes
     */
    private function calculate_tax_proration() {
        $county = $this->sheet_data['property_county'] ?? '';
        $closing_date = $this->sheet_data['closing_date'] ?? date('Y-m-d');
        $sales_price = $this->sheet_data['sales_price'];

        $tax_calc = new NSS_Tax_Proration($closing_date, $county, $sales_price);
        $tax_data = $tax_calc->calculate_property_tax();

        $this->results['tax_proration'] = [
            'daily_rate' => $tax_data['daily_rate'],
            'days_owed' => $tax_data['days_owed'],
            'prorated_amount' => $tax_data['prorated_amount'],
        ];
    }

    /**
     * Calculate commission based on tiered property value
     */
    private function calculate_commission() {
        $sales_price = $this->sheet_data['sales_price'];
        $property_value = new NSS_Property_Value($sales_price);
        $commission = $property_value->calculate_commission();

        // Check for custom commission override
        if (!empty($this->sheet_data['custom_commission'])) {
            $commission['amount'] = (string) $this->sheet_data['custom_commission'];
        }

        $this->results['commission'] = $commission;
    }

    /**
     * Calculate all title-related fees
     */
    private function calculate_title_fees() {
        global $wpdb;

        $county = $this->sheet_data['property_county'] ?? '';
        $has_owner_policy = $this->sheet_data['owner_policy'] ?? false;

        // Title closing fee
        $closing_fee = $wpdb->get_var($wpdb->prepare(
            "SELECT fee_amount FROM {$wpdb->prefix}nss_title_closing_fees
            WHERE county_name = %s AND is_active = 1",
            $county
        ));
        $closing_fee = $closing_fee ?? 0;

        // Title exam fee
        $exam_fee = $wpdb->get_var($wpdb->prepare(
            "SELECT fee_amount FROM {$wpdb->prefix}nss_title_exam_fees
            WHERE county_name = %s AND is_active = 1",
            $county
        ));
        $exam_fee = $exam_fee ?? 0;

        // Owner's policy fee (if applicable)
        $owner_policy_fee = '0.00';
        if ($has_owner_policy) {
            // Calculate based on sales price (typically 0.5-1% of sales price)
            $owner_policy_fee = NSS_Precision_Math::percentage($this->sheet_data['sales_price'], 0.5);
        }

        // Static fees
        $static_fees = $wpdb->get_results(
            "SELECT fee_type, fee_amount FROM {$wpdb->prefix}nss_static_title_fees
            WHERE is_active = 1"
        );

        $courier_fee = '0.00';
        $deed_prep_fee = '0.00';
        $wire_transfer_fee = '0.00';

        foreach ($static_fees as $fee) {
            switch ($fee->fee_type) {
                case 'courier':
                    $courier_fee = $fee->fee_amount;
                    break;
                case 'deed_prep':
                    $deed_prep_fee = $fee->fee_amount;
                    break;
                case 'wire_transfer':
                    $wire_transfer_fee = $fee->fee_amount;
                    break;
            }
        }

        $total_title_fees = NSS_Precision_Math::sum([
            $closing_fee,
            $exam_fee,
            $owner_policy_fee,
            $courier_fee,
            $deed_prep_fee,
            $wire_transfer_fee,
        ]);

        $this->results['title_fees'] = [
            'closing_fee' => (string) $closing_fee,
            'exam_fee' => (string) $exam_fee,
            'owner_policy_fee' => $owner_policy_fee,
            'courier_fee' => (string) $courier_fee,
            'deed_prep_fee' => (string) $deed_prep_fee,
            'wire_transfer_fee' => (string) $wire_transfer_fee,
            'total' => $total_title_fees,
        ];
    }

    /**
     * Calculate recording fees
     */
    private function calculate_recording_fees() {
        $num_parcels = $this->sheet_data['num_parcels'] ?? 1;
        $fee_per_parcel = $this->sheet_data['parcel_fee'] ?? 35.00;

        $total = NSS_Precision_Math::multiply($num_parcels, $fee_per_parcel);

        $this->results['recording_fees'] = [
            'num_parcels' => (int) $num_parcels,
            'fee_per_parcel' => (string) $fee_per_parcel,
            'total' => $total,
        ];
    }

    /**
     * Calculate total of all deductions
     */
    private function calculate_total_deductions() {
        $deductions = [
            $this->results['loan_payoffs']['total'],
            $this->results['conveyance_fees']['seller_amount'],
            $this->results['tax_proration']['prorated_amount'],
            $this->results['commission']['amount'],
            $this->results['title_fees']['total'],
            $this->results['recording_fees']['total'],
        ];

        // Add HOA fees
        if (!empty($this->sheet_data['hoa_fees'])) {
            $deductions[] = $this->sheet_data['hoa_fees'];
        }

        // Add additional fees (JSON array)
        if (!empty($this->sheet_data['additional_fees'])) {
            $additional = json_decode($this->sheet_data['additional_fees'], true);
            if (is_array($additional)) {
                foreach ($additional as $fee) {
                    if (isset($fee['amount'])) {
                        $deductions[] = $fee['amount'];
                    }
                }
            }
        }

        $total_deductions = NSS_Precision_Math::sum($deductions);

        $this->results['total_deductions'] = $total_deductions;
    }

    /**
     * Calculate final net proceeds
     */
    private function calculate_net_proceeds() {
        $sales_price = $this->sheet_data['sales_price'];
        $total_deductions = $this->results['total_deductions'];

        $net_proceeds = NSS_Precision_Math::subtract($sales_price, $total_deductions);

        $this->results['net_proceeds'] = [
            'sales_price' => (string) $sales_price,
            'total_deductions' => $total_deductions,
            'net_amount' => $net_proceeds,
        ];
    }

    /**
     * Get calculation results
     *
     * @return array
     */
    public function get_results() {
        return $this->results;
    }

    /**
     * Get formatted results for display
     *
     * @return array
     */
    public function get_formatted_results() {
        $results = $this->results;

        // Format all monetary values
        foreach ($results as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $k => $v) {
                    if (is_numeric($v) && strpos($k, 'rate') === false && strpos($k, 'percentage') === false) {
                        $results[$key][$k] = NSS_Precision_Math::format_currency($v);
                    }
                }
            }
        }

        return $results;
    }
}
