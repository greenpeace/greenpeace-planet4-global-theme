<?php

declare(strict_types=1);

namespace P4\MasterTheme\Api;

use WP_REST_Server;
use P4\MasterTheme\AnalyticsValues as AV;

/**
 * Analytics values API
 */
class AnalyticsValues
{
    /**
     * Register endpoint to read analytics values.
     *
     * @example GET /wp-json/planet4/v1/analytics-values/
     */
    public static function register_endpoint(): void
    {
        register_rest_route(
            'planet4/v1',
            '/analytics-values',
            [
                [
                    'methods' => WP_REST_Server::READABLE,
                    'permission_callback' => static function () {
                        return current_user_can('edit_posts');
                    },
                    'callback' => static function ($request) {
                        $post_id = (int) $request->get_param('id');

                        $analytics_values = AV::from_cache_or_api_or_hardcoded();

                        $global_options = $analytics_values->global_projects_options($post_id);
                        $local_options = $analytics_values->local_projects_options($post_id);
                        $basket_options = $analytics_values->basket_options();

                        return rest_ensure_response(
                            [
                                [
                                    'global_projects' => array_map(
                                        fn ($k, $v) => [
                                            'label' => $v,
                                            'value' => $k,
                                        ],
                                        array_keys($global_options),
                                        array_values($global_options)
                                    ),
                                    'local_projects' => array_map(
                                        fn ($k, $v) => [
                                            'label' => $v,
                                            'value' => $k,
                                        ],
                                        array_keys($local_options),
                                        array_values($local_options)
                                    ),
                                    'baskets' => array_map(
                                        fn ($k, $v) => [
                                            'label' => $v,
                                            'value' => $k,
                                        ],
                                        array_keys($basket_options),
                                        array_values($basket_options)
                                    ),
                                ],
                            ]
                        );
                    },
                ],
            ],
        );
    }
}
