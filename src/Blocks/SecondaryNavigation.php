<?php

/**
 * SecondaryNavigation block class.
 *
 * @package P4\MasterTheme
 * @since 0.1
 */

 namespace P4\MasterTheme\Blocks;

/**
 * Class SecondaryNavigation
 *
 * @package P4\MasterTheme\Blocks
 */
class SecondaryNavigation extends BaseBlock
{
    /**
     * Block name.
     *
     * @const string BLOCK_NAME.
     */
    public const BLOCK_NAME = 'secondary-navigation';

    /**
     * SecondaryNavigation constructor.
     */
    public function __construct()
    {

        $this->register_secondary_navigation_block();
    }

    /**
     * Register SecondaryNavigation block.
     */
    public function register_secondary_navigation_block(): void
    {
        register_block_type(
            self::get_full_block_name(),
            [
                'render_callback' => [ self::class, 'render_frontend' ],
                'attributes' => [
                    'levels' => [
                        'type' => 'array',
                        'default' => [
                            [
                                'heading' => 2,
                                'link' => true,
                            ],
                        ],
                    ],
                ],
            ]
        );

        add_action('enqueue_block_editor_assets', [ self::class, 'enqueue_editor_assets' ]);
        add_action('wp_enqueue_scripts', [ self::class, 'enqueue_frontend_assets' ]);
    }

    /**
     * Required by the `Base_Block` class.
     *
     * @param array $fields Unused, required by the abstract function.
     * @phpcs:disable SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     */
    public function prepare_data(array $fields): array
    {
        return [];
    }
    // @phpcs:enable SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
}
