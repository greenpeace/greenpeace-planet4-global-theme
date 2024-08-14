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
    /**
     * Extract campaign covers block from page/posts and transform it into Planet4 columns block.
     *
     * @param MigrationRecord $record Information on the execution, can be used to add logs.
     * phpcs:disable SlevomatCodingStandard.Functions.UnusedParameter -- interface implementation
     */
    public static function execute(MigrationRecord $record): void
    {
        $search = new BlockSearch();
        $parser = new WP_Block_Parser();
        $block_name = 'planet4-blocks/covers';
        $cover_type = 'campaign';
        $old_cover_types = [
            '1' => 'take-action',
            '2' => 'campaign',
            '3' => 'content',
        ];

        $post_ids = $search->get_posts_with_block($block_name);
        if (empty($post_ids)) {
            return;
        }

        $args = [
            'include' => $post_ids,
            'post_type' => [ 'page', 'post', 'action', 'campaign' ],
        ];
        $posts = get_posts($args) ?? [];

        if (empty($posts)) {
            return;
        }

        foreach ($posts as $post) {
            if (empty($post->post_content)) {
                continue;
            }

            // Go through the post blocks to find the planet4-blocks/covers one.
            $blocks = $parser->parse($post->post_content);

            foreach ($blocks as $block) {
                // For older cover blocks, the cover type is empty, so use the default content cover type.
                if (!isset($block['attrs']['cover_type'])) {
                    continue;
                }

                // For old cover type(earlier it was numeric).
                if (is_numeric($block['attrs']['cover_type'])) {
                    $block['attrs']['cover_type'] = $old_cover_types[ $block['attrs']['cover_type'] ];
                }

                // Skip other blocks & non campaign cover blocks.
                if (
                    ! isset($block['blockName']) || $block['blockName'] !== $block_name ||
                    $block['attrs']['cover_type'] !== $cover_type
                ) {
                    continue;
                }

                echo 'Parsing post ', $post->ID, "\n"; // phpcs:ignore

                // Gathering the data we want from this block.
                $block_settings = [];
                foreach ($block['attrs'] as $key => $value) {
                    if (empty($value)) {
                        continue;
                    }

                    if ('title' === $key) {
                        $block_settings['columns_title'] = $value;
                    } elseif ('description' === $key) {
                        // Filter & replace \n with \u003cbr\u003e .
                        $block_settings['columns_description'] = str_replace("\n", "\u003cbr\u003e", $value);
                    } elseif ('tags' === $key) {
                        // To keep the same order of columns, reverse the array.
                        $value = array_reverse($value);
                        foreach ($value as $tag_id) {
                            $tag = get_tag($tag_id);
                            if (!$tag) {
                                continue;
                            }

                            // Prepare tags(columns) array data.
                            $block_settings['columns'][] = [
                                'attachment' => get_term_meta($tag_id, 'tag_attachment_id', true),
                                'title' => '#' . html_entity_decode($tag->name),
                                'cta_link' => get_tag_link($tag),
                            ];
                        }
                    }
                }

                // No data, skip this update.
                if (empty($block_settings)) {
                    continue;
                }

                $result = true;
                $columns_title = $block_settings['columns_title'] ?? '';
                $columns_description = $block_settings['columns_description'] ?? '';
                // phpcs:disable Generic.Files.LineLength.MaxExceeded
                $p4_columns_block = '<!-- wp:planet4-blocks/columns {"columns_block_style":"image","columns_title":"' .
                    $columns_title . '","columns_description":"' .
                    $columns_description . '","columns":' . json_encode($block_settings['columns']) .
                    ',"className":"is-style-image"} /-->';

                // Search for covers block regex pattern.
                $pattern = '/<!-- wp\:planet4-blocks\/covers ' .
                    '{"cover_type":"campaign"[a-zA-Z0-9"":,]*"title":"' . $columns_title .
                    '",[a-zA-Z0-9\{\"\}\_\-\s\:\/\,\[\]\<\>\=\&\;\#\%\$\@\!\(\) \\\n]*' .
                    '!-- \/wp\:planet4\-blocks\/covers \-\-\>/';
                $match_found = preg_match($pattern, $post->post_content);

                if (!$match_found) {
                    // For old campaign covers, having numeric cover_type.
                    $pattern = '/<!-- wp\:planet4-blocks\/covers {"title":"' . $columns_title .
                        '",[a-zA-Z0-9\{\"\}\_\-\s\:\/\,\[\]\<\>\=\&\;\#\%\$\@\!\(\) \\\n]*\"cover_type\"\:\"2\"\} \/\-\-\>/';

                    $match_found = preg_match($pattern, $post->post_content);

                    if (!$match_found) {
                        // For old campaign covers empty column titles.
                        $pattern = '/<!-- wp\:planet4-blocks\/covers [a-zA-Z0-9\{\"\}\_\-\s\:\/\,\[\]\<\>\=\&\;\#\%\$\@\!\(\) \\\n]*\"cover_type\"\:\"2\"\} \/\-\-\>/';
                        $match_found = preg_match($pattern, $post->post_content);
                    }
                    // phpcs:enable Generic.Files.LineLength.MaxExceeded
                }

                if ($match_found) {
                    $updated_post_content = preg_replace($pattern, $p4_columns_block, $post->post_content, 1);

                    $post_args = array(
                        'post_content' => wp_slash($updated_post_content),
                        'ID' => $post->ID,
                    );

                    try {
                        wp_update_post($post_args);
                    } catch (\Throwable $e) {
                        echo 'Error on post ', $post->ID, "\n";
                        echo $e->getMessage(), "\n";
                        $result = false;
                    }
                }


                echo $result
                    ? "Migration successful\n"
                    : "Migration wasn't executed\n"; // phpcs:ignore
            }
        }
    }
    // phpcs:enable SlevomatCodingStandard.Functions.UnusedParameter
}
