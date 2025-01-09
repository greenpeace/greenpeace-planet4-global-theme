<?php

/**
 * HappyPoint block class
 *
 * @package P4\MasterTheme
 * @since 0.1
 */

 namespace P4\MasterTheme\Blocks;

 use WP_REST_Server;

/**
 * Class HappyPoint
 *
 * @package P4\MasterTheme\Blocks
 */
class HappyPoint extends BaseBlock
{
    /**
     * Block name.
     *
     * @const string BLOCK_NAME.
     */
    public const BLOCK_NAME = 'happypoint';

    /**
     * HappyPoint constructor.
     */
    public function __construct()
    {
        register_block_type(
            self::get_full_block_name(),
            [
                'editor_script' => 'planet4-blocks',
                // todo: Remove when all content is migrated.
                'render_callback' => [ self::class, 'render_frontend' ],
                'attributes' => [
                    'id' => [
                        'type' => 'integer',
                    ],
                    'focus_image' => [
                        'type' => 'string',
                    ],
                    'opacity' => [
                        'type' => 'integer',
                        'default' => 30,
                    ],
                    'iframe_url' => [
                        'type' => 'string',
                        'default' => '',
                    ],
                    'use_embed_code' => [
                        'type' => 'boolean',
                    ],
                    'embed_code' => [
                        'type' => 'string',
                        'default' => '',
                    ],
                    'override_default_content' => [
                        'type' => 'boolean',
                        'default' => 'false',
                    ],
                    'local_content_provider' => [
                        'type' => 'string',
                        'default' => 'none',
                    ],
                ],
            ]
        );

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
     * Get the required data for the frontend.
     *
     * @param object $fields This object contains image search params, such as `id`.
     *
     * @return array Image data.
     */
    public static function get_data(object $fields): array
    {
        $options = get_option('planet4_options');
        $image_from_settings = $options['happy_point_bg_image_id'] ?? '';
        $image_id = $fields['id'] ?? $image_from_settings;
        $img_meta = wp_get_attachment_metadata($image_id);
        $image_alt = get_post_meta($image_id, '_wp_attachment_image_alt', true);

        $data = [];
        $data['background_src'] = wp_get_attachment_image_src($image_id, 'retina-large')[0] ?? false;
        $data['background_srcset'] = wp_get_attachment_image_srcset($image_id, 'retina-large', $img_meta);
        $data['background_sizes'] = wp_calculate_image_sizes('retina-large', null, null, $image_id);
        $data['default_content_provider'] = $options['happy_point_content_provider'] ?? 'iframe_url';
        $data['engaging_network_id'] = $options['engaging_network_form_id'] ?? '';
        $data['default_image'] = get_template_directory_uri() . '/images/happy-point-block-bg.jpg';
        $data['background_alt'] = empty($image_alt) ? __('Background image', 'planet4-blocks') : $image_alt;
        $data['default_embed_code'] = $options['happy_point_embed_code'] ?? '';

        return $data;
    }

    /**
     * Endpoint to retrieve the data for the Happy Point block
     *
     * @example GET /wp-json/planet4/v1/get-happypoint-data
     */
    public static function register_endpoint(): void
    {
        register_rest_route(
            self::REST_NAMESPACE,
            '/get-happypoint-data',
            [
                [
                    'permission_callback' => static function () {
                        return true;
                    },
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => static function ($fields) {
                        $to_return = self::get_data($fields);
                        return rest_ensure_response($to_return);
                    },
                ],
            ]
        );
    }
}
