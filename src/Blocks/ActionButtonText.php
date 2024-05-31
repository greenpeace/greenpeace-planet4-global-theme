<?php

/**
 * Action Button Text block class.
 *
 * @package P4\MasterTheme
 * @since 0.1
 */

 namespace P4\MasterTheme\Blocks;

 use WP_Block;

/**
 * Class ActionButtonText
 *
 * @package P4\MasterTheme\Blocks
 */
class ActionButtonText extends BaseBlock
{
    /**
     * Block name.
     *
     * @const string BLOCK_NAME.
     */
    public const BLOCK_NAME = 'action-button-text';

    /**
     * ActionButtonText constructor.
     */
    public function __construct()
    {
        $this->register_action_button_text_block();
    }

    /**
     * Register ActionButtonText block.
     */
    public function register_action_button_text_block(): void
    {
        register_block_type(
            self::get_full_block_name(),
            [
                'render_callback' => [ $this, 'render_block' ],
            ]
        );
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

    /**
     * @param  array    $attributes Block attributes.
     * @param  string   $content    Block default content.
     * @param  WP_Block $block      Block instance.
     * @return string   Returns the value for the field.
     * @phpcs:disable SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
    */
    public function render_block(array $attributes, string $content, WP_Block $block): string
    {
        $post_id = $block->context['postId'];
        $link = get_permalink($post_id);
        $meta = get_post_meta($post_id);
        if (isset($meta['action_button_text']) && $meta['action_button_text'][0]) {
            $button_text = $meta['action_button_text'][0];
        } else {
            $options = get_option('planet4_options');
            $button_text = $options['take_action_covers_button_text'] ?? __('Take action', 'planet4-blocks');
        }
        return '<a href="' . $link . '" class="btn btn-primary btn-small">' . $button_text . '</a>';
    }
    // @phpcs:enable SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
}
