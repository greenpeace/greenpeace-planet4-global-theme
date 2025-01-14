<?php

declare(strict_types=1);

namespace P4\MasterTheme;

use WP_Block;

/**
 * Update image sizes for Query Loop and Media & Text blocks,
 * based on certain breakpoints.
 */
class BreakpointsImageSizes
{
    public static function hooks(): void
    {
        add_filter(
            'render_block',
            function ($block_content, $block, WP_Block $instance) {
                $is_query_block = 'core/query' === $block['blockName'];
                $is_media_text_block = 'core/media-text' === $block['blockName'];

                if (!$is_media_text_block && !$is_query_block) {
                    return $block_content;
                }

                $breakpoints = [
                    ['screen' => 1600, 'width' => '1320px'],
                    ['screen' => 1200, 'width' => '1140px'],
                    ['screen' => 992, 'width' => '960px'],
                    ['screen' => 768, 'width' => '720px'],
                    ['screen' => 601, 'width' => '540px'],
                    ['screen' => 577, 'width' => '540px'],
                ];

                if ($is_query_block) {
                    $column_count = $instance->attributes['displayLayout']['columns'] ?? null;
                    if (!$column_count || 1 === $column_count) {
                        return $block_content;
                    }

                    $sizes = array_map(function ($breakpoint) use ($column_count) {
                        $screen = $breakpoint['screen'];
                        $container = $breakpoint['width'];
                        $prev_col = $column_count - 1;

                        return "(min-width:{$screen}px) calc({$container}/{$column_count} - 1.25em * {$prev_col})";
                    }, $breakpoints);

                    $sizes_attr = 'sizes="' . implode(', ', array_merge($sizes, ['calc(100vw - 24px)'])) . '"';

                    // Assume all images are full width in a container.
                    $block_content = preg_replace('/sizes=".*"/', $sizes_attr, $block_content);
                } elseif ($is_media_text_block && array_key_exists('mediaId', $instance->attributes)) {
                    $media_id = $instance->attributes['mediaId'];
                    $media_width = $instance->attributes['mediaWidth'] ?? 50;

                    $srcset = wp_get_attachment_image_srcset($media_id, 'full');

                    if ('full' === $instance->attributes['align']) {
                        $sizes = !$instance->attributes['isStackedOnMobile'] ? "{$media_width}vw"
                            : "(min-width: 601px) {$media_width}vw, 100vw";

                        $sizes_attr = "sizes=\"{$sizes}\"";
                    } else {
                        $default = !$instance->attributes['isStackedOnMobile'] ?
                            "calc((100vw - 24px) * {$media_width} / 100)" : 'calc(100vw - 24px)';
                        $sizes = implode(
                            ',',
                            array_map(
                                function ($breakpoint) use ($instance, $media_width) {
                                    $screen = $breakpoint['screen'];
                                    $container = $breakpoint['width'];
                                    $should_stack = $screen < 600 && $instance->attributes['isStackedOnMobile'];
                                    $fraction = $should_stack ? 1 : round(100 / $media_width, 4);

                                    // Currently, we need to subtract 24px for Bootstrap container.
                                    return "(min-width: {$screen}px) calc(({$container} - 24px) / {$fraction})";
                                },
                                $breakpoints
                            )
                        );

                        $sizes_attr = "sizes=\"{$sizes}, {$default}\"";
                    }

                    $image_class_start = "class=\"wp-image-{$media_id} ";

                    $block_content = str_replace(
                        $image_class_start,
                        "$sizes_attr srcset=\"$srcset\" {$image_class_start}",
                        $block_content
                    );
                }

                return $block_content;
            },
            10,
            3
        );
    }
}
