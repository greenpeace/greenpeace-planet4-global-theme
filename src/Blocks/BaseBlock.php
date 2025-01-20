<?php

/**
 * Base block class.
 *
 * @package P4\MasterTheme
 */

namespace P4\MasterTheme\Blocks;

/**
 * Class BaseBlock
 *
 * @package P4\MasterTheme\Blocks
 */
abstract class BaseBlock
{
    public const NAMESPACE = 'planet4-blocks';
    public const REST_NAMESPACE = 'planet4/v1';

    /**
     * Get all the data that will be needed to render the block correctly.
     *
     * @param array $fields This is the array of fields of this block.
     *
     * @return array The data to be passed in the View.
     */
    abstract public function prepare_data(array $fields): array;

    /**
     * @param array $attributes Block attributes.
     *
     * @return mixed
     */
    public function render(array $attributes)
    {
        $data = $this->prepare_data($attributes);

        $block_output = \Timber::compile(static::BLOCK_NAME . '.twig', $data);

        // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
        $empty_message = defined('static::EMPTY_MESSAGE') ? __(static::EMPTY_MESSAGE, 'planet4-blocks') :
            "Block content is empty. Check the block's settings or remove it.";

        // Return empty string if rendered output contains only whitespace or new lines.
        // If it is a rest request from editor/admin area, return a message that block has no content.
        $empty_content = $this->is_rest_request() ? '<div class="EmptyMessage">' . $empty_message . '</div>' : '';

        return ctype_space($block_output) ? $empty_content : $block_output;
    }

    /**
     * Returns if current request is a rest api request.
     */
    public static function is_rest_request(): bool
    {
        return defined('REST_REQUEST') && REST_REQUEST;
    }

    /**
     * Returns the block name with its namespace prefix, e.g.: planet4-blocks/accordion.
     */
    public static function get_full_block_name(): string
    {
        return static::NAMESPACE . '/' . static::BLOCK_NAME;
    }

    /**
     * Renders a `div` with the block's attributes in a `data-` attribute to be handled in the frontend.
     *
     * @param array $attributes The block's attributes.
     *
     * @return string.
     */
    public static function render_frontend(array $attributes): string
    {
        $json = wp_json_encode([ 'attributes' => $attributes ]);

        return '<div data-render="' . self::get_full_block_name() .
            '" data-attributes="' . htmlspecialchars($json) . '"></div>';
    }

    /**
     * Register scripts and styles for a block
     */
    public static function enqueue_editor_assets(): void
    {
        static::enqueue_editor_script();
        static::enqueue_frontend_style();
        static::enqueue_editor_style();
    }

    /**
     * Enqueue assets for the frontend IF the blocks is present.
     */
    public static function enqueue_frontend_assets(): void
    {
        $full_name = static::get_full_block_name();
        $beta_blocks = [];

        $to_look_for = $full_name;
        if (in_array($full_name, $beta_blocks, true)) {
            $to_look_for .= '-beta';
        }

        if (! is_preview() && ! BlockList::has_block($to_look_for)) {
            return;
        }

        static::enqueue_frontend_script();
        static::enqueue_frontend_style();
    }

    /**
     * Editor script
     */
    public static function enqueue_editor_script(): void
    {
        $filepath = static::get_dir_path() . 'EditorScript.js';
        if (! file_exists($filepath)) {
            return;
        }
        $rel_filepath = static::get_rel_path() . 'EditorScript.js';

        wp_enqueue_script(
            static::get_full_block_name() . '-editor-script',
            static::get_url_path() . 'EditorScript.js',
            'planet4-blocks-editor-script',
            \P4\MasterTheme\Loader::theme_file_ver($rel_filepath),
            true
        );
    }

    /**
     * Editor style
     */
    public static function enqueue_editor_style(): void
    {
        $filepath = static::get_dir_path() . 'EditorStyle.min.css';
        if (! file_exists($filepath)) {
            return;
        }
        $rel_filepath = static::get_rel_path() . 'EditorStyle.min.css';

        wp_enqueue_style(
            static::get_full_block_name() . '-editor-style',
            static::get_url_path() . 'EditorStyle.min.css',
            // Ensure loaded both after main stylesheet and block's front end styles.
            [ 'planet4-editor-style', static::get_full_block_name() . '-style' ],
            \P4\MasterTheme\Loader::theme_file_ver($rel_filepath),
        );
    }

    /**
     * Frontend script
     */
    public static function enqueue_frontend_script(): void
    {
        $filepath = static::get_dir_path() . 'Script.js';
        if (! file_exists($filepath)) {
            return;
        }
        $rel_filepath = static::get_rel_path() . 'Script.js';

        wp_enqueue_script(
            static::get_full_block_name() . '-script',
            static::get_url_path() . 'Script.js',
            'planet4-blocks-script',
            \P4\MasterTheme\Loader::theme_file_ver($rel_filepath),
            true
        );
    }

    /**
     * Frontend style
     */
    public static function enqueue_frontend_style(): void
    {
        $filepath = static::get_dir_path() . 'Style.min.css';
        if (! file_exists($filepath)) {
            return;
        }
        $rel_filepath = static::get_rel_path() . 'Style.min.css';

        wp_enqueue_style(
            static::get_full_block_name() . '-style',
            static::get_url_path() . 'Style.min.css',
            [],
            \P4\MasterTheme\Loader::theme_file_ver($rel_filepath),
        );
    }

    /**
     * Converts the hy-phe-na-ted block name into CamelCase.
     */
    public static function get_camelized_block_name(): string
    {
        return str_replace('-', '', ucwords(static::BLOCK_NAME, '-'));
    }

    /**
     * Return URL path to plugin assets
     */
    public static function get_url_path(): string
    {
        return trailingslashit(get_template_directory_uri())
            . 'assets/build/' . static::get_camelized_block_name();
    }

    /**
     * Return directory path to plugin assets
     */
    public static function get_dir_path(): string
    {
        return trailingslashit(get_template_directory())
            . 'assets/build/' . static::get_camelized_block_name();
    }

    /**
     * Return relative path to blocks assets
     */
    public static function get_rel_path(): string
    {
        return 'assets/build/' . static::get_camelized_block_name();
    }

    /**
     * Hydrate a `div` with the block's attributes
     * in a `data-` attribute to be handled in the frontend.
     *
     * @param array  $attributes The block's attributes.
     * @param string $content The block's content.
     */
    public static function hydrate_frontend(array $attributes, string $content): string
    {
        $json = wp_json_encode([ 'attributes' => $attributes ]);

        // This will double check to parse ONLY hydrated blocks.
        if (! strpos($content, 'data-hydrate')) {
            return self::render_frontend($attributes);
        }

        // Parse to get the only the block content and not the whole block.
        preg_match_all('/>/', $content, $matches, PREG_OFFSET_CAPTURE);
        $start = $matches[0][1][1] + 1;
        $content = substr($content, $start);
        preg_match_all('/<\/div>/', $content, $matches, PREG_OFFSET_CAPTURE);
        $end = $matches[0][ count($matches[0]) - 2 ][1];
        $content = substr($content, 0, $end);

        return '<div data-hydrate="' . self::get_full_block_name()
            . '" data-attributes="' . htmlspecialchars($json) . '">'
            . $content . '</div>';
    }
}
