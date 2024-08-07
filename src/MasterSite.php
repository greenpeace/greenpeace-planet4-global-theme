<?php

namespace P4\MasterTheme;

use P4\MasterTheme\Features\Dev\CoreBlockPatterns;
use P4\MasterTheme\Features\LazyYoutubePlayer;
use Timber\Timber;
use Twig\Extension\StringLoaderExtension;
use Twig\Markup;
use WP_Error;
use WP_Post;

/**
 * Class MasterSite.
 * The main class that handles Planet4 Master Theme.
 */
class MasterSite extends \Timber\Site
{
    /**
     * Key of notice seen by user
     *
     */
    private const DASHBOARD_MESSAGE_KEY = 'last_p4_notice';

    /**
     * Version of notice
     *
     */
    private const DASHBOARD_MESSAGE_VERSION = '0.3';

    /**
     * Credit meta field key
     *
     */
    public const CREDIT_META_FIELD = '_credit_text';

    /**
     * Theme directory
     *
     */
    protected string $theme_dir;

    /**
     * Theme images directory
     *
     */
    protected string $theme_images_dir;

    /**
     * Sort options
     *
     */
    protected array $sort_options;

    /**
     * Variable that lets us know if the user has or hasn't used google to log in
     *
     */
    protected bool $google_login_error = false;

    /**
     * MasterSite constructor.
     */
    public function __construct()
    {
        $this->settings();
        $this->hooks();
        parent::__construct();
    }

    /**
     * Define settings for the Planet4 Master Theme.
     */
    protected function settings(): void
    {
        Timber::$dirname = ['templates', 'templates/blocks', 'views'];
        $this->theme_dir = get_template_directory_uri();
        $this->theme_images_dir = $this->theme_dir . '/images/';
        $this->sort_options = [
            '_score' => [
                'name' => __('Most relevant', 'planet4-master-theme'),
                'order' => 'DESC',
            ],
            'post_date' => [
                'name' => __('Newest', 'planet4-master-theme'),
                'order' => 'DESC',
            ],
            'post_date_asc' => [
                'name' => __('Oldest', 'planet4-master-theme'),
                'order' => 'ASC',
            ],
        ];
    }

    /**
     * Hooks the theme.
     */
    protected function hooks(): void
    {
        add_theme_support('post-thumbnails');
        add_theme_support('menus');
        if (!CoreBlockPatterns::is_active()) {
            // Disable WP Block Patterns.
            remove_theme_support('core-block-patterns');
        }

        add_post_type_support('page', 'excerpt'); // Added excerpt option to pages.

        add_filter('timber/context', [$this, 'add_to_context']);
        add_filter('timber/twig', [$this, 'add_to_twig']);
        add_action('init', [$this, 'register_taxonomies'], 2);
        add_action('init', [$this, 'register_oembed_provider']);
        add_action('admin_menu', [$this, 'add_post_revisions_setting']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        // Load the editor scripts only enqueuing editor scripts while in context of the editor.
        add_action('enqueue_block_editor_assets', [$this, 'enqueue_editor_assets']);
        // Load main theme assets before any child theme.
        add_action('wp_enqueue_scripts', [PublicAssets::class, 'enqueue_css'], 0);
        add_action('wp_enqueue_scripts', [PublicAssets::class, 'enqueue_js']);
        add_filter('safe_style_css', [$this, 'set_custom_allowed_css_properties']);
        add_filter('wp_kses_allowed_html', [$this, 'set_custom_allowed_attributes_filter'], 10, 2);
        add_action('add_meta_boxes', [$this, 'add_meta_box_search'], 10, 2);
        add_action('save_post', [$this, 'save_meta_box_search'], 10, 2);
        add_action('save_post', [$this, 'set_featured_image'], 10, 2);
        add_filter('wp_insert_post_data', [$this, 'require_post_title'], 10, 1);
        // Save "p4_global_project_tracking_id" on post save.
        add_action('save_post', [$this, 'save_global_project_id'], 10, 1);
        add_action('post_updated', [$this, 'clean_post_cache'], 10, 3);
        add_action('after_setup_theme', [$this, 'p4_master_theme_setup']);
        add_action('pre_insert_term', [$this, 'disallow_insert_term'], 1, 2);
        add_filter('wp_dropdown_users_args', [$this, 'filter_authors'], 10, 1);
        add_filter('wp_image_editors', [$this, 'allowedEditors']);
        add_filter('jpeg_quality', fn () => 60);
        add_filter('http_request_timeout', fn () => 10);
        add_action('after_setup_theme', [$this, 'add_image_sizes']);
        add_action('save_post', [$this, 'p4_auto_generate_excerpt'], 10, 2);

        add_action('admin_head', [$this, 'add_help_sidebar']);

        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('wp_head', 'wp_generator');
        if (has_action('wp_print_styles', 'print_emoji_styles')) {
            remove_action('wp_print_styles', 'print_emoji_styles');
        }

        register_nav_menus(
            [
                'navigation-bar-menu' => __('Navigation Bar Menu', 'planet4-master-theme-backend'),
                'donate-menu' => __('Donate Button', 'planet4-master-theme-backend'),
            ]
        );

        add_filter('authenticate', [$this, 'enforce_google_signon'], 4, 3);
        add_filter('authenticate', [$this, 'check_google_login_error'], 30, 1);
        add_filter('login_headerurl', [$this, 'add_login_logo_url']);
        add_filter('login_headertext', [$this, 'add_login_logo_url_title']);
        add_action('login_enqueue_scripts', [$this, 'add_login_stylesheet']);
        add_filter('comment_form_submit_field', [$this, 'gdpr_cc_comment_form_add_class'], 150, 1);
        add_filter('comment_form_default_fields', [$this, 'comment_form_cookie_checkbox_add_class']);
        add_filter('comment_form_default_fields', [$this, 'comment_form_replace_inputs']);
        add_filter('embed_oembed_html', [$this, 'filter_youtube_oembed_nocookie'], 10, 2);
        add_filter('oembed_result', [$this, 'filter_youtube_oembed_nocookie'], 10, 2);
        add_filter(
            'editable_roles',
            function ($roles) {
                uasort(
                    $roles,
                    function ($a, $b) {
                        return $b['name'] <=> $a['name'];
                    }
                );
                return $roles;
            }
        );

        /**
         * Apply wpautop to non-block content.
         *
         * @link https://wordpress.stackexchange.com/q/321662/26317
         *
         * @param string $block_content The HTML generated for the block.
         * @param array  $block         The block.
         */
        add_filter(
            'render_block',
            function ($block_content, $block) {
                if (is_null($block['blockName'])) {
                    return wpautop($block_content);
                }
                return $block_content;
            },
            10,
            2
        );

        add_action('init', [$this, 'login_redirect'], 1);
        add_filter('attachment_fields_to_edit', [$this, 'add_image_attachment_fields_to_edit'], 10, 2);
        add_filter('attachment_fields_to_save', [$this, 'add_image_attachment_fields_to_save'], 10, 2);
        version_compare(get_bloginfo('version'), '5.5', '<')
            ? add_action('init', [$this, 'p4_register_core_image_block'])
            : add_filter('register_block_type_args', [$this, 'register_core_blocks_callback']);
        add_action('admin_notices', [$this, 'show_dashboard_notice']);
        add_action('wp_ajax_dismiss_dashboard_notice', [$this, 'dismiss_dashboard_notice']);

        // Disable WordPress(WP5.5) Block Directory.
        $this->disable_block_directory();

        // Update P4 author override value in RSS feed.
        add_filter(
            'the_author',
            function ($post_author) {
                if (is_feed()) {
                    global $post;
                    $author_override = get_post_meta($post->ID, 'p4_author_override', true);
                    if ('' !== $author_override) {
                        $post_author = $author_override;
                    }
                }
                return $post_author;
            }
        );

        // Disable xmlrpc.
        add_filter('xmlrpc_enabled', '__return_false');

        // Anonymize Comment Authors IP address.
        add_filter(
            'pre_comment_user_ip',
            function () {
                return '';
            }
        );

        // Make post tags ordered.
        add_filter('register_taxonomy_args', [$this, 'set_post_tags_as_ordered'], 10, 2);

        add_action(
            'customize_register',
            function ($wp_customize): void {
                if (!defined('WP_APP_ENV') || ( 'production' !== WP_APP_ENV && 'staging' !== WP_APP_ENV )) {
                    return;
                }

                // Disable CSS Customizer.
                $wp_customize->remove_control('custom_css');
            }
        );

        // Admin scripts.
        add_action('admin_enqueue_scripts', [AdminAssets::class, 'enqueue_js']);

        // Disable the Elastic search sync, if archive posts feature is disable.
        add_filter(
            'ep_indexable_post_types',
            function ($post_types) {
                $setting = planet4_get_option('include_archive_content_for');
                if (isset($post_types['archive']) && 'nobody' === $setting) {
                    unset($post_types['archive']);
                }
                return $post_types;
            }
        );

        // Disable Background updates check in Site health.
        add_filter(
            'site_status_tests',
            function ($tests) {
                unset($tests['async']['background_updates']);
                return $tests;
            }
        );

        // Maximum revisions to keep whenever the editor save a post
        add_filter(
            'wp_revisions_to_keep',
            function ($revisions) {
                $revisions_to_keep = get_option('revisions_to_keep');
                return $revisions_to_keep ?: $revisions;
            },
            10,
            1
        );

        $this->register_meta_fields();

        // Override category template to use taxonomy.php.
        add_filter(
            'template_include',
            function ($tax_template) {
                if (!is_category()) {
                    return $tax_template;
                }
                return get_template_directory() . '/taxonomy.php';
            }
        );

        // Add noindex meta tag on pages that are excluded from search
        add_action(
            'wp_head',
            function (): void {
                if (!is_singular()) {
                    return;
                }

                global $post;

                $exclude_from_search = get_post_meta($post->ID, 'ep_exclude_from_search', true);

                if (!$exclude_from_search) {
                    return;
                }

                echo '<meta name="robots" content="noindex">' . PHP_EOL;
            },
            10
        );

        TimberSettings::hooks();
        AuthorPage::hooks();
        Search\Search::hooks();
        Sendgrid::hooks();
    }

    /**
     * Detects and redirects login from non-canonical domain to preferred domain
     */
    public function login_redirect(): void
    {
        if (!isset($GLOBALS['pagenow']) || 'wp-login.php' !== $GLOBALS['pagenow']) {
            // Not on the login page, as you were.
            return;
        }

        if (!isset($_SERVER['HTTP_HOST']) || !isset($_SERVER['SERVER_NAME'])) {
            // If either of these are unset, we can't be sure we want to redirect.
            return;
        }

        if ($_SERVER['HTTP_HOST'] === $_SERVER['SERVER_NAME']) {
            return;
        }

        $adminUrl = str_replace(
            sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])),
            sanitize_text_field(wp_unslash($_SERVER['SERVER_NAME'])),
            get_admin_url()
        );
        if (wp_safe_redirect($adminUrl)) {
            exit;
        }
    }

    /**
     * Sets the URL for the logo link in the login page.
     */
    public function add_login_logo_url(): string
    {
        return home_url();
    }

    /**
     * Sets the title for the logo link in the login page.
     */
    public function add_login_logo_url_title(): string
    {
        return get_bloginfo('name');
    }

    /**
     * Sets a custom stylesheet for the login page.
     */
    public function add_login_stylesheet(): void
    {
        wp_enqueue_style(
            'custom-login',
            $this->theme_dir . '/admin/css/login.css',
            [],
            Loader::theme_file_ver('admin/css/login.css')
        );
    }

    /**
     * Sets as featured image of the post the first image found attached in the post's content (if any).
     *
     * @param int     $post_id The ID of the current Post.
     * @param WP_Post $post The current Post.
     */
    public function set_featured_image(int $post_id, WP_Post $post): void
    {
        $types = Search\Filters\ContentTypes::get_all();
        // Ignore autosave, check user's capabilities and post type.
        if (
            defined('DOING_AUTOSAVE') && DOING_AUTOSAVE
            || !current_user_can('edit_post', $post_id)
            || !in_array($post->post_type, array_keys($types))
        ) {
            return;
        }

        // Check if user has set the featured image manually.
        $user_set_featured_image = get_post_meta($post_id, '_thumbnail_id', true);

        // Apply this behavior only if there is not already a featured image.
        if ($user_set_featured_image) {
            return;
        }

        // Find all matches of <img> html tags within the post's content
        // and get the id of the image from the elements class name.
        preg_match_all('/<img.+wp-image-(\d+).*>/i', $post->post_content, $matches);
        if (!isset($matches[1][0]) || !is_numeric($matches[1][0])) {
            return;
        }

        set_post_thumbnail($post_id, $matches[1][0]);
    }

    /**
     * Make post title mandatory on publish.
     */
    public static function require_post_title(array $data): ?array
    {
        $types = Search\Filters\ContentTypes::get_all();
        if (
            empty($data['post_title'])
            && !empty($data['post_status'])
            && $data['post_status'] === 'publish'
            && in_array($data['post_type'], array_keys($types))
        ) {
            $err_message = __('Title is a required field.', 'planet4-master-theme-backend');
            defined('WP_CLI') && WP_CLI
                ? throw new \Exception($err_message)
                : wp_die($err_message);
        }

        return $data;
    }

    /**
     * Generates hreflang metadata from countries.json template.
     */
    public static function generate_hreflang_meta(): ?array
    {

        $countries = wp_cache_get('countries');

        if (false === $countries) {
            $body = file_get_contents(get_template_directory() . '/templates/countries.json');
            $countries = json_decode($body, true);
            if (empty($countries)) {
                return null;
            }
            wp_cache_set('countries', $countries);
        }

        $metadata = [];

        foreach ($countries as $key => $letter) {
            if (0 === $key) {
                continue;
            }
            foreach ($letter as $country) {
                $lang = $country['lang'];
                foreach ($lang as $item) {
                    if (!isset($item['locale'])) {
                        continue;
                    }

                    foreach ($item['locale'] as $code) {
                        $metadata[$code] = $item['url'];
                    }
                }
            }
        }

        return $metadata;
    }

    /**
     * Sets as featured image of the post the first image found attached in the post's content (if any).
     *
     * @param int     $post_id The ID of the current Post.
     * @param WP_Post $post_after The current Post.
     * @param WP_Post $post_before Whether this is an existing post being updated or not.
     */
    public function clean_post_cache(int $post_id, WP_Post $post_after, WP_Post $post_before): void
    {

        // Ignore autosave.
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        $this->clean_boxout_posts_cache($post_id, $post_after, $post_before);

        clean_post_cache($post_id);
    }

    /**
     * Flush Take Action Boxout(TAB) posts cache, if the TAB act page status changes.
     *
     * @param int     $post_id The ID of the current Post.
     * @param WP_Post $post_after The current Post.
     * @param WP_Post $post_before Whether this is an existing post being updated or not.
     */
    private function clean_boxout_posts_cache(int $post_id, WP_Post $post_after, WP_Post $post_before): void
    {
        $parent_act_id = (int) planet4_get_option('act_page', -1);
        if ('page' !== $post_after->post_type || $parent_act_id !== $post_after->post_parent) {
            return;
        }

        // Flush cache only when a page status changes from publish to any non-public status & vice versa.
        if (
            ($post_before->post_status === $post_after->post_status) ||
            ('publish' !== $post_before->post_status && 'publish' !== $post_after->post_status)
        ) {
            return;
        }

        global $wpdb, $nginx_purger;

        // Search for those posts, who use TAB($post_id) from "Take Action Page Selector" dropdown.
        // phpcs:disable
        $sql          = 'SELECT post_id FROM %1$s WHERE meta_key = \'p4_take_action_page\' AND meta_value = %2$d';
        $prepared_sql = $wpdb->prepare($sql, $wpdb->postmeta, $post_id);
        $boxout_posts = $wpdb->get_col($prepared_sql);
        // phpcs:enable

        // Search for those posts, who use TAB($post_id) as a block inside block editor.
        $take_action_boxout_block = '%<!-- wp:planet4-blocks/take-action-boxout {"take_action_page":'
            . $post_id . '} /-->%';
        // phpcs:disable
        $sql          = 'SELECT ID FROM %1$s WHERE post_type = \'post\' AND post_status = \'publish\' AND post_content LIKE \'%2$s\'';
        $prepared_sql = $wpdb->prepare($sql, $wpdb->posts, $take_action_boxout_block);
        $result       = $wpdb->get_col($prepared_sql);
        // phpcs:enable

        $boxout_posts = array_merge($boxout_posts, $result);

        // Flush TAB posts cache.
        $boxout_posts = array_unique($boxout_posts);
        foreach ($boxout_posts as $tab_post_id) {
            clean_post_cache($tab_post_id);
            $tab_post_url = get_permalink($tab_post_id);
            $nginx_purger->purge_url(user_trailingslashit($tab_post_url));
        }
    }

    /**
     * Add extra image sizes as needed.
     */
    public function add_image_sizes(): void
    {
        add_image_size('retina-large', 2048, 1366, false);
        add_image_size('articles-medium-large', 510, 340, false);
    }

    /**
     * Force WordPress to use ImageCompression as image manipulation editor.
     */
    public function allowedEditors(): array
    {
        return [ImageCompression::class];
    }

    /**
     * Load translations for master theme
     */
    public function p4_master_theme_setup(): void
    {
        $domains = [
            'planet4-master-theme',
            'planet4-master-theme-backend',
            'planet4-engagingnetworks',
            'planet4-engagingnetworks-backend',
        ];
        $locale = is_admin() ? get_user_locale() : get_locale();

        foreach ($domains as $domain) {
            $mofile = get_template_directory() . '/languages/' . $domain . '-' . $locale . '.mo';
            load_textdomain($domain, $mofile);
        }
    }

    /**
     * Adds more data to the context variable that will be passed to the main template.
     *
     * @param array $context The associative array with data to be passed to the main template.
     *
     * @return mixed
     */
    public function add_to_context(array $context)
    {
        global $wp;

        $context['cookies'] = [
            'text' => planet4_get_option('cookies_field'),
            'enable_analytical_cookies' => planet4_get_option('enable_analytical_cookies'),
            'enable_reject_all_cookies' => planet4_get_option('enable_reject_all_cookies'),
            'enable_google_consent_mode' => planet4_get_option('enable_google_consent_mode'),
            'settings_copy' => [
                'necessary_cookies_name' => planet4_get_option('necessary_cookies_name', ''),
                'necessary_cookies_description' => planet4_get_option('necessary_cookies_description', ''),
                'analytical_cookies_name' => planet4_get_option('analytical_cookies_name', ''),
                'analytical_cookies_description' => planet4_get_option('analytical_cookies_description', ''),
                'all_cookies_name' => planet4_get_option('all_cookies_name', ''),
                'all_cookies_description' => planet4_get_option('all_cookies_description', ''),
            ],
        ];
        $context['theme_uri'] = $this->theme_dir;
        $context['data_nav_bar'] = [
            'images' => $this->theme_images_dir,
            'home_url' => home_url('/'),
            'search_query' => trim(get_search_query()),
            'country_dropdown_toggle' => __('Toggle worldwide site selection menu', 'planet4-master-theme'),
            'navbar_search_toggle' => __('Toggle search box', 'planet4-master-theme'),
        ];
        $context['domain'] = 'planet4-master-theme';
        $context['foo'] = 'bar'; // For unit test purposes.

        if (has_nav_menu('navigation-bar-menu')) {
            $menu = Timber::get_menu('navigation-bar-menu');
            $menu_items = $menu->get_items();
            $context['navbar_menu'] = $menu;
            $context['navbar_menu_items'] = array_filter(
                $menu_items,
                function ($item) {
                    return !in_array('wpml-ls-item', $item->classes ?? [], true);
                }
            );
        }

        // Check if the menu has been created.
        if (has_nav_menu('donate-menu')) {
            $donate_menu = Timber::get_menu('donate-menu');

            // Check if it has at least 1 item added into the menu.
            if (!empty($donate_menu->get_items())) {
                $context['donate_menu_items'] = $donate_menu->get_items();
            }
        }


        $languages = function_exists('icl_get_languages') ? icl_get_languages() : [];
        $context['site_languages'] = $languages;
        $context['languages'] = count($languages); // Keep this variable name as long as NRO themes use it.

        $context['site'] = $this;
        $context['current_url'] = trailingslashit(home_url($wp->request));
        $context['sort_options'] = $this->sort_options;
        $context['default_sort'] = Search\SearchPage::DEFAULT_SORT;

        $options = get_option('planet4_options');

        // Do not embed google tag manager js if 'greenpeace' cookie is not set
        // or enforce_cookies_policy setting is not enabled.
        $context['enforce_cookies_policy'] = isset($options['enforce_cookies_policy']) ? true : false;
        $context['google_tag_value'] = $options['google_tag_manager_identifier'] ?? '';
        $context['google_tag_domain'] = !empty($options['google_tag_manager_domain']) ?
            $options['google_tag_manager_domain'] : 'www.googletagmanager.com';
        $context['consent_default_analytics_storage'] =
            planet4_get_option('consent_default_analytics_storage') ?? 'denied';
        $context['consent_default_ad_storage'] =
            planet4_get_option('consent_default_ad_storage') ?? 'denied';
        $context['consent_default_ad_user_data'] =
            planet4_get_option('consent_default_ad_user_data') ?? 'denied';
        $context['consent_default_ad_personalization'] =
            planet4_get_option('consent_default_ad_personalization') ?? 'denied';
        $context['ab_hide_selector'] = $options['ab_hide_selector'] ?? null;
        $context['facebook_page_id'] = $options['facebook_page_id'] ?? '';
        $context['preconnect_domains'] = [];

        if (!empty($options['preconnect_domains'])) {
            $preconnect_domains = explode("\n", $options['preconnect_domains']);
            $preconnect_domains = array_map('trim', $preconnect_domains);
            $preconnect_domains = array_filter($preconnect_domains);

            $context['preconnect_domains'] = $preconnect_domains;
        }

        // hreflang metadata.
        if (is_front_page()) {
            $context['hreflang'] = self::generate_hreflang_meta();
        }

        // Datalayer feed.
        $current_user = wp_get_current_user();
        if ($current_user->ID) {
            $context['p4_signedin_status'] = 'true';
            $context['p4_visitor_type'] = $current_user->roles[0] ?? '';
        } else {
            $context['p4_signedin_status'] = 'false';
            $context['p4_visitor_type'] = 'guest';
        }

        $context['website_navbar_title'] = $options['website_navigation_title']
            ?? __('International (English)', 'planet4-master-theme');

        $context['act_page_id'] = $options['act_page'] ?? '';
        $context['explore_page_id'] = $options['explore_page'] ?? '';

        // Footer context.
        $context['copyright_text_line1'] = $options['copyright_line1'] ?? '';
        $context['copyright_text_line2'] = $options['copyright_line2'] ?? '';
        $context['footer_social_menu'] = wp_get_nav_menu_items('Footer Social');
        $context['footer_primary_menu'] = wp_get_nav_menu_items('Footer Primary');
        $context['footer_secondary_menu'] = wp_get_nav_menu_items('Footer Secondary');
        // Default depth level set to 1 if not selected from admin.
        $context['p4_comments_depth'] = get_option('thread_comments_depth') ?? 1;

        $context['countries_by_initials'] = json_decode(
            file_get_contents(get_template_directory() . '/templates/countries.json'),
            true,
            512,
            \JSON_OBJECT_AS_ARRAY
        );
        // HubSpot.
        $context['hubspot_active'] = is_plugin_active('gravityformshubspot/hubspot.php');
        // The Hubspot Tracking Code snippet will add only if the user has accepted "Marketing" cookies.
        if (
            $context['hubspot_active']
            && !isset($_COOKIE['no_track']) && isset($_COOKIE['active_consent_choice'])
            && $_COOKIE['active_consent_choice'] && isset($_COOKIE['greenpeace'])
            && in_array($_COOKIE['greenpeace'], [2, 4])
        ) {
            $context['hubspot_tracking_code'] = $options['hubspot_tracking_code'] ?? '';
        }

        // Dummy thumbnail.
        $context['dummy_thumbnail'] = get_template_directory_uri() . '/images/dummy-thumbnail.png';

        // IA: Tabs menu on mobile.
        $context['mobile_tabs_menu'] = (bool) planet4_get_option('new_ia');

        return $context;
    }

    /**
     * Add your own functions to Twig.
     *
     * @param Twig_ExtensionInterface $twig The Twig object that implements the Twig_ExtensionInterface.
     *
     * @return mixed
     */
    public function add_to_twig(\Twig\Environment $twig)
    {
        $twig->addExtension(new StringLoaderExtension());
        $twig->addFilter(new \Twig\TwigFilter('svgicon', [$this, 'svgicon']));
        return $twig;
    }

    /**
     * SVG Icon helper
     *
     * @param string $name Icon name.
     */
    public function svgicon(string $name): Markup
    {
        $svg_icon_template = '<svg viewBox="0 0 32 32" class="icon"><use xlink:href="'
            . $this->theme_dir . '/assets/build/sprite.symbol.svg#'
            . $name . '"></use></svg>';
        return new Markup($svg_icon_template, 'UTF-8');
    }

    /**
     * Set CSS properties that should be allowed for posts filter
     * Allow img object-position.
     *
     * @param array $allowedproperties Default allowed CSS properties.
     *
     */
    public function set_custom_allowed_css_properties(array $allowedproperties): array
    {
        $allowedproperties[] = 'object-position';
        $allowedproperties[] = '--spreadsheet-header-background';
        $allowedproperties[] = '--spreadsheet-even-row-background';
        $allowedproperties[] = '--spreadsheet-odd-row-background';

        return $allowedproperties;
    }

    /**
     * Set HTML attributes that should be allowed for posts filter
     * Allow img srcset and sizes attributes.
     * Allow iframes in posts.
     *
     * @see wp_kses_allowed_html()
     *
     * @param array  $allowedposttags Default allowed tags.
     * @param string $context         The context for which to retrieve tags.
     *
     */
    public function set_custom_allowed_attributes_filter(array $allowedposttags, string $context): array
    {
        if ('post' !== $context) {
            return $allowedposttags;
        }

        // Allow iframes and the following attributes.
        $allowedposttags['style'] = [];
        $allowedposttags['iframe'] = [
            'align' => true,
            'width' => true,
            'height' => true,
            'frameborder' => true,
            'name' => true,
            'src' => true,
            'srcdoc' => true,
            'id' => true,
            'class' => true,
            'style' => true,
            'scrolling' => true,
            'marginwidth' => true,
            'marginheight' => true,
            'allowfullscreen' => true,
        ];

        // Allow blockquote and the following attributes. (trigger: allow instagram embeds).
        $allowedposttags['blockquote'] = [
            'style' => true,
            'data-instgrm-captioned' => true,
            'data-instgrm-permalink' => true,
            'data-instgrm-version' => true,
            'class' => true,
        ];

        // Allow img and the following attributes.
        $allowedposttags['img'] = [
            'alt' => true,
            'class' => true,
            'id' => true,
            'height' => true,
            'hspace' => true,
            'name' => true,
            'src' => true,
            'srcset' => true,
            'sizes' => true,
            'width' => true,
            'style' => true,
            'vspace' => true,
            'loading' => true,
        ];

        $allowedposttags['script'] = [
            'src' => true,
            'id' => true,
            'data-*' => true,
            'onload' => true,
        ];

        // Allow source tag for WordPress audio shortcode to function.
        $allowedposttags['source'] = [
            'type' => true,
            'src' => true,
        ];

        // Allow below tags for carousel slider.
        $allowedposttags['div']['data-bs-ride'] = true;
        $allowedposttags['li']['data-bs-target'] = true;
        $allowedposttags['li']['data-bs-slide-to'] = true;
        $allowedposttags['a']['data-bs-slide'] = true;
        $allowedposttags['span']['aria-hidden'] = true;

        // Allow below tags for spreadsheet block.
        $allowedposttags['input'] = [
            'class' => true,
            'type' => true,
            'placeholder' => true,
        ];

        $allowedposttags['lite-youtube'] = [
            'videoid' => true,
            'params' => true,
            'style' => true,
        ];

        // Allow object tag with some attributes.
        $allowedposttags['object'] = [
            'class' => true,
            'data' => true,
            'id' => true,
            'type' => true,
            'style' => true,
            'aria-label' => true,
        ];

        return $allowedposttags;
    }

    /**
     * Sanitizes the settings input.
     *
     * @param string $setting The setting to sanitize.
     *
     * @return string The sanitized setting.
     */
    public function sanitize(string $setting): string
    {
        $allowed = [
            'ul' => [],
            'ol' => [],
            'li' => [],
            'strong' => [],
            'del' => [],
            'span' => [
                'style' => [],
            ],
            'p' => [
                'style' => [],
            ],
            'a' => [
                'href' => [],
                'target' => [],
                'rel' => [],
            ],
        ];
        return wp_kses($setting, $allowed);
    }

    /**
     * Load styling and behaviour on admin pages.
     */
    public function enqueue_admin_assets(): void
    {
        // Register jQuery 3 for use wherever needed by adding wp_enqueue_script( 'jquery-3' );.
        wp_register_script(
            'jquery-3',
            'https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js',
            [],
            '3.3.1',
            true
        );
    }

    /**
     * Registering into the Settings > Writing setting page.
    */
    public function add_post_revisions_setting(): void
    {
        register_setting('writing', 'revisions_to_keep');

        add_settings_field(
            'post-revisions-field',
            __('Post revisions', 'planet4-master-theme-backend'),
            function ($val): void {
                $id = $val['id'];
                $option_name = $val['option_name'];
                ?>
                    <input
                        type="number"
                        name="<?php echo esc_attr($option_name) ?>"
                        id="<?php echo esc_attr($id) ?>"
                        value="<?php echo esc_attr(get_option($option_name)) ?>"
                    />
                    <span>
                        <?php echo __(
                            'Maximum number of revisions to store for each post.',
                            'planet4-master-theme-backend'
                        )?>
                    </span>
                <?php
            },
            'writing',
            'default',
            array(
                'id' => 'post-revisions-field',
                'option_name' => 'revisions_to_keep',
            )
        );
    }

    /**
     * Add resources for the Gutenberg editor.
     */
    public function enqueue_editor_assets(): void
    {
        Loader::enqueue_versioned_style('assets/build/editorStyle.min.css', 'planet4-editor-style');
    }

    /**
     * Creates a Metabox on the side of the Add/Edit Post/Page
     * that is used for applying weight to the current Post/Page in search results.
     *
     * @param string  $post_type Post type.
     * @param WP_Post|WP_Comment $post      Post object.
     * phpcs:disable SlevomatCodingStandard.Functions.UnusedParameter -- add_meta_boxes callback
     */
    public function add_meta_box_search(string $post_type, $post): void
    {
        add_meta_box(
            'meta-box-search',
            'Search',
            [$this, 'view_meta_box_search'],
            ['post', 'page'],
            'side',
            'default',
            [$post]
        );
    }
    // phpcs:enable SlevomatCodingStandard.Functions.UnusedParameter

    /**
     * Renders a Metabox on the side of the Add/Edit Post/Page.
     *
     * @param WP_Post $post The currently Added/Edited post.
     * phpcs:disable Generic.WhiteSpace.ScopeIndent
     */
    public function view_meta_box_search(WP_Post $post): void
    {
        $weight = get_post_meta($post->ID, 'weight', true);
        $options = get_option('planet4_options');

        echo '<label for="my_meta_box_text">'
            . esc_html__('Weight', 'planet4-master-theme-backend')
            . ' (1-' . esc_attr(Search\Search::DEFAULT_MAX_WEIGHT) . ')</label>
                <input id="weight" type="text" name="weight" value="' . esc_attr($weight) . '" />';
?><script>
            $ = jQuery;
            $('#parent_id').off('change').on('change', function() {
                // Check selected Parent page and give bigger weight if it will be an Action page
                if ('<?php echo esc_js($options['act_page'] ?? -1); ?>' === $(this).val()) {
                    $('#weight').val(<?php echo esc_js(Search\Search::DEFAULT_ACTION_WEIGHT); ?>);
                } else {
                    $('#weight').val(<?php echo esc_js(Search\Search::DEFAULT_PAGE_WEIGHT); ?>);
                }
            });
        </script>
<?php
    }
    // phpcs:enable Generic.WhiteSpace.ScopeIndent

    /**
     * Applies filters for list of users in dropdown
     *
     * @param Array|null $args The filter options and values.
     */
    public function filter_authors(?array $args): ?array
    {
        if (isset($args['who'])) {
            $args['role__in'] = ['administrator', 'author', 'campaigner', 'contributor', 'editor'];
            unset($args['who']);
        }
        return $args;
    }

    /**
     * Forces a user to login using Google Auth if they have a greenpeace.org email
     *
     * @param WP_User|WP_Error|null $user The current user logging in.
     * @param String|null $username The username of the user.
     * @param String|null $password The password of the user.
     * @return WP_User|WP_Error|null
     */
    public function enforce_google_signon($user, ?string $username = null, ?string $password = null)
    {

        if (defined('WP_DEBUG') && WP_DEBUG === true) {
            return $user;
        }

        if (empty($username) || empty($password)) {
            return $user;
        }

        if (strpos($username, '@')) {
            $user_data = get_user_by('email', trim(wp_unslash($username)));
        } else {
            $login = trim($username);
            $user_data = get_user_by('login', $login);
        }

        if (empty($user_data) || is_wp_error($user)) {
            return $user;
        }

        $email_user_name = mb_substr($user_data->data->user_email, 0, strpos($user_data->data->user_email, '@'));

        // Dont enforce google login on aliases.
        if (strpos($email_user_name, '+')) {
            return $user;
        }

        $domain = '@greenpeace.org';
        if (mb_substr($user_data->data->user_email, -strlen($domain)) === $domain) {
            $this->google_login_error = true;
        }

        return $user;
    }

    /**
     * Checks if we have set a google login error earlier on so we can prevent login if google login wasn't used
     *
     * @param WP_User|WP_Error|null $user The current user logging in.
     *
     * @return WP_User|WP_Error|null
     */
    public function check_google_login_error($user)
    {
        if ($this->google_login_error) {
            $this->google_login_error = false;
            return new WP_Error(
                'google_login',
                __(
                    'You are trying to login with a Greenpeace email. Please use the Google login button instead.',
                    'planet4-master-theme-backend'
                )
            );
        }

        return $user;
    }

    /**
     * Saves the Search weight of the Post/Page.
     *
     * @param int     $post_id The ID of the current Post.
     * @param WP_Post $post The current Post.
     */
    public function save_meta_box_search(int $post_id, WP_Post $post): void
    {
        global $pagenow;

        // Ignore autosave.
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        // Check user's capabilities.
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        // Make sure there's input.
        $weight = filter_input(
            INPUT_POST,
            'weight',
            FILTER_VALIDATE_INT,
            [
                'options' => [
                    'min_range' => Search\Search::DEFAULT_MIN_WEIGHT,
                    'max_range' => Search\Search::DEFAULT_MAX_WEIGHT,
                ],
            ]
        );

        // If this is a new Page then set default weight for it.
        if (!$weight && 'post-new.php' === $pagenow) {
            if ('page' === $post->post_type) {
                $weight = Search\Search::DEFAULT_PAGE_WEIGHT;
            }
        }

        // Store weight.
        update_post_meta($post_id, 'weight', $weight);
    }

    /**
     * Registers taxonomies.
     */
    public function register_taxonomies(): void
    {
        register_taxonomy_for_object_type('post_tag', 'page');
        register_taxonomy_for_object_type('category', 'page');
    }

    /**
     * Declare meta fields
     */
    private function register_meta_fields(): void
    {
        // Credit for images, used in image caption.
        \register_post_meta(
            'attachment',
            self::CREDIT_META_FIELD,
            [
                'show_in_rest' => true,
                'type' => 'string',
                'single' => true,
            ]
        );

        \register_term_meta(
            'post_tag',
            'redirect_page',
            [
                'show_in_rest' => true,
                'type' => 'integer',
                'single' => true,
            ]
        );
    }

    /**
     * Registers oembed provider for Carto map.
     */
    public function register_oembed_provider(): void
    {
        wp_oembed_add_provider(
            '#https?://(?:www\.)?[^/^\.]+\.carto(db)?\.com/\S+#i',
            'https://services.carto.com/oembed',
            true
        );
    }

    /**
     * Auto generate excerpt for post.
     *
     * @param int     $post_id Id of the saved post.
     * @param WP_Post $post Post object.
     */
    public function p4_auto_generate_excerpt(int $post_id, WP_Post $post): void
    {
        if ('' !== $post->post_excerpt || 'post' !== $post->post_type) {
            return;
        }

        // Unhook save_post function so it doesn't loop infinitely.
        remove_action('save_post', [$this, 'p4_auto_generate_excerpt'], 10);

        // Generate excerpt text.
        $post_excerpt = strip_shortcodes($post->post_content);

        preg_match('/<p>(.*?)<\/p>/', $post_excerpt, $match_paragraph);

        $post_excerpt = $match_paragraph[1] ?? $post_excerpt;
        $post_excerpt = apply_filters('the_content', $post_excerpt);
        $post_excerpt = str_replace(']]>', ']]&gt;', $post_excerpt);
        $excerpt_length = apply_filters('excerpt_length', 30);
        $excerpt_more = apply_filters('excerpt_more', '&hellip;');
        $post_excerpt = wp_trim_words($post_excerpt, $excerpt_length, $excerpt_more);

        // Update the post, which calls save_post again.
        wp_update_post(
            [
                'ID' => $post_id,
                'post_excerpt' => $post_excerpt,
            ]
        );

        // re-hook save_post function.
        add_action('save_post', [$this, 'p4_auto_generate_excerpt'], 10, 2);
    }

    /**
     * Restrict creation of tags from all roles besides administrator.
     *
     * @param string $term The term to be added.
     * @param string $taxonomy Taxonomy slug.
     *
     * @return WP_Error|string
     */
    public function disallow_insert_term(string $term, string $taxonomy)
    {

        $user = wp_get_current_user();

        if ('post_tag' === $taxonomy && !in_array('administrator', (array) $user->roles, true)) {
            return new WP_Error(
                'disallow_insert_term',
                __('Your role does not have permission to add terms to this taxonomy', 'planet4-master-theme-backend')
            );
        }

        return $term;
    }

    /**
     * Add a help link to the Help sidebars.
     */
    public function add_help_sidebar(): void
    {
        if (!get_current_screen()) {
            return;
        }

        $screen = get_current_screen();
        $sidebar = $screen->get_help_sidebar();

        $sidebar .= '<p><a target="_blank" href="https://planet4.greenpeace.org/">Planet 4 Handbook</a></p>';

        $screen->set_help_sidebar($sidebar);
    }

    /**
     * Filter and add class to GDPR consent checkbox label after the GDPR fields appended to comment form submit field.
     *
     * @param string $submit_field The HTML content of comment form submit field.
     *
     * @return string HTML content of comment form submit field.
     */
    public function gdpr_cc_comment_form_add_class(string $submit_field): string
    {

        $pattern[0] = '/(for=["\']gdpr-comments-checkbox["\'])/';
        $replacement[0] = '$1 class="custom-control-description"';
        $pattern[1] = '/(id=["\']gdpr-comments-checkbox["\'])/';
        $replacement[1] = '$1 style="width:auto;"';
        $pattern[2] = '/id="gdpr-comments-compliance"/';
        $replacement[2] = 'id="gdpr-comments-compliance" class="custom-control"';

        $submit_field = preg_replace($pattern, $replacement, $submit_field);

        return $submit_field;
    }

    /**
     * Add classes to the default comment form cookie checkbox.
     *
     * @param array $fields The default fields of the comment form.
     *
     * @return array the new fields.
     */
    public function comment_form_cookie_checkbox_add_class(array $fields): array
    {

        if (isset($fields['cookies'])) {
            $pattern[0] = '/(class=["\']comment-form-cookies-consent["\'])/';
            $replacement[0] = 'class="comment-form-cookies-consent custom-control"';
            $pattern[1] = '/(for=["\']wp-comment-cookies-consent["\'])/';
            $replacement[1] = '$1 class="custom-control-description"';

            $fields['cookies'] = preg_replace($pattern, $replacement, $fields['cookies']);
        }

        return $fields;
    }

    /**
     * Use different templates for the comment form fields (name and email).
     * Also remove the website field since we don't want to use it.
     *
     * @param array $fields The default fields of the comment form.
     *
     * @return array the new fields.
     */
    public function comment_form_replace_inputs(array $fields): array
    {

        $fields['author'] = Timber::compile('comment_form/author_field.twig');
        $fields['email'] = Timber::compile('comment_form/email_field.twig');
        if (isset($fields['url'])) {
            unset($fields['url']);
        }

        return $fields;
    }

    /**
     * Filter function for embed_oembed_html.
     * Transform youtube embeds to youtube-nocookie.
     *
     * @see https://developer.wordpress.org/reference/hooks/embed_oembed_html/
     *
     * @param mixed  $cache The cached HTML result, stored in post meta.
     * @param string $url The attempted embed URL.
     *
     * @return mixed
     */
    public function filter_youtube_oembed_nocookie($cache, string $url)
    {
        if (LazyYoutubePlayer::is_active()) {
            return $this->new_youtube_filter($cache, $url);
        }

        return $this->old_youtube_filter($cache, $url);
    }

    /**
     * Filter function for embed_oembed_html.
     * Transform youtube embeds to youtube-nocookie.
     *
     * @see https://developer.wordpress.org/reference/hooks/embed_oembed_html/
     *
     * @param mixed  $cache The cached HTML result, stored in post meta.
     * @param string $url The attempted embed URL.
     *
     * @return mixed
     */
    private function new_youtube_filter($cache, string $url)
    {
        if (is_admin() || (defined('REST_REQUEST') && REST_REQUEST)) {
            return $cache;
        }

        if (!empty($url)) {
            if (strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false) {
                [$youtube_id, $query_string] = self::parse_youtube_url($url);

                $style = "background-image: url('https://i.ytimg.com/vi/$youtube_id/hqdefault.jpg');";

                return '<lite-youtube style="' . $style . '" videoid="' . $youtube_id
                    . '" params="' . $query_string . '"></lite-youtube>';
            }
        }

        return $cache;
    }

    /**
     * Filter function for embed_oembed_html.
     * Transform youtube embeds to youtube-nocookie.
     *
     * @see https://developer.wordpress.org/reference/hooks/embed_oembed_html/
     *
     * @param mixed  $cache The cached HTML result, stored in post meta.
     * @param string $url The attempted embed URL.
     *
     * @return mixed
     */
    private function old_youtube_filter($cache, string $url)
    {
        if (!empty($url)) {
            if (strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false) {
                $replacements = [
                    'youtube.com' => 'youtube-nocookie.com',
                    'feature=oembed' => 'feature=oembed&rel=0',
                ];

                $cache = str_replace(array_keys($replacements), array_values($replacements), $cache);
            }
        }

        return $cache;
    }

    /**
     * Parse info out of a Youtube URL.
     *
     * @param string $url The embedded url.
     *
     * @return string[] The youtube ID and the query string.
     */
    private static function parse_youtube_url(string $url): ?array
    {
        // @see https://stackoverflow.com/questions/3392993/php-regex-to-get-youtube-video-id
        // phpcs:ignore Generic.Files.LineLength.MaxExceeded
        $re = "/^(?:http(?:s)?:\/\/)?(?:www\.)?(?:m\.)?(?:youtu\.be\/|youtube\.com\/(?:(?:watch)?\?(?:.*&)?v(?:i)?=|(?:embed|v|vi|user|shorts)\/))([^\?&\"'>]+)/";
        preg_match_all($re, $url, $matches, PREG_SET_ORDER);
        $youtube_id = $matches[0][1] ?? null;

        // For now just rel, but we can extract more from the url.
        $query_string = apply_filters('planet4_youtube_embed_parameters', 'rel=0');

        return [$youtube_id, $query_string];
    }

    /**
     * Add custom media metadata fields.
     *
     * @param array    $form_fields An array of fields included in the attachment form.
     * @param \WP_Post $post The attachment record in the database.
     *
     * @return array Final array of form fields to use.
     */
    public function add_image_attachment_fields_to_edit(array $form_fields, \WP_Post $post): array
    {

        // Add a Credit field.
        $form_fields['credit_text'] = [
            'label' => __('Credit', 'planet4-master-theme-backend'),
            'input' => 'text', // this is default if "input" is omitted.
            'value' => get_post_meta($post->ID, self::CREDIT_META_FIELD, true),
            'helps' => __('The owner of the image.', 'planet4-master-theme-backend'),
        ];

        return $form_fields;
    }

    /**
     * Save custom media metadata fields
     *
     * @param array $post        The $post data for the attachment.
     * @param array $attachment  The $attachment part of the form $_POST ($_POST[attachments][postID]).
     *
     * @return array $post
     */
    public function add_image_attachment_fields_to_save(array $post, array $attachment): array
    {
        if (isset($attachment['credit_text'])) {
            update_post_meta($post['ID'], self::CREDIT_META_FIELD, $attachment['credit_text']);
        }

        return $post;
    }

    /**
     * Override the Gutenberg core/image block render method output,
     * to add credit field in it's caption text & image alt text as title.
     *
     * @param array  $attributes    Attributes of the Gutenberg core/image block.
     * @param string $content The image element HTML.
     *
     * @return string HTML content of image element with credit field in caption and alt text in image title.
     */
    public function p4_core_image_block_render(array $attributes, string $content): string
    {
        $image_id = isset($attributes['id']) ? trim(str_replace('attachment_', '', $attributes['id'])) : '';
        $img_post_meta = $image_id ? get_post_meta($image_id) : [];
        if (!$img_post_meta) {
            return $content;
        }

        $credit = $img_post_meta[self::CREDIT_META_FIELD][0] ?? '';
        $alt_text = $img_post_meta['_wp_attachment_image_alt'][0] ?? '';

        if ($alt_text) {
            $content = str_replace(' alt=', ' title="' . esc_attr($alt_text) . '" alt=', $content);
        }

        $image_credit = ' ' . $credit;
        if (false === strpos($credit, '©')) {
            $image_credit = ' ©' . $image_credit;
        }

        $caption = '';

        $pattern = '/<figcaption[^>]*>(.*?)<\/figcaption>/';
        if (preg_match($pattern, $content, $matches)) {
            $caption = $matches[1];
        }

        if (empty($credit) || (!empty($caption) && strpos($caption, $image_credit) !== false)) {
            return $content;
        }

        return str_replace(
            empty($caption) ? '</figure>' : $caption,
            empty($caption) ?
                '<figcaption>' . esc_attr($image_credit) . '</figcaption></figure>' :
                $caption . esc_attr($image_credit),
            $content
        );
    }

    /**
     * Add callback function to Gutenberg core/image block.
     */
    public function p4_register_core_image_block(): void
    {
        unregister_block_type('core/image');
        register_block_type(
            'core/image',
            ['render_callback' => [$this, 'p4_core_image_block_render']]
        );
    }

    /**
     * Add callback function to Gutenberg core/image block.
     *
     * @param array $args Parameters given during block register.
     *
     * @return array Parameters of the block.
     */
    public function register_core_blocks_callback(array $args): array
    {
        if ('core/image' === $args['name']) {
            $args['render_callback'] = [$this, 'p4_core_image_block_render'];
        }

        return $args;
    }

    /**
     * Show P4 team message on dashboard.
     */
    public function show_dashboard_notice(): void
    {
        // Show only on dashboard.
        $screen = get_current_screen();
        if (null === $screen || 'dashboard' !== $screen->id) {
            return;
        }

        // Don't show a dismissed version.
        $last_notice = get_user_meta(get_current_user_id(), self::DASHBOARD_MESSAGE_KEY, true);
        if (version_compare(self::DASHBOARD_MESSAGE_VERSION, $last_notice, '<=')) {
            return;
        }

        // Don't show an empty message.
        $message = trim($this->p4_message());
        if (empty($message)) {
            return;
        }

        echo '<div id="p4-notice" class="notice notice-info is-dismissible">'
            . wp_kses_post($message)
            . '</div>'
            . "<script>(function() {
                jQuery('#p4-notice').on('click', '.notice-dismiss', () => {
                    jQuery.post(ajaxurl, {'action': 'dismiss_dashboard_notice'}, function(response) {
                        jQuery('#p4-notice').hide();
                    });
                });
            })();</script>
            ";
    }

    /**
     * A message from Planet4 team.
     *
     * Message title should be a <h2> tag.
     * Message text should be written into <p> tags.
     * Return an empty string if no message for this version.
     *
     * Version number DASHBOARD_MESSAGE_VERSION has to be incremented
     * each time we add a new message.
     *
     * phpcs:disable Generic.Files.LineLength.MaxExceeded
     */
    private function p4_message(): string
    {
        return '<h2><a href="https://forms.gle/c9qaH2g5t8Q7VhAw6" target="_blank">Please take this survey on the new Media Archive</a></h2>
            <p>We want to hear from you on your experience with the Media Archive (now called Greenpeace Media) - it should just take a few minutes!</p>';
    }
    // phpcs:enable Generic.Files.LineLength.MaxExceeded

    /**
     * Dismiss P4 notice of dashboard, by saving the last version read in user meta field.
     *
     * @uses wp_die()
     */
    public function dismiss_dashboard_notice(): void
    {
        $user_id = get_current_user_id();
        if (0 === $user_id) {
            wp_die('User not logged in.', 401);
        }

        $res = update_user_meta(
            $user_id,
            self::DASHBOARD_MESSAGE_KEY,
            self::DASHBOARD_MESSAGE_VERSION
        );
        if (false === $res) {
            wp_die('User meta update failed.', 500);
        }

        wp_die('Notice dismissed.', 200);
    }

    /**
     * Remove block directory assets and rest endpoint.
     */
    public function disable_block_directory(): void
    {
        remove_action('enqueue_block_editor_assets', 'wp_enqueue_editor_block_directory_assets');
        add_filter('rest_endpoints', [$this, 'disable_block_directory_endpoint']);
    }

    /**
     * Remove block directory endpoint.
     *
     * @param array $endpoints The available endpoints.
     *
     */
    public function disable_block_directory_endpoint(array $endpoints): array
    {
        unset($endpoints['/wp/v2/block-directory/search']);
        return $endpoints;
    }

    /**
     * Set post tags options to retain their order on save and return them ordered
     *
     * @param array  $args     Array of arguments for registering a taxonomy.
     * @param string $taxonomy Taxonomy key.
     *
     * @return array $args Array of arguments for registering a taxonomy.
     */
    public function set_post_tags_as_ordered(array $args, string $taxonomy): array
    {
        if ('post_tag' !== $taxonomy) {
            return $args;
        }

        $args['sort'] = true;
        $args['args'] = ['orderby' => 'term_order'];
        return $args;
    }

    /**
     * Look up the ID of the global campaign and save it on the Post/Page.
     *
     * @param int     $post_id The ID of the current Post.
     */
    public function save_global_project_id(int $post_id): void
    {
        // Ignore autosave.
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        // Check user's capabilities.
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        $p4_campaign_name = get_post_meta($post_id, 'p4_campaign_name', true);
        $old_project_id = get_post_meta($post_id, 'p4_global_project_tracking_id', true);
        $project_id = AnalyticsValues::from_cache_or_api_or_hardcoded()->get_id_for_global_project($p4_campaign_name);
        // phpcs:ignore SlevomatCodingStandard.ControlStructures.EarlyExit.EarlyExitNotUsed
        if ('not set' !== $project_id && $old_project_id !== $project_id) {
            update_post_meta($post_id, 'p4_global_project_tracking_id', $project_id);
        }
    }
}
