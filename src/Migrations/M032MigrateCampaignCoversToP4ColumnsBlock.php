<?php

declare(strict_types=1);

namespace P4\MasterTheme\Migrations;

use P4\MasterTheme\MigrationRecord;
use P4\MasterTheme\MigrationScript;
use P4\MasterTheme\BlockReportSearch\BlockSearch;
use WP_Block_Parser;

/**
 * Migrate Campaign covers block to Planet4 columns block.
 */
class M032MigrateCampaignCoversToP4ColumnsBlock extends MigrationScript
{
    private const BLOCK_NAME = 'planet4-blocks/covers';
    private const POST_TYPES = [ 'page', 'post', 'action', 'campaign' ];
    private const COVER_TYPE = 'campaign';
    private const OLD_COVER_TYPES = [
        '1' => 'take-action',
        '2' => 'campaign',
        '3' => 'content',
    ];

    /**
     * Extract campaign covers block from page/posts and transform it into Planet4 columns block.
     *
     * @param MigrationRecord $record Information on the execution, can be used to add logs.
     * phpcs:disable SlevomatCodingStandard.Functions.UnusedParameter -- interface implementation
     */
    public static function execute(MigrationRecord $record): void
    {
        $parser = new WP_Block_Parser();

        // Get the list of posts using Covers block.
        $posts = self::get_posts_using_block(self::BLOCK_NAME);

        // If there are no posts, abort.
        if (!$posts) {
            return;
        }

        foreach ($posts as $post) {
            if (empty($post->post_content)) {
                continue;
            }

            echo 'Parsing post ', $post->ID, "\n"; // phpcs:ignore
            $result = false;

            // Go through the post blocks to find the planet4-blocks/covers one.
            $blocks = $parser->parse($post->post_content);

            foreach ($blocks as &$block) {
                // For older cover blocks, the cover type is empty, so use the default content cover type,hence skip it.
                if (!isset($block['attrs']['cover_type'])) {
                    continue;
                }

                // For old cover type(earlier it was numeric).
                if (is_numeric($block['attrs']['cover_type'])) {
                    $block['attrs']['cover_type'] = self::OLD_COVER_TYPES[ $block['attrs']['cover_type'] ];
                }

                // Skip other blocks & non campaign cover blocks.
                if (
                    ! isset($block['blockName']) || $block['blockName'] !== self::BLOCK_NAME ||
                    $block['attrs']['cover_type'] !== self::COVER_TYPE
                ) {
                    continue;
                }

                // Gathering the data we want from this block.
                $block_attrs = self::get_columns_block_attrs($block);

                // No data, skip this block.
                if (empty($block_attrs)) {
                    continue;
                }

                $block_attrs = array_merge(['columns_block_style' => 'image'], $block_attrs);
                $block_attrs['className'] = 'is-style-image';
                $block = self::transform_block($block_attrs);
            }

            // Unset the reference to prevent potential issues.
            unset($block);

            // Serialize the blocks content.
            $new_content = serialize_blocks($blocks);

            if ($post->post_content !== $new_content) {
                $post_update = array(
                    'ID' => $post->ID,
                    'post_content' => $new_content,
                );

                try {
                    // Update the post with the replaced blocks.
                    wp_update_post($post_update);
                    $result = true;
                } catch (\Throwable $e) {
                    echo 'Error on post ', $post->ID, "\n";
                    echo $e->getMessage(), "\n";
                }
            }

            echo $result
                ? "Migration successful\n"
                : "Migration wasn't executed\n"; // phpcs:ignore
        }
    }
    // phpcs:enable SlevomatCodingStandard.Functions.UnusedParameter

    /**
     * Get all the posts using block name.
     *
     * @param string $block_name - A block name.
     * @return mixed - The posts using block or null if no posts are found.
     */
    private static function get_posts_using_block(string $block_name): mixed
    {
        $search = new BlockSearch();

        $post_ids = $search->get_posts_with_block($block_name);

        if (empty($post_ids)) {
            return null;
        }

        $args = [
            'include' => $post_ids,
            'post_type' => self::POST_TYPES,
        ];

        $posts = get_posts($args) ?? [];

        if (empty($posts)) {
            return null;
        }

        return $posts;
    }

    /**
     * Transform a block attrs into columns block.
     *
     * @param array $block_attrs - A block attrs array.
     * @return array - The transformed block.
     */
    private static function transform_block(array $block_attrs): array
    {
        $p4_columns_block['blockName'] = 'planet4-blocks/columns';
        $p4_columns_block['attrs'] = $block_attrs;
        $p4_columns_block['innerBlocks'] = [];
        $p4_columns_block['innerHTML'] = '';
        $p4_columns_block['innerContent'] = [];

        return $p4_columns_block;
    }

    /**
     * Get the p4 columns block attrs.
     *
     * @param array $block - A parsed cover block.
     * @return array - A P4 columns block attrs.
     */
    private static function get_columns_block_attrs(array $block): array
    {
        $block_attrs = [];
        foreach ($block['attrs'] as $key => $value) {
            if (empty($value)) {
                continue;
            }

            if ('title' === $key) {
                $block_attrs['columns_title'] = $value;
            } elseif ('description' === $key) {
                // Filter & replace \n with \u003cbr\u003e .
                $block_attrs['columns_description'] = str_replace("\n", "\u003cbr\u003e", $value);
            } elseif ('tags' === $key) {
                // To keep the same order of columns, reverse the array.
                $value = array_reverse($value);
                foreach ($value as $tag_id) {
                    $tag = get_tag($tag_id);
                    if (!$tag) {
                        continue;
                    }

                    // Prepare tags(columns) array data.
                    $block_attrs['columns'][] = [
                        'attachment' => get_term_meta($tag_id, 'tag_attachment_id', true),
                        'title' => '#' . html_entity_decode($tag->name),
                        'cta_link' => get_tag_link($tag),
                    ];
                }
            }
        }

        return $block_attrs;
    }
}
