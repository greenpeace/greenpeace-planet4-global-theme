<?php

namespace P4\MasterTheme;

use ElasticPress\Elasticsearch as ES;
use WP_Error;

/**
 * Class ControlPanel
 */
class ControlPanel
{
    /**
     * ControlPanel constructor.
     */
    public function __construct()
    {
        $this->hooks();
    }

    /**
     * Hooks actions and filters.
     */
    public function hooks(): void
    {
        // Display the Control Panel only to Administrators.
        if (!current_user_can('manage_options') && !current_user_can('editor')) {
            return;
        }

        add_action('wp_dashboard_setup', [ $this, 'add_dashboard_widgets' ], 9);

        add_action('wp_ajax_flush_cache', [ $this, 'flush_cache' ]);
        add_action('wp_ajax_check_cache', [ $this, 'check_cache' ]);
        add_action('wp_ajax_check_elasticsearch', [ $this, 'check_elasticsearch' ]);

        add_action('admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ]);
    }

    /**
     * Adds a new Dashboard widget.
     */
    public function add_dashboard_widgets(): void
    {
        wp_add_dashboard_widget(
            'planet4_control_panel',
            __('Planet 4 Control Panel', 'planet4-master-theme-backend'),
            [ $this, 'add_items' ]
        );
    }

    /**
     * Adds items to the Control Panel.
     */
    public function add_items(): void
    {
        wp_nonce_field('cp-action');

        if (!current_user_can('manage_options') && !current_user_can('editor')) {
            return;
        }

        if (is_plugin_active('wp-redis/wp-redis.php')) {
            $this->add_item(
                [
                    'title' => __('Cache', 'planet4-master-theme-backend'),
                    'subitems' => [
                        [
                            'title' => __('Flush Object Cache', 'planet4-master-theme-backend'),
                            'action' => 'flush_cache',
                            'confirm' => __(
                                'Are you sure you want to delete all Object Cache keys?',
                                'planet4-master-theme-backend'
                            ),
                        ],
                        [
                            'title' => __('Check Object Cache', 'planet4-master-theme-backend'),
                            'action' => 'check_cache',
                        ],
                    ],
                ]
            );
        }

        if (!is_plugin_active('elasticpress/elasticpress.php')) {
            return;
        }

        $this->add_item(
            [
                'title' => __('Search', 'planet4-master-theme-backend'),
                'subitems' => [
                    [
                        'title' => __('Sync Elasticsearch', 'planet4-master-theme-backend'),
                        // If we give 'url' key instead of 'action'
                        // then we will do usual request instead of ajax request.
                        'url' => 'admin.php?page=elasticpress-sync',
                        // 'action' => 'ep_index', // Could use this EP action to perform sync asynchronously.
                    ],
                    [
                        'title' => __('Check Elasticsearch', 'planet4-master-theme-backend'),
                        'action' => 'check_elasticsearch',
                    ],
                ],
            ]
        );
    }

    /**
     * Adds a new item in the Control Panel and all of its subitems.
     *
     * @param array $data Associative array with all the data needed to add a new item in the Control Panel.
     * phpcs:disable Generic.Files.LineLength.MaxExceeded
     */
    public function add_item(array $data): void
    {
        echo '<div class="cp-item">
				<div><h3>' . esc_html($data['title']) . '</h3>';
        foreach ($data['subitems'] as $subitem) {
            echo '<div>
				<a data-ajaxurl="' . esc_url(admin_url('admin-ajax.php')) . '" href="' . esc_url($subitem['url'] ?? '#') . '" class="btn btn-cp-action ' . esc_attr($subitem['action'] ?? '') . '" data-action="' . esc_attr($subitem['action'] ?? '') . '" data-confirm="' . esc_attr($subitem['confirm'] ?? '') . '">' . esc_html($subitem['title']) . '</a>
					<span class="cp-subitem-response"></span>
				</div>';
        }
        echo '</div>
			</div>';
    }
    // phpcs:enable Generic.Files.LineLength.MaxExceeded

    /**
     * Adds a flush cache button to delete all keys in Redis database.
     */
    public function flush_cache(): void
    {
        if (! current_user_can('manage_options') && ! current_user_can('editor')) {
            return;
        }

        // If this is an ajax call.
        if (!wp_doing_ajax()) {
            return;
        }

        // Allow this action only to Administrators.
        $cp_nonce = sanitize_text_field($_GET['_wpnonce']);
        $cp_action = sanitize_text_field($_GET['cp-action']);

        // CSRF check and action check.
        if (wp_verify_nonce($cp_nonce, 'cp-action') && 'flush_cache' === $cp_action) {
            $response = [];

            // If cache flush was successful.
            if (wp_cache_flush()) {
                $response['message'] = __('Object Cache flushed', 'planet4-master-theme-backend');
                $response['class'] = 'cp-success';
            } else {
                $response['message'] = __('Object Cache did not flush', 'planet4-master-theme-backend');
                $response['class'] = 'cp-error';
            }

            if ($response) {
                echo wp_json_encode($response);
            }
        }
        wp_die();
    }

    /**
     * Adds a check cache button to check connectivity to the Redis server.
     */
    public function check_cache(): void
    {
        if (! current_user_can('manage_options') && ! current_user_can('editor')) {
            return;
        }

        // If this is an ajax call.
        if (!wp_doing_ajax()) {
            return;
        }

        // Allow this action only to Administrators.
        $cp_nonce = sanitize_text_field($_GET['_wpnonce']);
        $cp_action = sanitize_text_field($_GET['cp-action']);

        // CSRF check and action check.
        if (wp_verify_nonce($cp_nonce, 'cp-action') && 'check_cache' === $cp_action) {
            $response = [];
            $info = wp_redis_get_info();

            if ($info instanceof WP_Error) {
                if ($info->errors['wp-redis'] && is_array($info->errors['wp-redis'])) {
                    $response['message'] = $info->errors['wp-redis'][0];
                    $response['class'] = 'cp-error';
                }
            } elseif ('connected' === $info['status']) {
                $response['message'] = __('Planet 4 is connected to Redis.', 'planet4-master-theme-backend');
                $response['class'] = 'cp-success';
            }

            if ($response) {
                echo wp_json_encode($response);
            }
        }
        wp_die();
    }

    /**
     * Adds a check button to check communication to the ES cluster.
     */
    public function check_elasticsearch(): void
    {
        // If this is an ajax call.
        if (!wp_doing_ajax()) {
            return;
        }

        // Allow this action only to Administrators.
        if (! current_user_can('manage_options')) {
            return;
        }

        $cp_nonce = sanitize_text_field($_GET['_wpnonce']);
        $cp_action = sanitize_text_field($_GET['cp-action']);

        // CSRF check and action check.
        if (wp_verify_nonce($cp_nonce, 'cp-action') && 'check_elasticsearch' === $cp_action) {
            $response = [];
            // For version up to 2.8.* we can call ep_elasticsearch_can_connect() to check connection.
            if (( new ES() )->get_elasticsearch_version()) {
                $response['message'] = __('Planet4 is connected to Elasticsearch', 'planet4-master-theme-backend');
                $response['class'] = 'cp-success';
            } else {
                $response['message'] = __('Planet4 is not connected to Elasticsearch', 'planet4-master-theme-backend');
                $response['class'] = 'cp-error';
            }

            if ($response) {
                echo wp_json_encode($response);
            }
        }
        wp_die();
    }

    /**
     * Load admin assets.
     */
    public function enqueue_admin_assets(): void
    {
        if (!is_admin()) {
            return;
        }

        $this->load_dashboard_assets();
        $this->load_wpml_assets();
    }

    /**
     * Load Dashboard assets.
     */
    public function load_dashboard_assets(): void
    {
        $is_dashboard = 'dashboard' === get_current_screen()->base;

        if (!$is_dashboard) {
            return;
        }

        $theme_uri = get_template_directory_uri();
        wp_enqueue_style(
            'dashboard-style',
            "$theme_uri/admin/css/dashboard.css",
            [],
            Loader::theme_file_ver('admin/css/dashboard.css')
        );
        wp_enqueue_script(
            'dashboard-script',
            "$theme_uri/admin/js/dashboard.js",
            [],
            Loader::theme_file_ver('admin/js/dashboard.js'),
            true
        );
    }

    /**
     * Load WPML assets.
     */
    public function load_wpml_assets(): void
    {
        $is_wpml_active = is_plugin_active('sitepress-multilingual-cms/sitepress.php');

        if (!$is_wpml_active) {
            return;
        }

        $theme_uri = get_template_directory_uri();
        wp_enqueue_style(
            'wpml-style',
            "$theme_uri/admin/css/wpml.css",
            [],
            Loader::theme_file_ver('admin/css/wpml.css')
        );
    }
}
