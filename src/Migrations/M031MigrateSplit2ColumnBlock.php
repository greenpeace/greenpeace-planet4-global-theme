<?php

declare(strict_types=1);

namespace P4\MasterTheme\Migrations;

use P4\MasterTheme\MigrationRecord;
use P4\MasterTheme\MigrationScript;
use P4\MasterTheme\BlockReportSearch\BlockSearch;
use WP_Block_Parser;

/**
 * Migrate split-two-columns block to columns block.
 */
class M031MigrateSplit2ColumnBlock extends MigrationScript
{
    private const BLOCK_NAME = 'planet4-blocks/split-two-columns';
    private const POST_TYPES = [ 'page', 'post', 'action', 'campaign' ];

    /**
     * Extract split-two-columns block from page/posts and transform it into columns block.
     *
     * @param MigrationRecord $record Information on the execution, can be used to add logs.
     * phpcs:disable SlevomatCodingStandard.Functions.UnusedParameter -- interface implementation
     */
    public static function execute(MigrationRecord $record): void
    {
        $parser = new WP_Block_Parser();

        // Get the list of posts using split-two-columns block.
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

            // Go through the post blocks to find the planet4-blocks/split-two-columns one.
            $blocks = $parser->parse($post->post_content);

            foreach ($blocks as &$block) {
                // Skip other blocks.
                if (! isset($block['blockName']) || $block['blockName'] !== self::BLOCK_NAME) {
                    continue;
                }

                // Gathering the data we want from this block.
                $block_attrs = self::get_split_2_columns_block_attrs($block);

                // No data, skip this block.
                if (empty($block_attrs)) {
                    continue;
                }

                $block = self::get_transform_block($block_attrs);
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
     * Get the split-two-columns block attrs.
     *
     * @param array $block - A parsed split-two-columns block.
     * @return array - A block attrs array.
     */
    private static function get_split_2_columns_block_attrs(array $block): array
    {
        $block_attrs = [];

        $block_attrs['column1']['title'] = $block['attrs']['title'] ?? '';
        $block_attrs['column1']['description'] = wp_trim_words($block['attrs']['issue_description'] ?? '', 12);

        $block_attrs['column1']['link_text'] = $block['attrs']['issue_link_text'] ?? '';
        $block_attrs['column1']['link_path'] = $block['attrs']['issue_link_path'] ?? '';
        $block_attrs['column1']['image_id'] = $block['attrs']['issue_image_id'] ?? '';
        $block_attrs['column1']['image_src'] = $block['attrs']['issue_image_src'] ?? '';

        $block_attrs['column2']['title'] = $block['attrs']['tag_name'] ?? '';
        $block_attrs['column2']['description'] = wp_trim_words($block['attrs']['tag_description'] ?? '', 12);
        $block_attrs['column2']['button_text'] = $block['attrs']['button_text'] ?? '';
        $block_attrs['column2']['button_link'] = $block['attrs']['button_link'] ?? '';
        $block_attrs['column2']['link_path'] = $block['attrs']['tag_link'] ?? '';
        $block_attrs['column2']['image_id'] = $block['attrs']['tag_image_id'] ?? '';
        $block_attrs['column2']['image_src'] = $block['attrs']['tag_image_src'] ?? '';

        return $block_attrs;
    }

    /**
     * Transform a block attrs into columns block.
     *
     * @param array $block_attrs - A block attrs array.
     * @return array - The transformed block.
     * phpcs:disable Generic.Files.LineLength.MaxExceeded
     */
    private static function get_transform_block(array $block_attrs): array
    {
        $template = '<!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column {"verticalAlignment":"center"} -->
<div class="wp-block-column is-vertically-aligned-center"><!-- wp:media-text {"mediaId":' . $block_attrs['column1']['image_id'] . ',"mediaLink":"' . get_page_link($block_attrs['column1']['image_id']) . '","mediaType":"image"} -->
<div class="wp-block-media-text is-stacked-on-mobile"><figure class="wp-block-media-text__media"><img src="' . $block_attrs['column1']['image_src'] . '" alt="" class="wp-image-' . $block_attrs['column1']['image_id'] . ' size-full"/></figure><div class="wp-block-media-text__content"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">' . $block_attrs['column1']['title'] . '</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>' . $block_attrs['column1']['description'] . '</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><a href="' . $block_attrs['column1']['link_path'] . '">' . $block_attrs['column1']['link_text'] . '</a></p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:media-text --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:media-text {"mediaId":' . $block_attrs['column2']['image_id'] . ',"mediaLink":"' . get_page_link($block_attrs['column2']['image_id']) . '","mediaType":"image"} -->
<div class="wp-block-media-text is-stacked-on-mobile"><figure class="wp-block-media-text__media"><img src="' . $block_attrs['column2']['image_src'] . '" alt="" class="wp-image-' . $block_attrs['column2']['image_id'] . ' size-full"/></figure><div class="wp-block-media-text__content"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">#' . $block_attrs['column2']['title'] . '</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>' . $block_attrs['column2']['description'] . '</p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-cta"} -->
<div class="wp-block-button is-style-cta"><a class="wp-block-button__link wp-element-button" href="' . $block_attrs['column2']['button_link'] . '">' . $block_attrs['column2']['button_text'] . '</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div></div>
<!-- /wp:media-text --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->';

        $parser_new = new WP_Block_Parser();
        $blocks = $parser_new->parse($template);

        return $blocks[0];
    }
    // phpcs:enable Generic.Files.LineLength.MaxExceeded
}
