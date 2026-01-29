<?php
/**
 * GitHub-based automatic update checker
 *
 * Integrates with WordPress update system for automatic plugin updates
 */
class NSS_Updater {

    const GITHUB_REPO = 'absolute0github/net-seller-sheet-tool';
    const GITHUB_API = 'https://api.github.com/repos/';

    public function __construct() {
        add_filter('pre_set_site_transient_update_plugins', [$this, 'check_for_update']);
        add_filter('plugins_api', [$this, 'plugin_info'], 10, 3);
    }

    /**
     * Check GitHub for new plugin version
     *
     * @param object $transient
     * @return object Updated transient
     */
    public function check_for_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }

        $plugin_slug = plugin_basename(NSS_PLUGIN_FILE);
        $current_version = NSS_PLUGIN_VERSION;

        // Check cache first
        $cached = get_transient('nss_github_update');
        if ($cached !== false) {
            if (isset($cached['new_version']) && version_compare($current_version, $cached['new_version'], '<')) {
                $transient->response[$plugin_slug] = (object) [
                    'slug' => $plugin_slug,
                    'new_version' => $cached['new_version'],
                    'url' => $cached['url'],
                    'package' => $cached['package'],
                    'tested' => get_bloginfo('version'),
                ];
            }
            return $transient;
        }

        // Fetch from GitHub
        $release = $this->get_latest_release();
        if (!$release) {
            return $transient;
        }

        // Cache for 12 hours
        set_transient('nss_github_update', $release, 12 * HOUR_IN_SECONDS);

        $new_version = str_replace('v', '', $release['tag_name']);
        if (version_compare($current_version, $new_version, '<')) {
            $transient->response[$plugin_slug] = (object) [
                'slug' => $plugin_slug,
                'new_version' => $new_version,
                'url' => $release['url'],
                'package' => $release['package'],
                'tested' => get_bloginfo('version'),
            ];
        }

        return $transient;
    }

    /**
     * Provide plugin information to WordPress
     *
     * @param object $result
     * @param string $action
     * @param object $args
     * @return object
     */
    public function plugin_info($result, $action, $args) {
        if ($action !== 'plugin_information') {
            return $result;
        }

        $plugin_slug = 'net-seller-sheet';
        if (!isset($args->slug) || $args->slug !== $plugin_slug) {
            return $result;
        }

        $release = $this->get_latest_release();
        if (!$release) {
            return $result;
        }

        $result = new stdClass();
        $result->name = 'Net Seller Sheet';
        $result->slug = $plugin_slug;
        $result->version = str_replace('v', '', $release['tag_name']);
        $result->author = 'Your Name';
        $result->author_profile = 'https://example.com';
        $result->contributors = ['yourusername'];
        $result->description = 'Real estate net proceeds calculator with PDF generation';
        $result->sections = [
            'description' => $release['body'] ?? '',
            'installation' => 'Upload the plugin to your WordPress site and activate it.',
            'changelog' => 'See the release notes on GitHub',
        ];
        $result->download_link = $release['package'];
        $result->tested = get_bloginfo('version');
        $result->requires = '6.0';
        $result->requires_php = '8.0';

        return $result;
    }

    /**
     * Fetch latest GitHub release
     *
     * @return array|false
     */
    private function get_latest_release() {
        $url = self::GITHUB_API . self::GITHUB_REPO . '/releases/latest';

        $response = wp_remote_get($url, [
            'timeout' => 10,
            'headers' => [
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'WordPress/' . get_bloginfo('version'),
            ],
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($body) || isset($body['message'])) {
            return false;
        }

        return [
            'tag_name' => $body['tag_name'] ?? '',
            'body' => $body['body'] ?? '',
            'url' => $body['html_url'] ?? '',
            'package' => $body['zipball_url'] ?? '',
        ];
    }

    /**
     * Get update check URL (for manual checks)
     */
    public static function force_check() {
        delete_transient('nss_github_update');
        wp_clean_plugins_cache();
    }
}
