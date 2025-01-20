<?php

declare(strict_types=1);

namespace P4\MasterTheme;

/**
 * Update native Query Loop block pagination.
 */
class QueryLoopPagination
{
    public static function hooks(): void
    {
        // Remove native Query Loop block pagination when it's disabled.
        add_filter(
            'render_block_core/query-pagination',
            function ($content) {
                // Check if prev and next buttons are disabled.
                if (
                    str_contains($content, 'wp-block-query-pagination-previous disabled')
                    && str_contains($content, 'wp-block-query-pagination-next disabled')
                ) {
                    return null;
                }

                return $content;
            },
            10,
            3
        );

        // Update native Query Loop block pagination "Previous Page" button label to "Prev".
        // Also, show it even when it is disabled.
        add_filter(
            'render_block_core/query-pagination-previous',
            // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter -- filter callback
            function ($content, $parsed, $block) {
                $button_label = __('Prev', 'planet4-blocks');

                if (!array_key_exists('label', $block->attributes)) {
                    $block->attributes['label'] = $button_label;
                    return $block->render();
                }

                // Check if the button isn't rendered, then return it.
                if (empty($content)) {
                    return '<a href="/" class="wp-block-query-pagination-previous disabled">' . $button_label . '</a>';
                }

                return $content;
            },
            10,
            3
        );

        // Update native Query Loop block pagination "Next Page" button label to "Next".
        // Also, show it even when it is disabled.
        add_filter(
            'render_block_core/query-pagination-next',
            // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter -- filter callback
            function ($content, $parsed, $block) {
                $button_label = __('Next', 'planet4-blocks');

                if (!array_key_exists('label', $block->attributes)) {
                    $block->attributes['label'] = $button_label;
                    return $block->render();
                }

                // Check if the button isn't rendered, then return it.
                if (empty($content)) {
                    return '<a href="/" class="wp-block-query-pagination-next disabled">' . $button_label . '</a>';
                }

                return $content;
            },
            10,
            3
        );
    }
}
