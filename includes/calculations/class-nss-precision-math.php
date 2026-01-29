<?php
/**
 * Precision math wrapper using brick/math
 *
 * Handles all precise decimal calculations for financial accuracy
 */

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

class NSS_Precision_Math {

    /**
     * Convert value to BigDecimal
     *
     * @param mixed $value
     * @return BigDecimal
     */
    public static function parse($value) {
        if ($value instanceof BigDecimal) {
            return $value;
        }

        return BigDecimal::of((string) $value);
    }

    /**
     * Add two numbers with precision
     *
     * @param mixed $a
     * @param mixed $b
     * @return string
     */
    public static function add($a, $b) {
        $result = self::parse($a)->plus(self::parse($b));
        return $result->toScale(2, RoundingMode::HALF_UP)->__toString();
    }

    /**
     * Subtract b from a
     *
     * @param mixed $a
     * @param mixed $b
     * @return string
     */
    public static function subtract($a, $b) {
        $result = self::parse($a)->minus(self::parse($b));
        return $result->toScale(2, RoundingMode::HALF_UP)->__toString();
    }

    /**
     * Multiply two numbers
     *
     * @param mixed $a
     * @param mixed $b
     * @return string
     */
    public static function multiply($a, $b) {
        $result = self::parse($a)->multipliedBy(self::parse($b));
        return $result->toScale(2, RoundingMode::HALF_UP)->__toString();
    }

    /**
     * Divide a by b
     *
     * @param mixed $a
     * @param mixed $b
     * @return string
     */
    public static function divide($a, $b) {
        $result = self::parse($a)->dividedBy(
            self::parse($b),
            10,
            RoundingMode::HALF_UP
        );
        return $result->toScale(2, RoundingMode::HALF_UP)->__toString();
    }

    /**
     * Calculate percentage of a number
     *
     * @param mixed $amount
     * @param mixed $percentage
     * @return string
     */
    public static function percentage($amount, $percentage) {
        $result = self::parse($amount)->multipliedBy(self::parse($percentage));
        return $result->dividedBy('100', 10, RoundingMode::HALF_UP)
            ->toScale(2, RoundingMode::HALF_UP)->__toString();
    }

    /**
     * Sum array of values
     *
     * @param array $values
     * @return string
     */
    public static function sum($values) {
        $total = BigDecimal::of('0');

        foreach ($values as $value) {
            $total = $total->plus(self::parse($value));
        }

        return $total->toScale(2, RoundingMode::HALF_UP)->__toString();
    }

    /**
     * Compare two numbers
     * Returns: 0 if equal, 1 if a > b, -1 if a < b
     *
     * @param mixed $a
     * @param mixed $b
     * @return int
     */
    public static function compare($a, $b) {
        return self::parse($a)->compareTo(self::parse($b));
    }

    /**
     * Get absolute value
     *
     * @param mixed $value
     * @return string
     */
    public static function abs($value) {
        return self::parse($value)->abs()->toScale(2, RoundingMode::HALF_UP)->__toString();
    }

    /**
     * Format number as currency (string)
     *
     * @param mixed $value
     * @return string
     */
    public static function format_currency($value) {
        $decimal = self::parse($value)->toScale(2, RoundingMode::HALF_UP);
        return number_format((float) $decimal->__toString(), 2, '.', ',');
    }

    /**
     * Round to 2 decimal places
     *
     * @param mixed $value
     * @return string
     */
    public static function round($value) {
        return self::parse($value)->toScale(2, RoundingMode::HALF_UP)->__toString();
    }
}
