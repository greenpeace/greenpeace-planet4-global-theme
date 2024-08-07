<?php

/**
 * Displays a Campaign (Tag) page.
 *
 * Category <-> Issue
 * Tag <-> Campaign
 * Post <-> Action
 *
 * @package P4MT
 */

use P4\MasterTheme\Context;
use P4\MasterTheme\ListingPage;
use Timber\Timber;

if (!is_tag()) {
    exit();
}

$tag = get_queried_object();
$redirect_id = get_term_meta($tag->term_id, 'redirect_page', true);

if ($redirect_id) {
    global $wp_query;
    $redirect_page = get_post($redirect_id);
    $wp_query->queried_object = $redirect_page;
    $wp_query->queried_object_id = $redirect_page->ID;

    // Allow modification of redirect page behavior.
    do_action('p4_action_tag_page_redirect', $redirect_page);

    include 'page.php';
    exit();
}

$post = Timber::get_post(false); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
$context = Timber::context();
if ($post instanceof \WP_Post) {
    $post = Timber::get_post($post->ID);
    Context::set_og_meta_fields($context, $post);
}

$context['taxonomy'] = $tag;
$context['tag_name'] = single_tag_title('', false);
$context['tag_description'] = wpautop($tag->description);

// Temporary fix with rewind, cf. https://github.com/WordPress/gutenberg/issues/53593
rewind_posts();

$templates = ['tag.twig', 'archive.twig', 'index.twig'];
$page = new ListingPage($templates, $context);
