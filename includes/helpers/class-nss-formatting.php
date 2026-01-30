<?php
/**
 * Formatting helper class
 *
 * Handles formatting of data for display
 */
class NSS_Formatting {

    /**
     * Format currency
     *
     * @param mixed $amount
     * @return string
     */
    public static function currency($amount) {
        return '$' . number_format((float) $amount, 2);
    }

    /**
     * Format percentage
     *
     * Values are stored as whole percentages (3 = 3%, 1.5 = 1.5%)
     *
     * @param mixed $value
     * @param int $decimals
     * @return string
     */
    public static function percentage($value, $decimals = 2) {
        return number_format((float) $value, $decimals) . '%';
    }

    /**
     * Format date
     *
     * @param string $date
     * @param string $format
     * @return string
     */
    public static function date($date, $format = 'M d, Y') {
        $timestamp = strtotime($date);
        return $timestamp ? date($format, $timestamp) : $date;
    }

    /**
     * Format address
     *
     * @param array $address
     * @return string
     */
    public static function address($address) {
        $parts = [
            $address['street'] ?? '',
            $address['city'] ?? '',
            $address['state'] ?? '',
            $address['zip'] ?? '',
        ];

        return implode(', ', array_filter($parts));
    }

    /**
     * Format phone number
     *
     * @param string $phone
     * @return string
     */
    public static function phone($phone) {
        $phone = preg_replace('/\D/', '', $phone);

        if (strlen($phone) == 10) {
            return sprintf('(%s) %s-%s', substr($phone, 0, 3), substr($phone, 3, 3), substr($phone, 6));
        }

        return $phone;
    }

    /**
     * Format number
     *
     * @param mixed $number
     * @param int $decimals
     * @return string
     */
    public static function number($number, $decimals = 0) {
        return number_format((float) $number, $decimals);
    }

    /**
     * Format table for HTML output
     *
     * @param array $data
     * @return string HTML table
     */
    public static function table($data) {
        if (empty($data)) {
            return '';
        }

        $html = '<table class="nss-table"><thead><tr>';

        // Headers
        foreach (array_keys((array) $data[0]) as $key) {
            $html .= '<th>' . self::escape(ucwords(str_replace('_', ' ', $key))) . '</th>';
        }

        $html .= '</tr></thead><tbody>';

        // Rows
        foreach ($data as $row) {
            $html .= '<tr>';
            foreach ((array) $row as $value) {
                $html .= '<td>' . self::escape($value) . '</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        return $html;
    }

    /**
     * Escape value for HTML
     *
     * @param mixed $value
     * @return string
     */
    public static function escape($value) {
        if (is_array($value)) {
            return implode(', ', array_map('esc_html', $value));
        }

        return esc_html($value);
    }

    /**
     * Format calculation result for display
     *
     * @param array $result
     * @return array Formatted result
     */
    public static function format_calculation($result) {
        $formatted = [];

        foreach ($result as $key => $value) {
            if (is_array($value)) {
                $formatted[$key] = self::format_calculation($value);
            } elseif (strpos($key, 'amount') !== false || strpos($key, 'fee') !== false || strpos($key, 'price') !== false) {
                $formatted[$key] = self::currency($value);
            } elseif (strpos($key, 'rate') !== false || strpos($key, 'percentage') !== false) {
                $formatted[$key] = self::percentage($value);
            } elseif (strpos($key, 'date') !== false) {
                $formatted[$key] = self::date($value);
            } else {
                $formatted[$key] = $value;
            }
        }

        return $formatted;
    }
}
