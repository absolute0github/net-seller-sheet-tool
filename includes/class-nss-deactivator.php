<?php
/**
 * Plugin deactivation handler
 *
 * Handles cleanup on plugin deactivation
 */
class NSS_Deactivator {

    /**
     * Deactivate the plugin
     */
    public static function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();

        // Remove capabilities (they'll be re-added on activation)
        // This is optional - many plugins leave capabilities for data preservation
    }
}
