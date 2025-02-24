<?php

// phpcs:disable Generic.Files.LineLength.MaxExceeded

namespace P4\MasterTheme\Migrations;

use P4\MasterTheme\MigrationRecord;
use P4\MasterTheme\MigrationScript;

/**
 * Migrate Articles block to Posts List blocks.
 */
class M042MigrateArticlesBlockToPostsListBlock extends MigrationScript
{
    /**
     * Perform the actual migration.
     *
     * @param MigrationRecord $record Information on the execution, can be used to add logs.
     * phpcs:disable SlevomatCodingStandard.Functions.UnusedParameter -- interface implementation
     */
    public static function execute(MigrationRecord $record): void
    {
        $check_is_valid_block = function ($block) {
            return self::check_is_valid_block($block);
        };

        $transform_block = function ($block) {
            return self::transform_block($block);
        };

        Utils\Functions::execute_block_migration(
            Utils\Constants::BLOCK_ARTICLES,
            $check_is_valid_block,
            $transform_block,
        );
    }
    // phpcs:enable SlevomatCodingStandard.Functions.UnusedParameter

    /**
     * Check whether a block is a Articles block.
     *
     * @param array $block - A block data array.
     */
    private static function check_is_valid_block(array $block): bool
    {
        // Check if the block is valid.
        if (!is_array($block)) {
            return false;
        }

        // Check if the block has a 'blockName' key.
        if (!isset($block['blockName'])) {
            return false;
        }

        // Check if the block is a Articles block.
        return $block['blockName'] === Utils\Constants::BLOCK_ARTICLES;
    }

    /**
     * Create a new Query block based on attributes of a Articles block.
     *
     * @param array $block - The current articles block.
     * @return array - The new block.
     */
    private static function transform_block(array $block): array
    {
        $existing_block_attrs = self::get_posts_list_block_attrs($block);

        $tags = $existing_block_attrs['tags'];
        $posts_override = $existing_block_attrs['posts'];
        $post_types = $existing_block_attrs['post_types'];
        $layout_type = 'default';
        $classname = 'list';
        $rows = $existing_block_attrs['rows'];
        $current_post_id = $existing_block_attrs['current_post_id'];

        $attrs = self::set_query_block_attrs($tags, $posts_override, $post_types, $layout_type, $rows, $current_post_id);

        $inner_blocks = [
            self::get_head_group_block($existing_block_attrs['title']),
            self::get_paragraph_block($existing_block_attrs['description']),
            self::get_query_no_results_block(),
            self::get_post_template(),
            self::get_buttons_block(),
            self::get_nav_links_block(),
        ];

        return Utils\Functions::create_block_query(
            $inner_blocks,
            $attrs,
            $classname
        );
    }

    /**
     * Get the attributes of a Post lists block.
     *
     * @param array $existing_block - The current Articles block.
     * @return array - The attributes.
     */
    private static function get_posts_list_block_attrs(array $existing_block): array
    {
        $attrs = $existing_block['attrs'];

        return [
            'title' => isset($attrs['article_heading']) && !empty($attrs['article_heading']) ?
                $attrs['article_heading'] : __('Related Articles', 'planet4-blocks'),
            'description' => isset($attrs['articles_description']) ? $attrs['articles_description'] : '',
            'tags' => isset($attrs['tags']) ? $attrs['tags'] : [],
            'posts' => isset($attrs['posts']) ? $attrs['posts'] : [],
            'post_types' => isset($attrs['post_types']) ? $attrs['post_types'] : [],
            'rows' => isset($attrs['article_count']) ? $attrs['article_count'] : 3,
            'current_post_id' => isset($attrs['current_post_id']) ? $attrs['current_post_id'] : 0,
        ];
    }

    /**
     * Set all the attributes to create a new Query block.
     *
     * @param array $tags - The list of post tags.
     * @param array $posts_override - The list of posts to include in the query.
     * @param array $post_types - The list of terms of the "p4-page-type" taxonomy.
     * @param string $layout_type - The layout type (grid or flex).
     * @param int $per_page - The number of elements per page.
     * @param int $current_post_id - The current post ID.
     * @return array - The attributes.
     */
    private static function set_query_block_attrs(
        array $tags,
        array $posts_override,
        array $post_types,
        string $layout_type,
        int $per_page,
        int $current_post_id
    ): array {
        $query = [];
        $query['pages'] = 0;
        $query['offset'] = 0;
        $query['postType'] = Utils\Constants::POST_TYPES_POST;
        $query['order'] = 'desc';
        $query['orderBy'] = 'date';
        $query['author'] = '';
        $query['search'] = '';
        $query['exclude'] = [];
        $query['sticky'] = '';
        $query['inherit'] = false;
        $query['hasPassword'] = false;
        $query['postIn'] = $posts_override;

        // Set the number of posts per page.
        $query['perPage'] = $per_page;

        if (!empty($tags)) {
            $query['taxQuery']['post_tag'] = $tags;
        }
        if (!empty($post_types)) {
            $query['taxQuery']['p4-page-type'] = $post_types;
        }

        if ($current_post_id) {
            $query['exclude'] = [$current_post_id];
        }

        $layout = [];
        $layout['type'] = $layout_type;
        $layout['columnCount'] = 3;

        $attrs = [];
        $attrs['queryId'] = 0;
        $attrs['query'] = $query;
        $attrs['namespace'] = Utils\Constants::BLOCK_POSTS_LIST;
        $attrs['layout'] = $layout;
        return $attrs;
    }

    /**
     * Create and get a new buttons container block.
     *
     * @return array - The new block.
     */
    private static function get_buttons_block(): array
    {
        return Utils\Functions::create_block_buttons(
            [
                'lock' => [
                    'move' => true,
                ],
                'layout' => [
                    'type' => 'flex',
                    'justifyContent' => 'space-between',
                    'orientation' => 'horizontal',
                    'flexWrap' => 'nowrap',
                ],
                'className' => 'carousel-controls',
            ],
            [
                Utils\Functions::create_block_single_button(
                    ['className' => 'carousel-control-prev'],
                    __('Prev', 'planet4-blocks'),
                ),
                Utils\Functions::create_block_single_button(
                    ['className' => 'carousel-control-next'],
                    __('Next', 'planet4-blocks'),
                ),
            ]
        );
    }

    /**
     * Create and get a new post template.
     *
     * @return array - The new template.
     */
    private static function get_post_template(): array
    {
        return Utils\Functions::create_post_template(
            [
                Utils\Functions::create_block_columns(
                    [],
                    [
                        Utils\Functions::create_new_block(
                            Utils\Constants::BLOCK_FEAT_IMAGE,
                            ['isLink' => true]
                        ),
                        Utils\Functions::create_group_block(
                            [
                                Utils\Functions::create_group_block(
                                    [
                                        Utils\Functions::create_new_block(
                                            Utils\Constants::BLOCK_TERMS,
                                            ['term' => 'category', 'separator' => ' | ']
                                        ),
                                        Utils\Functions::create_new_block(
                                            Utils\Constants::BLOCK_TERMS,
                                            ['term' => 'post_tag', 'separator' => ' ']
                                        ),
                                    ],
                                    [
                                        'layout' => [
                                            'type' => 'flex',
                                        ]
                                    ]
                                ),
                                Utils\Functions::create_new_block(
                                    Utils\Constants::BLOCK_TITLE,
                                    ['isLink' => true]
                                ),
                                Utils\Functions::create_new_block(
                                    Utils\Constants::BLOCK_EXCERPT
                                ),
                                Utils\Functions::create_group_block(
                                    [
                                        Utils\Functions::create_new_block(
                                            Utils\Constants::BLOCK_AUTHOR,
                                            ['isLink' => true]
                                        ),
                                        Utils\Functions::create_new_block(
                                            Utils\Constants::BLOCK_DATE
                                        ),
                                    ],
                                    ['className' => 'posts-list-meta',]
                                ),
                            ],
                            []
                        ),
                    ]
                ),
            ],
            [
                'lock' => [
                    'move' => true,
                    'remove' => true,
                ],
            ]
        );
    }

    /**
     * Create and get a new navigation link block.
     *
     * @return mixed - The navigation link block if the All Stories page is set.
     */
    private static function get_nav_links_block(): array
    {
        if (!get_option('page_for_posts')) {
            return [];
        }

        return Utils\Functions::create_new_block(
            Utils\Constants::BLOCK_NAV_LINK,
            [
                'label' => __('See all stories', 'planet4-blocks'),
                'url' => get_permalink(get_option('page_for_posts')),
                'className' => 'see-all-link',
            ]
        );
    }

    /**
     * Create and get a new query-no-results block.
     *
     * @return array - The new block.
     */
    private static function get_query_no_results_block(): array
    {
        return Utils\Functions::create_block_query_no_results(
            [
                Utils\Functions::create_block_paragraph(
                    ['placeholder' => __('No posts found. (This default text can be edited)', 'planet4-blocks')],
                    __('No posts found.', 'planet4-blocks'),
                ),
            ],
            [],
        );
    }

    /**
     * Create and get a new paragraph block.
     *
     * @param string $description - The text for the paragraph.
     * @return array - The new block.
     */
    private static function get_paragraph_block(string $description): array
    {
        return Utils\Functions::create_block_paragraph(
            [
                'lock' => [
                    'move' => true,
                ],
                'placeholder' => __('Enter description', 'planet4-blocks'),
                'style' => [
                    'spacing' => [
                        'margin' => [
                            'top' => '24px',
                            'bottom' => '36px',
                        ],
                    ],
                ],
            ],
            $description
        );
    }

    /**
     * Create and get a new group block for the Covers head.
     *
     * @param string $title - The block title.
     * @return array - The new block.
     */
    private static function get_head_group_block(string $title): array
    {
        return Utils\Functions::create_group_block(
            [
                Utils\Functions::create_block_heading(
                    ['lock' => ['move' => true]],
                    $title
                ),
                self::get_nav_links_block(),
            ],
            [
                'layout' => [
                    'type' => 'flex',
                    'justifyContent' => 'space-between',
                ],
            ]
        );
    }
}
