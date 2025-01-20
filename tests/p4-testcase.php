<?php

/**
 * P4 Test Case Class
 *
 * @package P4MT
 */

/**
 * Class P4TestCase.
 */
// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class P4TestCase extends WP_UnitTestCase
{
    public int $attachment_id;

    /**
     * Setup test
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->initialize_planet4_data();
        require_once get_template_directory() . '/functions.php';
    }

    /**
     * Use wp unit testcase factories to create data in database for the tests.
     */
    private function initialize_planet4_data(): void
    {
        $this->attachment_id = (int) $this->factory->attachment->create_upload_object(
            dirname(__DIR__) . '/tests/data/images/pressmedia.jpg',
            0
        );

        // Create a user with editor role.
        $this->factory->user->create(
            [
                'role' => 'editor',
                'user_login' => 'p4_editor',
            ]
        );

        // Create a user with editor role.
        $this->factory->user->create(
            [
                'role' => 'author',
                'user_login' => 'p4_author',
            ]
        );

        // Get admin user.
        $user = get_user_by('login', 'admin');
        wp_set_current_user($user->ID);

        // Create Act & Explore pages
        // Accepts the same arguments as wp_insert_post.
        $act_page_id = $this->factory->post->create(
            [
                'post_type' => 'page',
                'post_title' => 'ACT',
                'post_name' => 'act',
                'meta_input' => [
                    '_thumbnail_id' => $this->attachment_id,
                ],
            ]
        );

        $explore_page_id = $this->factory->post->create(
            [
                'post_type' => 'page',
                'post_title' => 'EXPLORE',
                'post_name' => 'explore',
                'meta_input' => [
                    '_thumbnail_id' => $this->attachment_id,
                ],
            ]
        );

        $this->factory->post->create(
            [
                'post_type' => 'page',
                'post_title' => 'Take action page',
                'post_name' => 'take-action-page',
                'post_parent' => $act_page_id,
                'meta_input' => [
                    '_thumbnail_id' => $this->attachment_id,
                ],
            ]
        );

        $issue_cat_id = $this->factory->category->create(
            [
                'name' => 'Issues',
                'slug' => 'issues',
                'description' => 'Issues we work on',
            ]
        );

        $issues_cat = get_category_by_slug('issues');
        $this->factory->category->create(
            [
                'name' => 'Nature',
                'slug' => 'nature',
                'parent' => $issues_cat->term_id,
                'description' => 'Focusing on great global forests and oceans we aim to preserve,
								  protect and restore the most valuable
								  ecosystems for the climate and for biodiversity.',
            ]
        );

        // Create tag.
        $this->factory->tag->create(
            [
                'name' => 'ArcticSunrise',
                'slug' => 'arcticsunrise',
            ]
        );

        // Create p4-page-type terms.
        $this->factory->term->create(
            [
                'name' => 'Story',
                'taxonomy' => 'p4-page-type',
                'slug' => 'story',
            ]
        );
        $this->factory->term->create(
            [
                'name' => 'Publication',
                'taxonomy' => 'p4-page-type',
                'slug' => 'publication',
            ]
        );
        $this->factory->term->create(
            [
                'name' => 'Press Release',
                'taxonomy' => 'p4-page-type',
                'slug' => 'press-release',
            ]
        );

        // Create action-type terms.
        $this->factory->term->create(
            [
                'name' => 'Event',
                'taxonomy' => 'action-type',
                'slug' => 'event',
            ]
        );
        $this->factory->term->create(
            [
                'name' => 'Petition',
                'taxonomy' => 'action-type',
                'slug' => 'petition',
            ]
        );
        $this->factory->term->create(
            [
                'name' => 'Contest',
                'taxonomy' => 'action-type',
                'slug' => 'contest',
            ]
        );

        wp_set_current_user(0);
    }
}
