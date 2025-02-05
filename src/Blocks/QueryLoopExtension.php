<?php

declare(strict_types=1);

namespace P4\MasterTheme\Blocks;

/**
 * Handle Posts/Actions List block manual override query with custom `postIn` filter.
 * `include` is not used because it generates some issues with getEntityRecords filters.
 * Handle filtering password-potected posts.
 */
class QueryLoopExtension
{
    public static function registerHooks(): void
    {
        self::registerEditorQuery();
        self::registerFrontendQuery();
    }

    public static function registerEditorQuery(): void
    {
        $postInFilter = function ($args, $request) {
            $postIn = $request->get_param('postIn');
            if (!empty($postIn)) {
                $args['post__in'] = array_map('intval', (array) $postIn);
                $args['orderby'] = 'post__in';
            }
            if ($request->has_param('hasPassword')) {
                $hasPassword = $request->get_param('hasPassword');
                $args['has_password'] = $hasPassword !== false && $hasPassword !== 'false';
            }
            return $args;
        };

        add_filter('rest_post_query', $postInFilter, 10, 2);
        add_filter('rest_page_query', $postInFilter, 10, 2);
        add_filter('rest_p4_action_query', $postInFilter, 10, 2);
    }

    public static function registerFrontendQuery(): void
    {
        add_filter(
            'query_loop_block_query_vars',
            function ($query, $block) {
                $blockQuery = $block->context['query'] ?? [];

                $is_new_ia = !empty(planet4_get_option('new_ia'));

                $query['post_type'] = ['page'];

                if (!$is_new_ia) {
                    $query['post_parent'] = $blockQuery['parent'];
                } else {
                    array_push($query['post_type'], 'p4_action');
                    $query['post_parent__in'] = [$blockQuery['parent'], planet4_get_option('take_action_page')];
                }

                if (!empty($blockQuery['postIn'])) {
                    $query['post__in'] = array_map('intval', (array) $blockQuery['postIn']);
                    $query['orderby'] = 'post__in';
                }

                if (isset($blockQuery['hasPassword'])) {
                    $query['has_password'] = (bool) $blockQuery['hasPassword'];
                }

                return $query;
            },
            10,
            2
        );
    }
}
