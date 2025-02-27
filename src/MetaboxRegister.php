<?php

namespace P4\MasterTheme;

/**
 * Class MetaboxRegister
 */
class MetaboxRegister
{
    /**
     * The maximum number of take action pages to show in dropdown.
     *
     * @const int MAX_TAKE_ACTION_PAGES
     */
    public const MAX_TAKE_ACTION_PAGES = 100;

    /**
     * MetaboxRegister constructor.
     */
    public function __construct()
    {
        $this->hooks();
    }

    /**
     * Class hooks.
     */
    private function hooks(): void
    {
        add_action('cmb2_init', [ $this, 'register_p4_meta_box' ]);
    }

    /**
     * Register P4 meta box.
     */
    public function register_p4_meta_box(): void
    {
        $this->register_meta_box_post();
    }

    /**
     * Register Post meta box.
     */
    public function register_meta_box_post(): void
    {

        $p4_post = new_cmb2_box(
            [
                'id' => 'p4_metabox_post',
                'title' => __('Post Articles Element Fields', 'planet4-master-theme-backend'),
                'object_types' => [ 'post' ],
            ]
        );

        $p4_post->add_field(
            [
                'name' => __('Author Override', 'planet4-master-theme-backend'),
                'desc' => __('Enter author name if you want to override the author', 'planet4-master-theme-backend'),
                'id' => 'p4_author_override',
                'type' => 'text_medium',
            ]
        );

        $p4_post->add_field(
            [
                'name' => __('Include Related Posts', 'planet4-master-theme-backend'),
                'id' => 'include_articles',
                'type' => 'select',
                'options' => [
                    'yes' => 'Yes',
                    'no' => 'No',
                ],
            ]
        );

        $p4_post->add_field(
            [
                'name' => __('Background Image Override', 'planet4-master-theme-backend'),
                'desc' => __(
                    'Upload an image or select one from the media library to override the background image',
                    'planet4-master-theme-backend'
                ),
                'id' => 'p4_background_image_override',
                'type' => 'file',
                'options' => [
                    'url' => false,
                ],
                'text' => [
                    'add_upload_file_text' => __('Add Image', 'planet4-master-theme-backend'),
                ],
                'preview_size' => 'large',
            ]
        );
    }
}
