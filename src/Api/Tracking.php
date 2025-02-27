<?php

declare(strict_types=1);

namespace P4\MasterTheme\Api;

use WP_REST_Server;
use WP_REST_Request;
use P4\MasterTheme\SqlParameters;

/**
 * Instance Tracking API
 */
class Tracking
{
    /**
     * Register endpoint to read general Tracking.
     *
     * @example GET /wp-json/planet4/v1/tracking/
     */

    public static int $last_days_default = 30;

    public static function register_endpoint(): void
    {
        register_rest_route(
            'planet4/v1',
            'tracking',
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => function (WP_REST_Request $request) {
                    $params = $request->get_params();

                    if (empty(filter_input(INPUT_GET, 'last_days', FILTER_SANITIZE_NUMBER_INT))) {
                        $params['last_days'] = self::$last_days_default;
                    }

                    $params['full'] = filter_var($params['full'], FILTER_VALIDATE_BOOLEAN);

                    return [
                        'logins' => self::get_logins($params),
                        'content_created' => self::get_content_created($params),
                    ];
                },
                'permission_callback' => function (WP_REST_Request $request) {
                    if (current_user_can('manage_options')) {
                        return true;
                    }

                    if (!defined('PLANET4_API_KEY') || empty(PLANET4_API_KEY)) {
                        return false;
                    }

                    $token = trim($request->get_header('X-Auth-Token') ?? '');

                    return !empty($token) && $token === PLANET4_API_KEY;
                },
                'args' => array(
                    'last_days' => array(
                        'required' => false,
                        'default' => self::$last_days_default,
                    ),
                    'full' => array(
                        'required' => false,
                        'default' => false,
                    ),
                ),
            ],
        );
    }

    /**
     * Retrieves the total number of logins in the last X days.
     *
     * @param array $params refers to request params
     * @return array Get logins.
    */
    private static function get_logins(array $params): array
    {
        global $wpdb;

        $response = [];
        $last_days = (60 * 60 * 24 * $params['last_days']);
        $now = time();

        $query = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT u.display_name, m.meta_value
                FROM {$wpdb->usermeta} AS m
                INNER JOIN {$wpdb->users} AS u ON m.user_id = u.ID
                WHERE m.meta_key = %s",
                'session_tokens'
            ),
            OBJECT
        );

        $data = [];

        foreach ($query as $row) {
            $unserialized = maybe_unserialize($row->meta_value);
            $key = array_keys($unserialized)[0];
            $login_date = $unserialized[$key]['login'];

            if (($now - $login_date) >= $last_days) {
                continue;
            }

            array_push(
                $data,
                [
                    'display_name' => $row->display_name,
                    // Convert epoch to human-readable date
                    'login_date' => date("Y-m-d H:i:s", $login_date),
                ]
            );
        }

        if ($params['full']) {
            $response['data'] = $data;
        }

        $response['total'] = count($data);

        return $response;
    }

    /**
     * Retrieves the total number of published posts created in the last X days, grouped by post type.
     *
     * @param array $params refers to request params
     * @return array Get tracking data.
    */
    private static function get_content_created(array $params): array
    {
        global $wpdb;

        $post_types = \get_post_types(
            [
                'public' => true,
                'exclude_from_search' => false,
            ]
        );

         $sql_params = new SqlParameters();
         $sql = 'SELECT post_type, count(ID) AS total
            FROM ' . $sql_params->identifier($wpdb->posts) . '
            WHERE post_date >= NOW() - INTERVAL ' . $params['last_days'] . ' DAY
            AND post_status = ' . $sql_params->string('publish') . '
            AND post_type IN ' . $sql_params->string_list($post_types) . '
            GROUP BY post_type';
         $results = $wpdb->get_results(
             // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
             $wpdb->prepare($sql, $sql_params->get_values()),
             \OBJECT
         );

        $data = [];

        foreach ($results as $row) {
            $data[$row->post_type] = [
                'total' => (int) $row->total,
            ];
        }

        return $data;
    }
}
