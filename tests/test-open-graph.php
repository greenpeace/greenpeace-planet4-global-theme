<?php

/**
 * Test custom open graph meta attributes.
 *
 * @package P4MT
 */

/**
 * Class OpenGraphTest.
 */
// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class OpenGraphTest extends P4TestCase
{
    use MarkupAssertionsTrait;

    /**
     * Test that custom open graph meta are rendered on the fronend.
     *
     * @param array  $post_data  Post data array.
     * @param string $template   Php template used to render the page.
     *
     * @dataProvider posts_with_custom_og_provider
     */
    public function test_post_custom_open_graph_data(array $post_data, string $template): void
    {
        // Get author user.
        $user = get_user_by('login', 'p4_author');
        wp_set_current_user($user->ID);

        $attachment_id = $this->factory->attachment->create_upload_object(
            dirname(__DIR__) . '/tests/data/images/pressmedia.jpg',
            0
        );
        $post_data['meta_input']['p4_og_image_id'] = $attachment_id;
        $post_data['meta_input']['_thumbnail_id'] = $attachment_id;
        $post_id = $this->factory->post->create($post_data);

        $permalink = get_permalink($post_id);
        $this->go_to($permalink);

        \Timber\Timber::$context_cache = [];

        wp_styles();
        $output = \Timber\Helper::ob_function(
            function () use ($template): void {
                    include get_template_directory() . '/' . $template;
            }
        );

        $this->assertHasElementWithAttributes(
            [
                'property' => 'og:title',
                'content' => $post_data['meta_input']['p4_og_title'] . ' - ' . get_bloginfo('name'),
            ],
            $output,
            'Did not find og:title meta with expected content.'
        );

        $this->assertHasElementWithAttributes(
            [
                'property' => 'og:description',
                'content' => wp_strip_all_tags($post_data['meta_input']['p4_og_description']),
            ],
            $output,
            'Did not find og:description meta with expected content.'
        );

        $this->assertHasElementWithAttributes(
            [
                'property' => 'og:image',
                'content' => wp_get_attachment_url($attachment_id),
            ],
            $output,
            'Did not find og:image meta with expected content.'
        );

        $this->assertHasElementWithAttributes(
            [
                'property' => 'og:image:width',
            ],
            $output,
            'Did not find og:image:width meta with expected content.'
        );
    }

    /**
     * Test that open graph meta are rendered on the frontend.
     *
     * @param array  $post_data  Post data array.
     * @param string $template   Php template used to render the page.
     *
     * @dataProvider posts_provider
     */
    public function test_post_open_graph_data(array $post_data, string $template): void
    {
        // Get author user.
        $user = get_user_by('login', 'p4_author');
        wp_set_current_user($user->ID);

        // Create a sample post.
        $attachment_id = $this->factory->attachment->create_upload_object(
            dirname(__DIR__) . '/tests/data/images/pressmedia.jpg',
            0
        );
        $post_data['meta_input']['_thumbnail_id'] = $attachment_id;
        $post_id = $this->factory->post->create($post_data);

        $permalink = get_permalink($post_id);
        $this->go_to($permalink);

        \Timber\Timber::$context_cache = [];

        wp_styles();
        $output = \Timber\Helper::ob_function(
            function () use ($template): void {
                    include get_template_directory() . '/' . $template;
            }
        );

        $this->assertHasElementWithAttributes(
            [
                'property' => 'og:title',
                'content' => $post_data['post_title'] . ' - ' . get_bloginfo('name'),
            ],
            $output,
            'Did not find og:title meta with expected content.'
        );

        $this->assertHasElementWithAttributes(
            [
                'property' => 'og:description',
                'content' => $post_data['post_excerpt'],
            ],
            $output,
            'Did not find og:description meta with expected content.'
        );
    }

    /**
     * Provide test cases for custom open graph values.
     * Each row contains:
     * Post data, Php template, Attachment id
     *
     */
    public function posts_with_custom_og_provider(): array
    {
        return [
            [
                [
                    'post_type' => 'post',
                    'post_title' => 'The name of the place is Babylon',
                    'post_name' => 'test-social-url',
                    'post_content' => 'test content',
                    'meta_input' => [
                        'p4_og_title' => 'Custom open graph title',
                        'p4_og_description' => 'Custom open graph description',
                    ],
                ],
                'single.php',
            ],
            [
                [
                    'post_type' => 'page',
                    'post_title' => 'The name of the place is Babylon',
                    'post_name' => 'test-social-url',
                    'post_content' => 'test content',
                    'meta_input' => [
                        'p4_og_title' => 'Custom open graph title',
                        'p4_og_description' => 'Custom open graph description',
                    ],
                ],
                'page.php',
            ],
            // Test html tags in OG description.
            [
                [
                    'post_type' => 'page',
                    'post_title' => 'The name of the place is Babylon',
                    'post_name' => 'test-social-url',
                    'post_content' => 'test content',
                    'meta_input' => [
                        'p4_og_title' => 'Custom open graph title',
                        'p4_og_description' => '<p>Custom open graph description</p>',
                    ],
                ],
                'page.php',
            ],
        ];
    }

    /**
     * Provide test cases for open graph values.
     * Each row contains:
     * Post data, Php template
     *
     */
    public function posts_provider(): array
    {
        return [
            [
                [
                    'post_type' => 'post',
                    'post_title' => 'The name of the place is Babylon',
                    'post_name' => 'test-social-url',
                    'post_content' => 'test content',
                    'post_excerpt' => 'post excerpt',
                ],
                'single.php',
            ],
            [
                [
                    'post_type' => 'page',
                    'post_title' => 'The name of the place is Babylon',
                    'post_name' => 'test-social-url',
                    'post_content' => 'test content',
                    'post_excerpt' => 'post excerpt',
                ],
                'page.php',
            ],
        ];
    }
}
