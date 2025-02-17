<?php

namespace P4\MasterTheme;

use CMB2_Field;
use P4\MasterTheme\Settings\Features;

/**
 * Class P4\MasterTheme\Settings
 */
class Settings
{
    /**
     * ID of the Metabox
     */
    public const METABOX_ID = 'option_metabox';

    /**
     * Option key
     *
     */
    public const KEY = 'planet4_options';

    /**
     * Option page slug
     *
     */
    private string $slug = 'planet4_settings_navigation';

    /**
     * Options Page title
     *
     */
    protected string $title = '';

    /**
     * Options Page hook
     *
     */
    protected string $options_page = '';

    /**
     * Subpages
     *
     * @var array
     * Includes arrays with the title and fields of each subpage
     */
    protected array $subpages = [];

    /**
     * Constructor
     */
    public function __construct()
    {

        // Set our title.
        $this->title = __('Planet 4', 'planet4-master-theme-backend');

        // Set our subpages.
        // Each subpage has a title and a path and the fields.
        // phpcs:disable Generic.Files.LineLength.MaxExceeded
        $is_new_ia = !empty(planet4_get_option('new_ia'));
        $anaylical_cookies_enabled = !empty(planet4_get_option('enable_analytical_cookies'));
        $this->subpages = [
            'planet4_settings_navigation' => [
                'title' => 'Navigation',
                'fields' => [
                    [
                        'name' => __('Select Act Page', 'planet4-master-theme-backend'),
                        'id' => 'act_page',
                        'type' => 'act_page_dropdown',
                        'classes' => $is_new_ia ? 'hidden' : '',
                    ],
                    [
                        'name' => __('Select Explore Page', 'planet4-master-theme-backend'),
                        'id' => 'explore_page',
                        'type' => 'explore_page_dropdown',
                        'classes' => $is_new_ia ? 'hidden' : '',
                    ],
                    [
                        'name' => __('Select Issues Parent Category', 'planet4-master-theme-backend'),
                        'id' => 'issues_parent_category',
                        'type' => 'category_select_taxonomy',
                        'classes' => $is_new_ia ? 'hidden' : '',
                    ],
                    [
                        'name' => __('New Information Architecture', 'planet4-master-theme-backend'),
                        'desc' => __('Enables all features supporting new IA and navigation functionality (<a href="https://planet4.greenpeace.org/manage/information-architecture/" target="_blank">read more</a>)<br>(eg. Actions post type, listing pages pagination, mobile tabs, etc).', 'planet4-master-theme-backend'),
                        'id' => 'new_ia',
                        'type' => 'checkbox',
                    ],
                    [
                        'name' => __('Country Selector text', 'planet4-master-theme-backend'),
                        'id' => 'website_navigation_title',
                        'type' => 'text',
                    ],
                    [
                        'name' => __('Copyright Text Line 1', 'planet4-master-theme-backend'),
                        'id' => 'copyright_line1',
                        'type' => 'wysiwyg',
                        'options' => [
                            'textarea_rows' => 3,
                            'media_buttons' => false,
                        ],
                    ],
                    [
                        'name' => __('Copyright Text Line 2', 'planet4-master-theme-backend'),
                        'id' => 'copyright_line2',
                        'type' => 'wysiwyg',
                        'options' => [
                            'textarea_rows' => 2,
                            'media_buttons' => false,
                        ],
                    ],
                ],
            ],
            'planet4_settings_defaults_content' => [
                'title' => 'Defaults content',
                'fields' => [
                    [
                        'name' => __('Take Action Covers default button text', 'planet4-master-theme-backend'),
                        'id' => 'take_action_covers_button_text',
                        'type' => 'text',
                        'attributes' => [
                            'type' => 'text',
                        ],
                        'desc' => __('Add default button text which appears on <b>Take Action</b> card of <b>Take Action Covers</b> block. <br>Also it would be used for Take Action Cards inside Posts and Take Action Cards in search results', 'planet4-master-theme-backend'),
                    ],

                    // Happy Point settings.
                    [
                        'name' => __('Default Happy Point Background Image', 'planet4-master-theme-backend'),
                        'id' => 'happy_point_bg_image',
                        'type' => 'file',
                        'options' => [
                            'url' => false,
                        ],
                        'text' => [
                            'add_upload_file_text' => __(
                                'Add Default Happy Point Background Image',
                                'planet4-master-theme-backend'
                            ),
                        ],
                        'query_args' => [
                            'type' => 'image',
                        ],
                        'desc' => __('Minimum image width should be 1920px', 'planet4-master-theme-backend'),
                    ],

                    [
                        'name' => __('Default Happy Point Form', 'planet4-master-theme-backend'),
                        'id' => 'happy_point_content_provider',
                        'type' => 'radio',
                        'options' => [
                            'iframe_url' => __('URL (for iframe)', 'planet4-master-theme-backend'),
                            'embed_code' => __('Embed code (HubSpot)', 'planet4-master-theme-backend'),
                        ],
                        'default' => 'iframe_url',
                    ],

                    [
                        'name' => __('Happy Point Subscribe Form URL', 'planet4-master-theme-backend'),
                        'id' => 'engaging_network_form_id',
                        'type' => 'text',
                    ],

                    [
                        'name' => __('Happy Point HubSpot embed code', 'planet4-master-theme-backend'),
                        'id' => 'happy_point_embed_code',
                        'type' => 'wysiwyg',
                        'options' => [
                            'textarea_rows' => 10,
                            'media_buttons' => false,
                        ],
                    ],

                    [
                        'name' => __('Old Posts Archive notice cutoff years', 'planet4-master-theme-backend'),
                        'id' => 'old_posts_archive_notice_cutoff',
                        'type' => 'text',
                        'desc' => __('Condition to determine when to show the notice, in years.<br>Any Post older than that will have the notice displayed on the top.', 'planet4-master-theme-backend'),
                    ],

                    [
                        'name' => __('Old Posts Archive notice title', 'planet4-master-theme-backend'),
                        'id' => 'old_posts_archive_notice_title',
                        'type' => 'text',
                    ],

                    [
                        'name' => __('Old Posts Archive notice description', 'planet4-master-theme-backend'),
                        'id' => 'old_posts_archive_notice_description',
                        'type' => 'textarea',
                    ],

                    [
                        'name' => __('Old Posts Archive button text', 'planet4-master-theme-backend'),
                        'id' => 'old_posts_archive_notice_button',
                        'type' => 'text',
                    ],
                ],
            ],
            'planet4_settings_cookies_text' => [
                'title' => 'Cookies',
                'fields' => [
                    [
                        'name' => __('Cookies Box General Text', 'planet4-master-theme-backend'),
                        'id' => 'cookies_field',
                        'type' => 'wysiwyg',
                        'options' => [
                            'textarea_rows' => 5,
                            'media_buttons' => false,
                        ],
                    ],
                    [
                        'name' => __('Necessary Cookies label', 'planet4-master-theme-backend'),
                        'id' => 'necessary_cookies_name',
                        'type' => 'text',
                    ],
                    [
                        'name' => __('Necessary Cookies description', 'planet4-master-theme-backend'),
                        'id' => 'necessary_cookies_description',
                        'type' => 'wysiwyg',
                        'options' => [
                            'textarea_rows' => 2,
                            'media_buttons' => false,
                            'quicktags' => [
                                'buttons' => 'strong,em',
                            ],
                        ],
                    ],

                    [
                        'name' => __('Marketing Cookies label', 'planet4-master-theme-backend'),
                        'id' => 'all_cookies_name',
                        'type' => 'text',
                    ],
                    [
                        'name' => __('Marketing Cookies description', 'planet4-master-theme-backend'),
                        'id' => 'all_cookies_description',
                        'type' => 'wysiwyg',
                        'options' => [
                            'textarea_rows' => 2,
                            'media_buttons' => false,
                            'quicktags' => [
                                'buttons' => 'strong,em',
                            ],
                        ],
                    ],

                    [
                        'name' => __('Enable Analytical Cookies', 'planet4-master-theme-backend'),
                        'desc' => __(
                            'Enable the Analytical cookies option in Cookies block and box',
                            'planet4-master-theme-backend'
                        ),
                        'id' => 'enable_analytical_cookies',
                        'type' => 'checkbox',
                    ],

                    [
                        'name' => __('Analytical Cookies label', 'planet4-master-theme-backend'),
                        'id' => 'analytical_cookies_name',
                        'type' => 'text',
                        'classes' => $anaylical_cookies_enabled ? '' : 'hidden',
                    ],
                    [
                        'name' => __('Analytical Cookies description', 'planet4-master-theme-backend'),
                        'id' => 'analytical_cookies_description',
                        'type' => 'wysiwyg',
                        'options' => [
                            'textarea_rows' => 2,
                            'media_buttons' => false,
                            'quicktags' => [
                                'buttons' => 'strong,em',
                            ],
                        ],
                        'classes' => $anaylical_cookies_enabled ? '' : 'hidden',
                    ],

                    [
                        'name' => __('Enforce Cookies Policy', 'planet4-master-theme-backend'),
                        'desc' => __(
                            'GDPR related setting. By enabling this option specific content will be blocked and will require user consent to be shown.<br>
                            <b>WARNING:</b> If the setting is checked this will prevent Google Tag Manager container from being fired unless the user has accepted the cookies policy (giving consent in the cookies bar).<br>
                            This might affect the data collected with Google Analytics. For more information please see the documentation in the  <a href="https://planet4.greenpeace.org/handbook/block-cookies/">Planet 4 Handbook</a>.',
                            'planet4-master-theme-backend'
                        ),
                        'id' => 'enforce_cookies_policy',
                        'type' => 'checkbox',
                    ],

                    [
                        'name' => __('Reject all cookies', 'planet4-master-theme-backend'),
                        'desc' => __('Add the "Reject all" option in the Cookies box', 'planet4-master-theme-backend'),
                        'id' => 'enable_reject_all_cookies',
                        'type' => 'checkbox',
                    ],
                    [
                        'name' => __('Enable Google Consent Mode', 'planet4-master-theme-backend'),
                        'desc' => __("Enabling the Consent Mode will affect your setup in Google Tag Manager. The Consent Mode will prevent tags with built-in consent checks (eg. Google Analytics) from running before the user's consent is granted.", 'planet4-master-theme-backend'),
                        'id' => 'enable_google_consent_mode',
                        'type' => 'checkbox',
                    ],
                    [
                        'name' => __('Consent default: analytics_storage', 'planet4-master-theme-backend'),
                        'desc' => __(
                            'The default value for analytics_storage consent before visitors make their choice in the cookies box (Google Consent Mode V2).',
                            'planet4-master-theme-backend'
                        ),
                        'id' => 'consent_default_analytics_storage',
                        'type' => 'select',
                        'default' => 'denied',
                        'options' => [
                            'denied' => __('Denied', 'planet4-master-theme-backend'),
                            'granted' => __('Granted', 'planet4-master-theme-backend'),
                        ],
                    ],
                    [
                        'name' => __('Consent default: ad_storage', 'planet4-master-theme-backend'),
                        'desc' => __(
                            'The default value for ad_storage consent before visitors make their choice in the cookies box (Google Consent Mode V2).',
                            'planet4-master-theme-backend'
                        ),
                        'id' => 'consent_default_ad_storage',
                        'type' => 'select',
                        'default' => 'denied',
                        'options' => [
                            'denied' => __('Denied', 'planet4-master-theme-backend'),
                            'granted' => __('Granted', 'planet4-master-theme-backend'),
                        ],
                    ],
                    [
                        'name' => __('Consent default: ad_user_data', 'planet4-master-theme-backend'),
                        'desc' => __(
                            'The default value for ad_user_data consent before visitors make their choice in the cookies box (Google Consent Mode V2).',
                            'planet4-master-theme-backend'
                        ),
                        'id' => 'consent_default_ad_user_data',
                        'type' => 'select',
                        'default' => 'denied',
                        'options' => [
                            'denied' => __('Denied', 'planet4-master-theme-backend'),
                            'granted' => __('Granted', 'planet4-master-theme-backend'),
                        ],
                    ],
                    [
                        'name' => __('Consent default: ad_personalization', 'planet4-master-theme-backend'),
                        'desc' => __(
                            'The default value for ad_personalization consent before visitors make their choice in the cookies box (Google Consent Mode V2).',
                            'planet4-master-theme-backend'
                        ),
                        'id' => 'consent_default_ad_personalization',
                        'type' => 'select',
                        'default' => 'denied',
                        'options' => [
                            'denied' => __('Denied', 'planet4-master-theme-backend'),
                            'granted' => __('Granted', 'planet4-master-theme-backend'),
                        ],
                    ],
                ],
            ],
            'planet4_settings_social' => [
                'title' => 'Social',
                'fields' => [
                    [
                        'name' => __('Facebook Page ID', 'planet4-master-theme-backend'),
                        'id' => 'facebook_page_id',
                        'type' => 'text',
                        'desc' => __(
                            'This is used by Facebook Insights to provide analytics for traffic to your site from Facebook.<br>
                            Find the app ID in your <a href="https://business.facebook.com/business/loginpage/" target="_blank">App Dashboard</a>.',
                            'planet4-master-theme-backend'
                        ),
                    ],
                    [
                        'name' => __('Facebook App Access Token', 'planet4-master-theme-backend'),
                        'id' => 'fb_app_access_token',
                        'type' => 'text',
                        'desc' => __(
                            'This is used by the Meta block to fetch Facebook & Instagram embed information.<br>
                            Alternatively you can use your own, if you have created a Business Facebook App
                            (<a href="https://developers.facebook.com/docs/facebook-login/guides/access-tokens/#apptokens" target="_blank">Read more</a>).<br>
                            You could add App ID and App Secret separated by a | character, instead of a Token (eg. {your-app_id}|{your-app_secret}).',
                            'planet4-master-theme-backend'
                        ),
                    ],
                    [
                        'name' => __('Preconnect Domains', 'planet4-master-theme-backend'),
                        'desc' => __(
                            'Add a list of frequently used third-party services to "preconnect" (e.g.: https://in.hotjar.com).',
                            'planet4-master-theme-backend'
                        ),
                        'id' => 'preconnect_domains',
                        'type' => 'textarea',
                        'attributes' => [
                            'type' => 'text',
                        ],
                    ],
                ],
            ],
            'planet4_settings_404_page' => [
                'title' => '404 Page',
                'fields' => [
                    [
                        'name' => __('404 Background Image', 'planet4-master-theme-backend'),
                        'id' => '404_page_bg_image',
                        'type' => 'file',
                        'options' => [
                            'url' => false,
                        ],
                        'text' => [
                            'add_upload_file_text' => __(
                                'Add 404 Page Background Image',
                                'planet4-master-theme-backend'
                            ),
                        ],
                        'query_args' => [
                            'type' => 'image',
                        ],
                        'desc' => __('Minimum image width should be 1920px', 'planet4-master-theme-backend'),
                    ],

                    [
                        'name' => __('404 Page text', 'planet4-master-theme-backend'),
                        'id' => '404_page_text',
                        'type' => 'wysiwyg',
                        'options' => [
                            'textarea_rows' => 3,
                            'media_buttons' => false,
                        ],
                        'desc' => __('Add 404 page text', 'planet4-master-theme-backend'),
                    ],
                ],
            ],
            'planet4_settings_analytics' => [
                'title' => 'Analytics',
                'fields' => [
                    [
                        'name' => __('Google Tag Manager Container', 'planet4-master-theme-backend'),
                        'id' => 'google_tag_manager_identifier',
                        'type' => 'text',
                    ],
                    [
                        'name' => __('Google Tag Manager Custom Domain', 'planet4-master-theme-backend'),
                        'desc' => __(
                            'Use a custom domain from which Tag Manager scripts are loaded. A Google Tag Manager Web Container must be set up in server-side Tag Manager on the same domain. Leave empty for the default.',
                            'planet4-master-theme-backend'
                        ),
                        'id' => 'google_tag_manager_domain',
                        'type' => 'text',
                    ],
                    [
                        'name' => __('Local Projects Google Sheet ID', 'planet4-master-theme-backend'),
                        'desc' => __(
                            'The Google Sheet that is used to get analytics values from local(NRO) spreadsheet.',
                            'planet4-master-theme-backend'
                        ),
                        'id' => 'analytics_local_google_sheet_id',
                        'type' => 'text',
                    ],
                    // New IA special pages.
                    [
                        'name' => __('Select "Get Informed" page', 'planet4-master-theme-backend'),
                        'id' => 'get_informed_page',
                        'type' => $is_new_ia ? 'get_informed_page_dropdown' : 'hidden',
                    ],
                    [
                        'name' => __('Select "Take Action" page', 'planet4-master-theme-backend'),
                        'id' => 'take_action_page',
                        'type' => $is_new_ia ? 'take_action_page_dropdown' : 'hidden',
                    ],
                    [
                        'name' => __('Select "About Us" page', 'planet4-master-theme-backend'),
                        'id' => 'about_us_page',
                        'type' => $is_new_ia ? 'about_us_page_dropdown' : 'hidden',
                    ],
                    [
                        'name' => __('VWO A/B testing', 'planet4-master-theme-backend'),
                        'desc' => __(
                            'Adds a Content Security Policy exception for VWO.',
                            'planet4-master-theme-backend'
                        ),
                        'id' => 'enable_vwo',
                        'type' => 'checkbox',
                    ],
                ],
            ],
            'planet4_settings_features' => Features::get_options_page(),
        ];

        // This option should be visible only if the ElasticPress plugin is activated.
        $is_elasticpress = function_exists('is_plugin_active') && is_plugin_active('elasticpress/elasticpress.php');
        if ($is_elasticpress) {
            $search_content = array(
                'planet4_settings_search_content' => [
                    'title' => 'Search content',
                    'fields' => [
                        [
                            'name' => __('Include archived content in search for', 'planet4-master-theme-backend'),
                            'desc' => __(
                                '<b>Important:</b> On change of Include archive content setting, Please kindly',
                                'planet4-master-theme-backend'
                            ) . ' <a href="admin.php?page=elasticpress-sync">Sync Elasticsearch</a>.',
                            'id' => 'include_archive_content_for',
                            'type' => 'select',
                            'default' => 'nobody',
                            'options' => [
                                'nobody' => __('Nobody', 'planet4-master-theme-backend'),
                                'logged_in' => __('Logged in users', 'planet4-master-theme-backend'),
                                'all' => __('All users', 'planet4-master-theme-backend'),
                            ],
                        ],
                        [
                            'name' => __('Search content decay', 'planet4-master-theme-backend'),
                            'desc' => __('Amount of lowering of the relevancy score for older results. Between 0 and 1. The lower this number is, the lower older content will be ranked. See image. <br>We use the exponential function (exp, green curve).<br/> <img style="max-width:350px" alt="ElasticSearch decay function graph" src="https://www.elastic.co/guide/en/elasticsearch/reference/current/images/decay_2d.png">', 'planet4-master-theme-backend'),
                            'id' => 'epwr_decay',
                            'type' => 'text',
                        ],
                        [
                            'name' => __('Search content decay scale', 'planet4-master-theme-backend'),
                            'desc' => __(
                                'Timescale for lowering the relevance of older results. See image above.',
                                'planet4-master-theme-backend'
                            ),
                            'id' => 'epwr_scale',
                            'type' => 'text',
                        ],
                        [
                            'name' => __('Search content decay offset', 'planet4-master-theme-backend'),
                            'desc' => __(
                                'How old should a post be before relevance is lowered. See image above.',
                                'planet4-master-theme-backend'
                            ),
                            'id' => 'epwr_offset',
                            'type' => 'text',
                        ],
                    ],
                ],
            );
            $this->subpages = array_merge($this->subpages, $search_content);
        }
        // This option should be visible only if the GF Hubspot add-on is activated.
        $is_gf_hubspot_addon = function_exists('is_plugin_active') && is_plugin_active('gravityformshubspot/hubspot.php');
        if ($is_gf_hubspot_addon) {
            array_push(
                $this->subpages['planet4_settings_analytics']['fields'],
                [
                    'name' => __('Hubspot tracking code', 'planet4-master-theme-backend'),
                    'desc' => __(
                        'Paste here the tracking code from your Hubspot account.',
                        'planet4-master-theme-backend'
                    ),
                    'id' => 'hubspot_tracking_code',
                    'type' => 'textarea',
                    'attributes' => [
                        'type' => 'text',
                    ],
                ],
            );
        }

        $this->hooks();
    }

    /**
     * Initiate our hooks
     */
    public function hooks(): void
    {
        add_action('admin_init', [$this, 'init']);
        add_action('admin_menu', [$this, 'add_options_pages']);
        add_action('cmb2_save_options-page_fields_' . self::METABOX_ID, [$this, 'add_notifications']);
        add_filter('cmb2_render_act_page_dropdown', [$this, 'p4_render_page_dropdown'], 10, 2);
        add_filter('cmb2_render_explore_page_dropdown', [$this, 'p4_render_page_dropdown'], 10, 2);
        add_filter('cmb2_render_category_select_taxonomy', [$this, 'p4_render_category_dropdown'], 10, 2);
        add_filter('cmb2_render_get_informed_page_dropdown', [$this, 'p4_render_page_dropdown'], 10, 2);
        add_filter('cmb2_render_take_action_page_dropdown', [$this, 'p4_render_page_dropdown'], 10, 2);
        add_filter('cmb2_render_about_us_page_dropdown', [$this, 'p4_render_page_dropdown'], 10, 2);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

        // Make settings multilingual if wpml plugin is installed and activated.
        if (function_exists('is_plugin_active') && is_plugin_active('sitepress-multilingual-cms/sitepress.php')) {
            add_action('init', [$this, 'make_settings_multilingual']);
        }

        Features::hooks();
    }

    /**
     * Register our setting to WP.
     */
    public function init(): void
    {
        register_setting(self::KEY, self::KEY);
    }

    /**
     * Add menu options page.
     */
    public function add_options_pages(): void
    {
        $this->options_page = add_menu_page(
            $this->title,
            $this->title,
            'manage_options',
            $this->slug,
            null,
            'dashicons-admin-site-alt'
        );
        foreach ($this->subpages as $path => $subpage) {
            add_submenu_page(
                $this->slug,
                $subpage['title'],
                $subpage['title'],
                'manage_options',
                $path,
                fn () => $this->admin_page_display($path)
            );
        }
    }

    /**
     * Display notifications of success and error
     * This is the method used by WordPress to add a success notification
     *
     * @see https://github.com/WordPress/WordPress/blob/57fb3c6cf016678ab38d7a636b8df41fa2d955f1/wp-admin/options.php#L313
     */
    public function add_notifications(): void
    {
        if (!count(get_settings_errors())) {
            add_settings_error(
                'general',
                'settings_updated',
                __('Settings saved.', 'planet4-master-theme-backend'),
                'success'
            );
        }

        settings_errors();
    }

    /**
     * Render act page dropdown.
     *
     * @param CMB2_Field $field_args Field arguments.
     * @param mixed $value Value.
     */
    public function p4_render_page_dropdown(CMB2_Field $field_args, $value): void
    {
        wp_dropdown_pages(
            [
                'show_option_none' => esc_html__('Select Page', 'planet4-master-theme-backend'),
                'hide_empty' => 0,
                'hierarchical' => true,
                'selected' => esc_attr($value),
                'name' => $field_args->id(),
            ]
        );
    }

    /**
     * Render category dropdown.
     *
     * @param CMB2_Field $field_args Field arguments.
     * @param mixed $value Value.
     * phpcs:disable SlevomatCodingStandard.Functions.UnusedParameter -- add_filter callback
     */
    public function p4_render_category_dropdown(CMB2_Field $field_args, $value): void
    {

        wp_dropdown_categories(
            [
                'show_option_none' => __('Select Category', 'planet4-master-theme-backend'),
                'hide_empty' => 0,
                'hierarchical' => true,
                'orderby' => 'name',
                'selected' => $value,
                'name' => 'issues_parent_category',
            ]
        );
    }
    // phpcs:enable SlevomatCodingStandard.Functions.UnusedParameter

    /**
     * Admin page markup. Mostly handled by CMB2.
     *
     * @param string $plugin_page The key for the current page.
     */
    public function admin_page_display(string $plugin_page): void
    {
        $page_config = $this->subpages[$plugin_page];

        $fields = $page_config['fields'];
        $description = $page_config['description'] ?? null;
        // Allow storing options in a different database record.
        $root_option = $page_config['root_option'] ?? self::KEY;

        $add_scripts = $this->subpages[$plugin_page]['add_scripts'] ?? null;
        if (is_callable($add_scripts)) {
            $add_scripts();
        }

        $form = cmb2_metabox_form(
            $this->option_metabox($fields, $root_option),
            $root_option,
            ['echo' => false]
        );

        echo sprintf(
            '<div class="wrap %s">
				<h2>%s</h2>
				%s
				%s
			</div>',
            esc_attr(self::KEY),
            esc_html(get_admin_page_title()),
            wp_kses($description ? '<div>' . $description . '</div>' : '', 'post'),
            $form // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        );
    }

    /**
     * Defines the theme option metabox and field configuration.
     *
     * @param array  $fields expects the fields (if they exist) of this subpage.
     * @param string $option_id Key of option to store serialized array in.
     *
     */
    public function option_metabox(array $fields, string $option_id): array
    {
        return [
            'id' => self::METABOX_ID,
            'show_on' => [
                'key' => 'options-page',
                'value' => [
                    $option_id,
                ],
            ],
            'show_names' => true,
            'fields' => $fields,
        ];
    }

    /**
     * Hook for wpml plugin.
     * Enables the possibility to save a different value per language
     * for the theme options using WPML language switcher.
     */
    public function make_settings_multilingual(): void
    {
        do_action('wpml_multilingual_options', 'planet4_options');
    }

    /**
     * Loads options assets.
     */
    public function enqueue_admin_assets(): void
    {
        wp_register_style(
            'options-style',
            get_template_directory_uri() . '/admin/css/options.css',
            [],
            Loader::theme_file_ver('admin/css/options.css')
        );
        wp_enqueue_style('options-style');

        wp_enqueue_script(
            'options-script',
            get_template_directory_uri() . '/admin/js/options.js',
            [],
            Loader::theme_file_ver('admin/js/options.js'),
            true
        );
        wp_enqueue_script('options-script');
    }
}
