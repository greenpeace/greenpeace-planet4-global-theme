<?php

namespace P4\MasterTheme;

use P4\MasterTheme\Controllers\EnsapiController;
use P4\MasterTheme\Blocks\ENForm;

/**
 * Class MasterBlocks
 * The main class that handles Planet4 blocks.
 */
class MasterBlocks
{
    /**
     * MasterBlocks constructor.
     */
    public function __construct()
    {
        $this->hooks();
    }

    /**
     * Class hooks.
     */
    private function hooks(): void
    {
        // Register p4 block categories.
        add_filter('block_categories_all', function ($categories) {

            // Adding the "planet4-blocks" category.
            array_unshift($categories, [
                'slug' => 'planet4-blocks',
                'title' => 'Planet 4 Blocks',
            ]);

            // Adding the "planet4-block-templates" category.
            array_unshift($categories, [
                'slug' => 'planet4-block-templates',
                'title' => 'Planet 4 Block Templates',
            ]);

            return $categories;
        });

        // Adds functionality to Twig.
        add_filter('timber/twig', function ($twig) {
            // Adding functions as filters.
            $twig->addFilter(
                new \Twig\TwigFilter(
                    'object_to_array_theme',
                    function ($std_class_object) {
                        $response = [];
                        foreach ($std_class_object as $key => $value) {
                            $response[$key] = $value;
                        }
                        return $response;
                    }
                )
            );

            return $twig;
        });

        // Fix Columns block attributes.
        add_filter('render_block_data', [$this, 'empty_string_to_false_in_link_new_tab_in_columns_blocks']);

        // Admin scripts.
        add_action('enqueue_block_editor_assets', [ $this, 'enqueue_block_editor_script' ]);

        // Frontend scripts.
        add_action('wp_enqueue_scripts', [$this, 'enqueue_block_public_assets']);
    }

    /**
     * @param array $block the block being rendered.
     * For the "link_new_tab" field the type was initially incorrectly set to
     * string instead of boolean. As a result we need to catch all empty strings here and
     * turn them into false.
     *
     * Note that this DOES NOT get called when a block is rendered using the REST API column rendered *sigh*.
     * Even better, there is no hook that allows to modify the request before the parameters are validated.
     * As this is only for the block renderer of columns, a good option would be to replace the invalid argument
     * on the frontend before the API is called.
     * This should be sufficient as we can fix the data after this fix hsa been applied,
     * after which we can remove the workaround.
     *
     * @return array the block with correct attributes.
     */
    public function empty_string_to_false_in_link_new_tab_in_columns_blocks(array $block): array
    {
        // Yes, that's right, WordPress doesn't follow its own rules here so we have a camel among snakes.
        if ('planet4-blocks/columns' === $block['blockName'] ?? null) {
            foreach ($block['attrs']['columns'] ?? [] as $key => $column) {
                if (!isset($column['link_new_tab']) || true === $column['link_new_tab']) {
                    continue;
                }
                $block['attrs']['columns'][$key]['link_new_tab'] = false;
            }
        }

        return $block;
    }

    /**
     * Enqueue block editor script.
     */
    public function enqueue_block_editor_script(): void
    {
        $theme_dir = get_template_directory_uri();

        $js_creation = filectime(get_template_directory() . '/assets/build/editorIndex.js');
        // Enqueue editor script for all Blocks in this Plugin.
        wp_enqueue_script(
            'planet4-blocks-theme-editor-script',
            $theme_dir . '/assets/build/editorIndex.js',
            [
                'wp-blocks', // Helpers for registering blocks.
                'wp-components', // Wordpress components.
                'wp-element', // WP React wrapper.
                'wp-data', // WP data helpers.
                'wp-i18n', // Exports the __() function.
                'wp-editor',
                'wp-edit-post',
            ],
            $js_creation,
            true
        );

        $reflection_vars = self::reflect_js_variables();
        wp_localize_script('planet4-blocks-theme-editor-script', 'p4_vars', $reflection_vars);
    }

    /**
     * Load block assets for the frontend.
     */
    public function enqueue_block_public_assets(): void
    {
        $theme_dir = get_template_directory_uri();

        $js_creation = filectime(get_template_directory() . '/assets/build/frontendIndex.js');
        // Include React in the Frontend.
        wp_register_script(
            'planet4-blocks-theme-script',
            $theme_dir . '/assets/build/frontendIndex.js',
            [
                // WP React wrapper.
                'wp-element',
                // Exports the __() function.
                'wp-i18n',
            ],
            $js_creation,
            true
        );
        wp_enqueue_script('planet4-blocks-theme-script');

        $reflection_vars = self::reflect_js_variables();
        wp_localize_script('planet4-blocks-theme-script', 'p4_vars', $reflection_vars);
    }

    /**
     * Get Planet 4 options
     *
     */
    private function get_p4_options(): array
    {
        $option_values = get_option('planet4_options');

        $cookies_default_copy = [
            'necessary_cookies_name' => $option_values['necessary_cookies_name'] ?? '',
            'necessary_cookies_description' => $option_values['necessary_cookies_description'] ?? '',
            'analytical_cookies_name' => $option_values['analytical_cookies_name'] ?? '',
            'analytical_cookies_description' => $option_values['analytical_cookies_description'] ?? '',
            'all_cookies_name' => $option_values['all_cookies_name'] ?? '',
            'all_cookies_description' => $option_values['all_cookies_description'] ?? '',
        ];

        return [
            'enable_analytical_cookies' => $option_values['enable_analytical_cookies'] ?? '',
            'enable_google_consent_mode' => $option_values['enable_google_consent_mode'] ?? '',
            'cookies_default_copy' => $cookies_default_copy,
            'take_action_covers_button_text' => $option_values['take_action_covers_button_text'] ?? '',
            'take_action_page' => $option_values['take_action_page'] ?? '',
            'new_ia' => $option_values['new_ia'] ?? '',
        ];
    }

    /**
     * Get Planet 4 features
     *
     */
    private function get_p4_features(): array
    {
        return get_option('planet4_features');
    }

    /**
     * Get all available EN pages.
     */
    public function get_en_pages(): array
    {
        $main_settings = get_option('p4en_main_settings');

        // Get EN pages only on admin panel.
        if (!is_admin() || !isset($main_settings['p4en_private_api'])) {
            return [];
        }

        $pages = [];
        $pages[] = $main_settings['p4en_private_api'];
        $ens_private_token = $main_settings['p4en_private_api'];
        $ens_api = new EnsapiController($ens_private_token);
        $pages = $ens_api->get_pages_by_types_status(ENForm::ENFORM_PAGE_TYPES, 'live');
        uasort(
            $pages,
            function ($a, $b) {
                return ($a['name'] ?? '') <=> ($b['name'] ?? '');
            }
        );

        return $pages;
    }

    /**
     * Get all available EN forms.
     */
    public function get_en_forms(): array
    {
        // Get EN Forms.
        $query = new \WP_Query(
            [
                'post_status' => 'publish',
                'post_type' => 'p4en_form',
                'orderby' => 'post_title',
                'order' => 'asc',
                'suppress_filters' => false,
                'posts_per_page' => -1,
            ]
        );
        return $query->posts;
    }

    /**
     * Add variables reflected from PHP to JS.
     */
    public function reflect_js_variables(): array
    {
        $news_page = (int) get_option('page_for_posts');

        return [
            'news_page_link' => $news_page ? get_permalink($news_page) : null,
            'options' => $this->get_p4_options(),
            'features' => $this->get_p4_features(),
            'pages' => $this->get_en_pages(),
            'forms' => $this->get_en_forms(),
            'themeUrl' => get_template_directory_uri(),
        ];
    }
}
