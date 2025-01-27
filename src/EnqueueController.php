<?php

namespace P4\MasterTheme;

/**
 * Class EnqueueController.
 *
 * This class is used to enqueue scripts and styles.
 */
class EnqueueController
{
    /**
     * EnqueueController constructor.
     */
    public function __construct()
    {
        add_action('enqueue_toggle_comment_submit_script', [$this, 'enqueue_toggle_comment_submit']);
        add_action('enqueue_hubspot_cookie_script', [$this, 'enqueue_hubspot_cookie']);
        add_action('enqueue_share_buttons_script', [$this, 'enqueue_share_buttons']);
        add_action('enqueue_google_tag_manager_script', [$this, 'enqueue_google_tag_manager']);
        add_action('enqueue_bulk_export_script', [$this, 'enqueue_bulk_export']);
        add_action('enqueue_media_import_button_script', [$this, 'enqueue_media_import_button']);
        add_action('enqueue_filter_block_names_script', [$this, 'enqueue_filter_block_names']);
        add_action('enqueue_metabox_search_script', [$this, 'enqueue_metabox_search']);
        add_action('enqueue_dismiss_dashboard_notice_script', [$this, 'enqueue_dismiss_dashboard_notice']);
        add_action('enqueue_hide_share_buttons_script', [$this, 'enqueue_hide_share_buttons']);
    }

    /**
     * Enqueues the toggle comment submit script.
     *
     * This method registers and enqueues the JavaScript file used to manage
     * the functionality of toggling the comment submit button based on user input.
     *
     */
    public function enqueue_toggle_comment_submit(): void
    {
        $this->enqueue_script(
            'toggle-comment-submit-script',
            '/assets/build/toggleCommentSubmit.js',
            [],
            $this->get_file_version('/assets/build/toggleCommentSubmit.js'),
            true
        );
    }

    /**
     * Enqueues the filter block names script.
     *
     */
    public function enqueue_filter_block_names(): void
    {
        $this->enqueue_script(
            'filter-block-names-script',
            '/assets/build/filterBlockNames.js',
            [],
            $this->get_file_version('/assets/build/filterBlockNames.js'),
            true
        );
    }

    /**
     * Enqueues the HubSpot cookie script.
     *
     * This method registers and enqueues the JavaScript file for handling HubSpot
     * cookie management on the website.
     *
     */
    public function enqueue_hubspot_cookie(): void
    {
        $this->enqueue_script(
            'hubspot-cookie-script',
            '/assets/build/hubspotCookie.js',
            [],
            $this->get_file_version('/assets/build/hubspotCookie.js'),
            true
        );
    }

    /**
     * Enqueues the script to hide the share buttons.
     *
     */
    public function enqueue_hide_share_buttons(): void
    {
        $this->enqueue_script(
            'hide-share-buttons-script',
            '/assets/build/hideShareButtons.js',
            [],
            $this->get_file_version('/assets/build/hideShareButtons.js'),
            true
        );
    }

    /**
     * Enqueues the bulk export script.
     *
     * @param string $text The text to be passed to the script.
     */
    public function enqueue_bulk_export(string $text): void
    {
        $script = [
            'id' => 'bulkExportText',
            'name' => 'hubspot-bulk-export-script',
            'path' => '/assets/build/bulkExport.js',
        ];

        $this->enqueue_script(
            $script['name'],
            $script['path'],
            [],
            $this->get_file_version($script['path']),
            true
        );

        if (!wp_script_is($script['name'], 'enqueued')) {
            return;
        }

        $script = 'var ' . $script['id'] . ' = "' . $text . '";';

        wp_add_inline_script($script['name'], $script);
    }

    /**
     * Enqueues the dismiss dashboard notice script.
     *
     * This method registers and enqueues the JavaScript file used to add
     * the dismiss button to the dashboard notices.
     *
     */
    public function enqueue_dismiss_dashboard_notice(): void
    {
        $script = [
            'id' => 'dismissDashboardNotice',
            'name' => 'dismiss-dashboard-notice-script',
            'path' => '/assets/build/dismissDashboardNotice.js',
        ];

        $this->enqueue_script(
            $script['name'],
            $script['path'],
            [],
            $this->get_file_version($script['path']),
            true
        );

        if (!wp_script_is($script['name'], 'enqueued')) {
            return;
        }

        wp_localize_script($script['name'], $script['id'], array(
            'ajaxurl' => admin_url('admin-ajax.php'),
        ));
    }

    /**
     * Enqueues the media import button script.
     *
     * This method registers and enqueues the JavaScript file used to add
     * the media import button to the media library.
     *
     * @param string $label The label for the media import button.
     *
     */
    public function enqueue_media_import_button(string $label): void
    {
        $script = [
            'id' => 'mediaImportLabel',
            'name' => 'media-import-button-script',
            'path' => '/assets/build/mediaImportButton.js',
        ];

        $this->enqueue_script(
            $script['name'],
            $script['path'],
            [],
            $this->get_file_version($script['path']),
            true
        );

        if (!wp_script_is($script['name'], 'enqueued')) {
            return;
        }

        $btn_label = 'var ' . $script['id'] . ' = "' . $label . '";';

        wp_add_inline_script($script['name'], $btn_label);
    }

    /**
     * Enqueues the metabox search script.
     *
     * This method registers and enqueues the JavaScript file used to manage
     * the functionality of setting the metabox weight based on the parent page.
     *
     * @param array $data The data to be passed to the script.
     */
    public function enqueue_metabox_search(array $data): void
    {
        $script = [
            'id' => 'metaboxSearchData',
            'name' => 'metabox-search-script',
            'path' => '/assets/build/metaboxSearch.js',
        ];

        $this->enqueue_script(
            $script['name'],
            $script['path'],
            [],
            $this->get_file_version($script['path']),
            true
        );

        if (!wp_script_is($script['name'], 'enqueued')) {
            return;
        }

        wp_localize_script($script['name'], $script['id'], $data);
    }

    /**
     * Enqueues the Google Tag Manager script and passes the context data to it.
     *
     * @param array $context The context data to be passed to the script.
     */
    public function enqueue_google_tag_manager(array $context): void
    {
        $script = [
            'id' => 'googleTagManagerData',
            'name' => 'google-tag-manager-script',
            'path' => '/assets/build/googleTagManager.js',
        ];

        $this->enqueue_script(
            $script['name'],
            $script['path'],
            [],
            $this->get_file_version($script['path']),
            true
        );

        if (!wp_script_is($script['name'], 'enqueued')) {
            return;
        }

        $gtm_data = [
            'google_tag_value' => $context['google_tag_value'] ?? null,
            'google_tag_domain' => $context['google_tag_domain'] ?? null,
            'consent_default_analytics_storage' => $context['consent_default_analytics_storage'] ?? null,
            'consent_default_ad_storage' => $context['consent_default_ad_storage'] ?? null,
            'consent_default_ad_user_data' => $context['consent_default_ad_user_data'] ?? null,
            'consent_default_ad_personalization' => $context['consent_default_ad_personalization'] ?? null,
            'page_category' => $context['page_category'] ?? null,
            'p4_signedin_status' => $context['p4_signedin_status'] ?? null,
            'p4_visitor_type' => $context['p4_visitor_type'] ?? null,
            'post_tags' => $context['post_tags'] ?? null,
            'p4_blocks' => $context['p4_blocks'] ?? null,
            'post_categories' => $context['post_categories'] ?? null,
            'reading_time' => $context['reading_time'] ?? null,
            'page_date' => $context['page_date'] ?? null,
            'cf_campaign_name' => $context['cf_campaign_name'] ?? null,
            'cf_project_id' => $context['cf_project_id'] ?? null,
            'cf_local_project_id' => $context['cf_local_project_id'] ?? null,
            'cf_basket_name' => $context['cf_basket_name'] ?? null,
            'cf_scope' => $context['cf_scope'] ?? null,
            'cf_department' => $context['cf_department'] ?? null,
            'enforce_cookies_policy' => $context['enforce_cookies_policy'] ?? null,
            'cookies_enable_google_consent_mode' => $context['cookies']->enable_google_consent_mode ?? null,
            'post_password_required' => $context['post']->password_required ?? null,
        ];

        wp_localize_script($script['name'], $script['id'], $gtm_data);
    }

    /**
     * Enqueues the share buttons script.
     *
     * This method registers and enqueues the JavaScript file for handling
     * share buttons on the website.
     *
     * @param array $social_data The data to be passed to the script.
     *
     */
    public function enqueue_share_buttons(array $social_data): void
    {
        $script = [
            'id' => 'shareButtonsData',
            'name' => 'share-buttons-script',
            'path' => '/assets/build/shareButtons.js',
        ];

        $this->enqueue_script(
            $script['name'],
            $script['path'],
            [],
            $this->get_file_version($script['path']),
            true
        );

        if (!wp_script_is($script['name'], 'enqueued')) {
            return;
        }

        $data = [
            'link' => $social_data['link'] ?? null,
            'title' => $social_data['title'] ?? null,
            'description' => $social_data['description'] ?? null,
            'share_url' => $social_data['p4_gf_share_url_override'] ?? null,
            'share_text' => $social_data['p4_gf_share_text_override'] ?? null,
        ];

        wp_localize_script($script['name'], $script['id'], $data);
    }

    /**
     * Enqueue a script with the given parameters.
     *
     * @param string $handle The script handle.
     * @param string $path   The script path relative to the theme directory.
     * @param array $deps    (Optional) An array of dependencies.
     * @param string|null $version (Optional) The script version.
     * @param bool $in_footer (Optional) Whether to load the script in the footer.
     */
    private function enqueue_script(
        string $handle,
        string $path,
        array $deps = [],
        ?string $version = null,
        bool $in_footer = true
    ): void {
        wp_enqueue_script(
            $handle,
            get_template_directory_uri() . $path,
            $deps,
            $version,
            $in_footer
        );
    }

    /**
     * Get the file version based on its last modification time.
     *
     * @param string $relative_path The file path relative to the theme directory.
     * @return string|null The version string (timestamp) or null if the file does not exist.
     */
    private function get_file_version(string $relative_path): ?string
    {
        $absolute_path = get_template_directory() . $relative_path;

        if (file_exists($absolute_path)) {
            return filemtime($absolute_path);
        }

        return null;
    }
}
