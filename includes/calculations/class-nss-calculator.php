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
        $this->calculate_total_deductions();
        $this->calculate_net_proceeds();

        return $this->results;
    }

    /**
     * Calculate total loan payoffs
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

        $this->results['loan_payoffs'] = [
            'payoff_1' => isset($this->sheet_data['loan_payoff_1']) ? (string) $this->sheet_data['loan_payoff_1'] : '0.00',
            'payoff_2' => isset($this->sheet_data['loan_payoff_2']) ? (string) $this->sheet_data['loan_payoff_2'] : '0.00',
            'payoff_3' => isset($this->sheet_data['loan_payoff_3']) ? (string) $this->sheet_data['loan_payoff_3'] : '0.00',
            'total'    => $total_payoffs,
        ];
    }

    /**
     * Calculate conveyance (recording) fees by county
     *
     * fee_percentage stores dollars per $1,000 (e.g., 3 = $3 per $1,000 of sales price)
     */
    private function calculate_conveyance_fees() {
        global $wpdb;

        $county      = $this->sheet_data['property_county'] ?? '';
        $sales_price = $this->sheet_data['sales_price'];

        $fee_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}nss_conveyance_fees
            WHERE county_name = %s AND is_active = 1",
            $county
        ));

        if (!$fee_data) {
            // Default: $4.00 per $1,000, seller pays 100%
            $thousands     = NSS_Precision_Math::divide($sales_price, 1000);
            $default_fee   = NSS_Precision_Math::multiply($thousands, 4);
            $this->results['conveyance_fees'] = [
                'rate'              => 4.0,
                'amount'            => $default_fee,
                'seller_percentage' => 1.0,
                'seller_amount'     => $default_fee,
            ];
            return;
        }

        // fee_percentage is dollars per $1,000 (e.g., 3 = $3/k)
        if ($fee_data->fee_percentage > 0) {
            $thousands = NSS_Precision_Math::divide($sales_price, 1000);
            $total_fee = NSS_Precision_Math::multiply($thousands, $fee_data->fee_percentage);
        } else {
            $total_fee = $fee_data->flat_fee;
        }

        $seller_amount = NSS_Precision_Math::multiply(
            $total_fee,
            $fee_data->seller_pays_percentage
        );

        $this->results['conveyance_fees'] = [
            'rate'              => (float) $fee_data->fee_percentage,
            'amount'            => $total_fee,
            'seller_percentage' => (float) $fee_data->seller_pays_percentage,
            'seller_amount'     => $seller_amount,
        ];
    }

    /**
     * Calculate prorated taxes and 50% tax holdback
     *
     * annual_taxes is read from tax_info JSON (user-supplied dollar amount)
     */
    private function calculate_tax_proration() {
        $closing_date = $this->sheet_data['closing_date'] ?? date('Y-m-d');

        $tax_info = $this->sheet_data['tax_info'] ?? [];
        if (is_string($tax_info)) {
            $tax_info = json_decode($tax_info, true) ?? [];
        }
        $annual_taxes = $tax_info['annual_taxes'] ?? 0;

        $tax_calc = new NSS_Tax_Proration($closing_date, $annual_taxes);
        $tax_data = $tax_calc->calculate_property_tax();

        $this->results['tax_proration'] = [
            'annual_taxes'    => $tax_data['annual_taxes'],
            'daily_rate'      => $tax_data['daily_rate'],
            'days_owed'       => $tax_data['days_owed'],
            'prorated_amount' => $tax_data['prorated_amount'],
            'tax_hold'        => $tax_data['tax_hold'],
        ];
    }

    /**
     * Calculate agent commission from user-entered percentage
     *
     * agent_commission_percentage is read from commission_structure JSON
     */
    private function calculate_commission() {
        $sales_price = $this->sheet_data['sales_price'];

        $commission_structure = $this->sheet_data['commission_structure'] ?? [];
        if (is_string($commission_structure)) {
            $commission_structure = json_decode($commission_structure, true) ?? [];
        }
        $rate = $commission_structure['agent_commission_percentage'] ?? 0;

        $amount = NSS_Precision_Math::percentage($sales_price, $rate);

        $this->results['commission'] = [
            'rate'   => (float) $rate,
            'amount' => $amount,
        ];
    }

    /**
     * Calculate all title-related fees
     *
     * Owner's policy is always calculated using cumulative Ohio OTIRB tiers.
     */
    private function calculate_title_fees() {
        global $wpdb;

        $county      = $this->sheet_data['property_county'] ?? '';
        $sales_price = $this->sheet_data['sales_price'];

        // Title closing fee (county-specific)
        $closing_fee = $wpdb->get_var($wpdb->prepare(
            "SELECT fee_amount FROM {$wpdb->prefix}nss_title_closing_fees
            WHERE county_name = %s AND is_active = 1",
            $county
        ));
        $closing_fee = $closing_fee ?? 375.00; // Default: $375.00 if no county record

        // Owner's policy — always calculated using tiered OTIRB rates
        $property_value   = new NSS_Property_Value($sales_price);
        $insurance_result = $property_value->calculate_title_insurance($sales_price);
        $owner_policy_fee = $insurance_result['premium'];

        // Static fees
        $static_fees = $wpdb->get_results(
            "SELECT fee_type, fee_amount FROM {$wpdb->prefix}nss_static_title_fees
            WHERE is_active = 1"
        );

        $courier_fee       = '0.00';
        $deed_prep_fee     = '0.00';
        $wire_transfer_fee = '0.00';
        $custom_fees       = [];

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
                default:
                    $custom_fees[] = [
                        'label'  => $fee->fee_type,
                        'amount' => (string) $fee->fee_amount,
                    ];
            }
        }

        $custom_totals    = array_column($custom_fees, 'amount');
        $total_title_fees = NSS_Precision_Math::sum(array_merge([
            $closing_fee,
            $owner_policy_fee,
            $courier_fee,
            $deed_prep_fee,
            $wire_transfer_fee,
        ], $custom_totals));

        $this->results['title_fees'] = [
            'closing_fee'       => (string) $closing_fee,
            'owner_policy_fee'  => $owner_policy_fee,
            'courier_fee'       => (string) $courier_fee,
            'deed_prep_fee'     => (string) $deed_prep_fee,
            'wire_transfer_fee' => (string) $wire_transfer_fee,
            'custom_fees'       => $custom_fees,
            'total'             => $total_title_fees,
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
            $this->results['tax_proration']['tax_hold'],
            $this->results['commission']['amount'],
            $this->results['title_fees']['total'],
        ];

        // Add HOA fees
        if (!empty($this->sheet_data['hoa_fees'])) {
            $deductions[] = $this->sheet_data['hoa_fees'];
        }

        // Add additional fees (JSON array)
        if (!empty($this->sheet_data['additional_fees'])) {
            $additional = is_string($this->sheet_data['additional_fees'])
                ? json_decode($this->sheet_data['additional_fees'], true)
                : $this->sheet_data['additional_fees'];
            if (is_array($additional)) {
                foreach ($additional as $fee) {
                    if (isset($fee['amount'])) {
                        $deductions[] = $fee['amount'];
                    }
                }
            }
        }

        $this->results['total_deductions'] = NSS_Precision_Math::sum($deductions);
    }

    /**
     * Calculate final net proceeds
     */
    private function calculate_net_proceeds() {
        $sales_price      = $this->sheet_data['sales_price'];
        $total_deductions = $this->results['total_deductions'];

        $net_proceeds = NSS_Precision_Math::subtract($sales_price, $total_deductions);

        $this->results['net_proceeds'] = [
            'sales_price'      => (string) $sales_price,
            'total_deductions' => $total_deductions,
            'net_amount'       => $net_proceeds,
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
