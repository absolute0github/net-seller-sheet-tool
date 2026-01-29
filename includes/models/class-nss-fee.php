<?php
/**
 * Fee model - handles conveyance, tax, and title fees
 *
 * Base class for fee management
 */
class NSS_Fee {

    protected $table_name;
    protected $id;
    protected $data = [];

    /**
     * Constructor
     *
     * @param string $fee_type Type of fee (conveyance, tax_rate, title_closing, etc.)
     * @param int|null $id Fee ID to load
     */
    public function __construct($fee_type, $id = null) {
        global $wpdb;

        // Map fee types to table names
        $type_map = [
            'conveyance' => 'nss_conveyance_fees',
            'tax_rate' => 'nss_tax_rates',
            'property_value' => 'nss_property_value_rates',
            'title_closing' => 'nss_title_closing_fees',
            'title_exam' => 'nss_title_exam_fees',
            'static_fee' => 'nss_static_title_fees',
        ];

        $this->table_name = $wpdb->prefix . ($type_map[$fee_type] ?? '');

        if ($id) {
            $this->load($id);
        }
    }

    /**
     * Load fee from database
     *
     * @param int $id
     * @return bool
     */
    public function load($id) {
        global $wpdb;

        $fee = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            (int) $id
        ), ARRAY_A);

        if (!$fee) {
            return false;
        }

        $this->id = $fee['id'];
        $this->data = $fee;
        return true;
    }

    /**
     * Save fee (insert or update)
     *
     * @param array $data
     * @return int|false ID on success, false on failure
     */
    public function save($data) {
        global $wpdb;

        if ($this->id) {
            // Update
            $updated = $wpdb->update(
                $this->table_name,
                array_merge($this->data, $data),
                ['id' => $this->id],
                [],
                ['%d']
            );
            return $updated !== false ? $this->id : false;
        } else {
            // Insert
            $data['created_at'] = current_time('mysql');
            $inserted = $wpdb->insert($this->table_name, $data);
            if ($inserted) {
                $this->id = $wpdb->insert_id;
                $this->data = $data;
                return $this->id;
            }
            return false;
        }
    }

    /**
     * Delete fee
     *
     * @return bool
     */
    public function delete() {
        if (!$this->id) {
            return false;
        }

        global $wpdb;
        return $wpdb->delete(
            $this->table_name,
            ['id' => $this->id],
            ['%d']
        ) !== false;
    }

    /**
     * Get fee data
     *
     * @return array
     */
    public function get_data() {
        return $this->data;
    }

    /**
     * Toggle active status
     *
     * @return bool
     */
    public function toggle_active() {
        if (!$this->id) {
            return false;
        }

        global $wpdb;

        $new_status = $this->data['is_active'] ? 0 : 1;

        $updated = $wpdb->update(
            $this->table_name,
            ['is_active' => $new_status],
            ['id' => $this->id],
            ['%d'],
            ['%d']
        );

        if ($updated !== false) {
            $this->data['is_active'] = $new_status;
            return true;
        }

        return false;
    }

    /**
     * Get all active fees
     *
     * @return array
     */
    public static function get_active($fee_type) {
        global $wpdb;

        $type_map = [
            'conveyance' => 'nss_conveyance_fees',
            'tax_rate' => 'nss_tax_rates',
            'property_value' => 'nss_property_value_rates',
            'title_closing' => 'nss_title_closing_fees',
            'title_exam' => 'nss_title_exam_fees',
            'static_fee' => 'nss_static_title_fees',
        ];

        $table = $wpdb->prefix . ($type_map[$fee_type] ?? '');

        return $wpdb->get_results(
            "SELECT * FROM {$table} WHERE is_active = 1 ORDER BY created_at DESC"
        ) ?: [];
    }

    /**
     * Bulk update active status
     *
     * @param array $ids
     * @param int $active_status
     * @return bool
     */
    public static function bulk_toggle_active($fee_type, $ids, $active_status) {
        global $wpdb;

        $type_map = [
            'conveyance' => 'nss_conveyance_fees',
            'tax_rate' => 'nss_tax_rates',
            'property_value' => 'nss_property_value_rates',
            'title_closing' => 'nss_title_closing_fees',
            'title_exam' => 'nss_title_exam_fees',
            'static_fee' => 'nss_static_title_fees',
        ];

        $table = $wpdb->prefix . ($type_map[$fee_type] ?? '');
        $ids = array_map('intval', $ids);
        $ids = implode(',', $ids);

        return $wpdb->query(
            "UPDATE {$table} SET is_active = {$active_status} WHERE id IN ({$ids})"
        ) !== false;
    }
}
