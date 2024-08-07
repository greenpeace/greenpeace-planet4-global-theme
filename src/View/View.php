<?php

/**
 * View class
 */

namespace P4\MasterTheme\View;

use Timber\Timber;

/**
 * Class View
 */
class View
{
    /** The path to the template files. */
    private string $template_dir = '/templates/';

    /** The path to the template files override subfolder, relative to the child theme. */
    private string $template_override_subdir = '/templates/plugins/planet4-plugin-gutenberg-blocks/includes/';

    /**
     * Creates the View object.
     */
    public function __construct()
    {
    }

    /**
     * Compile and return a template file.
     *
     * @param array|string $template_name The file name of the template to render.
     * @param array        $data The data to pass to the template.
     * @param string       $relevant_dir The path to a subdirectory where the template is located
     *                     (relative to $template_dir).
     *
     * @return bool|string The returned output
     */
    public function get_template(
        array|string $template_name,
        array $data,
        string $relevant_dir = 'block_templates/'
    ): bool|string {
        return Timber::compile([$relevant_dir . $template_name . '.twig'], $data);
    }

    /**
     * Uses the appropriate templating engine to render a template file.
     *
     * @param array|string $template_name The file name of the template to render.
     * @param array        $data The data to pass to the template.
     * @param string       $relevant_dir The path to a subdirectory where the template is located
     *                     (relative to $template_dir).
     * @param bool         $compile A boolean to compile the template.
     */
    public function view_template(
        array|string $template_name,
        array $data,
        string $relevant_dir = '',
        bool $compile = false
    ): string|null {
        if ($compile) {
            return Timber::compile([$relevant_dir . $template_name . '.twig'], $data);
        }

        Timber::render([$relevant_dir . $template_name . '.twig'], $data);
        return null;
    }

    /**
     * Overrides the template file if a child theme is active and contains one.
     *
     * @param array|string $template_name The file name of the template to render.
     * @param string       $relevant_dir The path to a subdirectory where the template is located
     *                     (relative to $template_dir or $template_override_subdir).
     * @param string       $template_ext The extension of the template (php, twig, ...).
     *
     * @return string      The returned output
     */
    private function get_template_dir(
        array|string $template_name,
        string $relevant_dir = 'block_templates/',
        string $template_ext = 'twig'
    ): string {
        if (is_child_theme()) {
            $override_dir = get_stylesheet_directory() . $this->template_override_subdir;
            if (file_exists($override_dir . $relevant_dir . $template_name . '.' . $template_ext)) {
                return $override_dir;
            }
        }

        return $this->template_dir;
    }

    /**
     * Render the settings page of the plugin.
     *
     * @param array $data All the data needed to render the template.
     */
    public function settings(array $data): void
    {
        $this->view_template(__FUNCTION__, $data, 'block_templates/');
    }

    /**
     * Uses the appropriate templating engine to render a template file.
     *
     * @param array|string $template_name The file name of the template to render.
     * @param array        $data The data to pass to the template.
     * @param string       $template_ext The extension of the template (php, twig, ...).
     * @param string       $relevant_dir The path to a subdirectory where the template is located
     *                     (relative to $template_dir).
     */
    public function block(
        array|string $template_name,
        array $data,
        string $template_ext = 'twig',
        string $relevant_dir = 'block_templates/'
    ): void {
        $template_dir = $this->get_template_dir($template_name, $relevant_dir, $template_ext);
        if ('twig' === $template_ext) {
            Timber::render([$relevant_dir . $template_name . '.' . $template_ext], $data);
        } else {
            include_once $template_dir . $relevant_dir . $template_name . '.' . $template_ext;
        }
    }

    /**
     * Render EN Form Post.
     *
     * @param array $data All the data needed to render the template.
     */
    public function enform_post(array $data): void
    {
        $this->view_template(__FUNCTION__, $data, '/block_templates/enform/');
    }

    /**
     * Render the Selected Components meta box for EN Forms.
     *
     * @param array $data All the data needed to render the template.
     */
    public function en_selected_meta_box(array $data): void
    {
        $this->view_template(__FUNCTION__, $data, 'block_templates/');
    }
}
