<?php

/**
 * The main template file
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being main.css).
 * It is used to display a page when nothing more specific matches a query.
 * E.g., it puts together the home page when no home.php file exists
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since   Timber 0.1
 */

use P4\MasterTheme\Context;
use P4\MasterTheme\Post;
use P4\MasterTheme\ListingPage;
use Timber\Timber;

$context = Timber::get_context();
$posts = Timber::get_posts();
$templates = [ 'index.twig' ];

if (is_home()) {
    $post = new Post(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
    $post->set_data_layer();
    $data_layer = $post->get_data_layer();

    $page_meta_data = get_post_meta($post->ID);
    $page_meta_data = array_map(fn ($v) => reset($v), $page_meta_data);

    $context['title'] = ( $page_meta_data['p4_title'] ?? '' )
        ? ( $page_meta_data['p4_title'] ?? '' )
        : html_entity_decode($context['wp_title'] ?? '');

    Context::set_header($context, $page_meta_data, $context['title']);
    Context::set_background_image($context);
    Context::set_og_meta_fields($context, $post);
    Context::set_campaign_datalayer($context, $page_meta_data);
    Context::set_utm_params($context, $post);

    $MIN_STICKY_POSTS = 4;
    $sticky_posts = array_filter($posts, function ($p) {
        if(is_sticky($p->ID)) {
            // echo $p->ID . "</br>";
            return $p;
        }
    });

    if(count($sticky_posts) > $MIN_STICKY_POSTS) {
        $posts = array_filter($posts, function ($p) {
            if(!is_sticky($p->ID)) {
                return $p;
            }
        });
    } else {
        $sticky_posts = array();
    }

    $context['posts'] = $posts;
    $context['sticky_posts'] = $sticky_posts;

    array_unshift($templates, 'all-posts.twig');

    $page = new ListingPage($templates, $context);
} else {
    do_action('enqueue_google_tag_manager_script', $context);
    Timber::render($templates, $context);
}
