<?php

/**
 * Covers block class
 *
 * @package P4\MasterTheme
 * @since 0.1
 */

namespace P4\MasterTheme\Blocks;

use WP_REST_Server;
use P4\MasterTheme\ActionPage;

/**
 * Class Covers
 *
 * @package P4\MasterTheme\Blocks
 */
class Covers extends BaseBlock
{
    /**
     * Block name.
     */
    public const BLOCK_NAME = 'covers';

    /**
     * Block version, update when changing attributes
     */
    private const VERSION = 2;

    /**
     * Old cover types, needed to convert existing blocks to version 2.
     */
    private const OLD_COVER_TYPES = [
        '1' => 'take-action',
    ];

    /**
     * New cover types, used for version 2.
     */
    private const TAKE_ACTION_COVER_TYPE = 'take-action';

    /**
     * Layout options.
     */
    private const CAROUSEL_LAYOUT = 'carousel';
    private const GRID_LAYOUT = 'grid';

    /**
     * Limit amounts of covers per layout.
     */
    private const POSTS_LIMIT = 50;
    private const POSTS_LIMIT_CAROUSEL_LAYOUT = 12; // When Carousel layout is selected we want no more than 12 covers.

    /**
     * Covers constructor.
     */
    public function __construct()
    {
        register_block_type(
            self::get_full_block_name(),
            [
                'editor_script' => 'planet4-blocks',
                'render_callback' => static function ($attributes, $content) {
                    if (isset($attributes['covers_view'])) {
                        $attributes['initialRowsLimit'] = '3' === $attributes['covers_view'] ? 0 :
                            intval($attributes['covers_view']);
                        unset($attributes['covers_view']);
                    }

                    if (!isset($attributes['version'])) {
                        $attributes['version'] = self::VERSION;
                    }

                    if (is_numeric($attributes['cover_type'])) {
                        $old_cover_type = $attributes['cover_type'];
                        $attributes['cover_type'] = self::OLD_COVER_TYPES[ $old_cover_type ];
                    }

                    if (empty($attributes['readMoreText'])) {
                        $attributes['readMoreText'] = __('Load more', 'planet4-blocks');
                    }

                    $attributes['covers'] = self::get_covers($attributes);

                    return self::hydrate_frontend($attributes, $content);
                },
                'attributes' => [
                    'cover_type' => [
                        'type' => 'string',
                        'default' => self::TAKE_ACTION_COVER_TYPE,
                    ],
                    'initialRowsLimit' => [
                        'type' => 'integer',
                        'default' => 1,
                    ],
                    'title' => [
                        'type' => 'string',
                        'default' => '',
                    ],
                    'description' => [
                        'type' => 'string',
                        'default' => '',
                    ],
                    'tags' => [
                        'type' => 'array',
                        'default' => [],
                        'items' => [
                            'type' => 'integer',
                        ],
                    ],
                    'post_types' => [
                        'type' => 'array',
                        'default' => [],
                        'items' => [
                            'type' => 'integer',
                        ],
                    ],
                    'posts' => [
                        'type' => 'array',
                        'default' => [],
                        'items' => [
                            'type' => 'integer',
                        ],
                    ],
                    'version' => [
                        'type' => 'integer',
                        'default' => self::VERSION,
                    ],
                    'layout' => [
                        'type' => 'string',
                        'default' => self::GRID_LAYOUT,
                    ],
                    'isExample' => [
                        'type' => 'boolean',
                        'default' => false,
                    ],
                    'exampleCovers' => [
                        'type' => 'object',
                    ],
                    'readMoreText' => [
                        'type' => 'string',
                        'default' => __('Load more', 'planet4-blocks'),
                    ],
                    'covers' => [
                        'type' => 'array',
                        'default' => [],
                        'items' => [
                            'type' => 'object',
                        ],
                    ],
                ],
            ]
        );

        add_action('enqueue_block_editor_assets', [ self::class, 'enqueue_editor_assets' ]);
        add_action('wp_enqueue_scripts', [ self::class, 'enqueue_frontend_assets' ]);
        add_action('rest_api_init', [ self::class, 'register_endpoint' ]);
    }

    /**
     * Required by the `BaseBlock` class.
     *
     * @param array $fields Unused, required by the abstract function.
     * @phpcs:disable SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     */
    public function prepare_data(array $fields): array
    {
        return [];
    }
    // @phpcs:enable SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter

    /**
     * Get all the data that will be needed to render the block correctly.
     *
     * @param array $fields This is the array of fields of this block.
     *
     * @return array The data to be passed in the View.
     */
    public static function get_covers(array $fields): array
    {
        $cover_type = $fields['cover_type'];
        $covers = [];

        if (self::TAKE_ACTION_COVER_TYPE === $cover_type) {
            $covers = self::populate_posts_for_act_pages($fields);
        }

        return $covers;
    }

    /**
     * Get posts that are act page children.
     *
     * @param array $fields This is the array of fields of this block.
     */
    private static function filter_posts_for_act_pages(array $fields): array
    {
        $tag_ids = $fields['tags'] ?? [];
        $parent_act_id = (int) planet4_get_option('act_page', -1);
        $layout = $fields['layout'] ?? self::GRID_LAYOUT;

        if (-1 !== $parent_act_id) {
            $args = [
                'post_type' => 'page',
                'post_status' => 'publish',
                'post_parent' => $parent_act_id,
                'orderby' => [
                    'menu_order' => 'ASC',
                    'date' => 'DESC',
                    'title' => 'ASC',
                ],
                'suppress_filters' => false,
                'numberposts' => self::numberposts($layout),
            ];
            // If user selected a tag to associate with the Take Action page covers.
            if (! empty($tag_ids)) {
                $args['tag__in'] = $tag_ids;
            }

            // Ignore sniffer rule, arguments contain suppress_filters.
            // phpcs:ignore
            return get_posts($args);
        }

        return [];
    }

    /**
     * Get posts that are Action pages (p4_action).
     *
     * @param array $fields This is the array of fields of this block.
     */
    private static function filter_posts_for_action_pages(array $fields): array
    {
        $tag_ids = $fields['tags'] ?? [];
        $layout = $fields['layout'] ?? self::GRID_LAYOUT;

        $args = [
            'post_type' => ActionPage::POST_TYPE,
            'post_status' => 'publish',
            'orderby' => [
                'menu_order' => 'ASC',
                'date' => 'DESC',
                'title' => 'ASC',
            ],
            'suppress_filters' => false,
            'numberposts' => self::numberposts($layout),
        ];
        // If user selected a tag to associate with the Take Action page covers.
        if (!empty($tag_ids)) {
            $args['tag__in'] = $tag_ids;
        }

        // Ignore sniffer rule, arguments contain suppress_filters.
        // phpcs:ignore
        return get_posts($args) ?? [];
    }

    /**
     * Get specific posts.
     *
     * @param array $fields This is the array of fields of this block.
     */
    private static function filter_posts_by_ids(array $fields): array
    {
        $post_ids = $fields['posts'] ?? [];
        $layout = $fields['layout'] ?? self::GRID_LAYOUT;

        if (empty($post_ids)) {
            return [];
        }

        // Get all posts with arguments.
        $args = [
            'orderby' => 'post__in',
            'post_status' => 'publish',
            'post__in' => $post_ids,
            'suppress_filters' => false,
            'numberposts' => self::numberposts($layout),
        ];

        // If cover type is take action pages set post_type to page.
        if (isset($fields['cover_type']) && self::TAKE_ACTION_COVER_TYPE === $fields['cover_type']) {
            $args['post_type'] = ['page', ActionPage::POST_TYPE];
        } else {
            $args['post_type'] = ['post', 'page'];
        }

        // Ignore sniffer rule, arguments contain suppress_filters.
        // phpcs:ignore
        return get_posts($args);
    }

    /**
     * Get posts for content four column.
     *
     * @param array $fields This is the array of fields of this block.
     */
    private static function filter_posts_for_cfc(array $fields): array
    {
        $tag_ids = $fields['tags'] ?? [];
        $post_types = $fields['post_types'] ?? [];
        $layout = $fields['layout'] ?? self::GRID_LAYOUT;

        $query_args = [
            'post_type' => 'post',
            'orderby' => [
                'date' => 'DESC',
                'title' => 'ASC',
            ],
            'no_found_rows' => true,
            'posts_per_page' => self::numberposts($layout),
        ];

        // Get all posts with the specific tags.
        // Construct the arguments array for the query.
        if (!empty($tag_ids) && !empty($post_types)) {
            $query_args['tax_query'] = [
                'relation' => 'AND',
                [
                    'taxonomy' => 'post_tag',
                    'field' => 'term_id',
                    'terms' => $tag_ids,
                ],
                [
                    'taxonomy' => 'p4-page-type',
                    'field' => 'term_id',
                    'terms' => $post_types,
                ],
            ];
        } elseif (!empty($tag_ids) && empty($post_types)) {
            $query_args['tax_query'] = [
                [
                    'taxonomy' => 'post_tag',
                    'field' => 'term_id',
                    'terms' => $tag_ids,
                ],
            ];
        } elseif (empty($tag_ids) && !empty($post_types)) {
            $query_args['tax_query'] = [
                [
                    'taxonomy' => 'p4-page-type',
                    'field' => 'term_id',
                    'terms' => $post_types,
                ],
            ];
        }

        // If tax_query has been defined in the arguments array, then make a query based on these arguments.
        if (array_key_exists('tax_query', $query_args)) {
            // Construct a WP_Query object and make a query based on the arguments array.
            $query = new \WP_Query();
            $posts = $query->query($query_args);

            return $posts;
        }

        return [];
    }

    /**
     * Populate posts for take action covers template.
     *
     * @param array $fields This is the array of fields of this block.
     */
    private static function populate_posts_for_act_pages(array &$fields): array
    {
        $post_ids = $fields['posts'] ?? [];
        $options = get_option('planet4_options');

        if (!empty($post_ids)) {
            $actions = self::filter_posts_by_ids($fields);
        } else {
            $actions = self::filter_posts_for_act_pages($fields);
            if (!empty($options['new_ia'])) {
                $actions = array_merge(
                    self::filter_posts_for_action_pages($fields),
                    $actions
                );
                // Sort by published date, recent first.
                usort(
                    $actions,
                    function ($a, $b) {
                        return $a->menu_order <=> $b->menu_order;
                    }
                );
            }
        }

        $covers = [];

        if ($actions) {
            foreach ($actions as $action) {
                $tags = [];
                $wp_tags = wp_get_post_tags($action->ID);

                if (is_array($wp_tags) && $wp_tags) {
                    foreach ($wp_tags as $wp_tag) {
                        $tags[] = [
                            'name' => html_entity_decode($wp_tag->name),
                            'href' => get_tag_link($wp_tag),
                        ];
                    }
                }

                $img_id = get_post_thumbnail_id($action);

                // Get the button text from the meta data (for Actions), the P4 settings, or use the default value.
                $meta = get_post_meta($action->ID);
                if (isset($meta['action_button_text']) && $meta['action_button_text'][0]) {
                    $cover_button_text = $meta['action_button_text'][0];
                } else {
                    $cover_button_text = $options['take_action_covers_button_text'] ??
                        __('Take action', 'planet4-blocks');
                }

                $covers[] = [
                    'tags' => $tags ?? [],
                    'title' => html_entity_decode(get_the_title($action)),
                    'excerpt' => $action->post_excerpt,
                    'image' => get_the_post_thumbnail_url($action, 'large'),
                    'srcset' => wp_get_attachment_image_srcset($img_id, 'articles-medium-large') ?? 'false',
                    'alt_text' => get_the_post_thumbnail_url($action, 'large'),
                    'button_text' => $cover_button_text,
                    'link' => get_permalink($action->ID),
                ];
            }
        }

        return $covers;
    }

    /**
     * @param string $layout Covers block layout.
     *
     * @return int Number of posts to fetch.
     */
    private static function numberposts(string $layout): int
    {
        if (self::CAROUSEL_LAYOUT === $layout) {
            return self::POSTS_LIMIT_CAROUSEL_LAYOUT;
        }

        return self::POSTS_LIMIT;
    }

    /**
     * Register endpoint to retrieve the covers for the Covers block.
     *
     * @example GET /wp-json/planet4/v1/get-covers/
     */
    public static function register_endpoint(): void
    {
        register_rest_route(
            self::REST_NAMESPACE,
            'get-covers',
            [
                [
                    'permission_callback' => static function () {
                        return true;
                    },
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => static function ($request) {
                        $covers = self::get_covers($request->get_params());
                        return rest_ensure_response($covers);
                    },
                ],
            ]
        );
    }
}
