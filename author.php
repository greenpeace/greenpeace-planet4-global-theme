<?php

/**
 * The template for displaying Author Archive pages
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since    Timber 0.1
 */

use P4\MasterTheme\User;
use P4\MasterTheme\Post;
use P4\MasterTheme\ListingPage;
use Timber\Timber;

$context = Timber::get_context();

if (isset($wp_query->query_vars['author'])) {
    $author = new User($wp_query->query_vars['author']);
    $context['author'] = $author;
    $context['title'] = 'Author Archives: ' . $author->name();

    $context['page_category'] = 'Author Page';

    $context['social_accounts'] = Post::filter_social_accounts(
        $context['footer_social_menu'] ?: []
    );
    $context['og_title'] = $author->name;
    $context['og_description'] = get_the_author_meta('description', $author->ID);
    $context['og_image_data'] = [
        'url' => get_avatar_url($author->ID, [ 'size' => 300, 'default' => $context['default_avatar']]),
        'width' => '300',
        'height' => '300',
    ];
}

do_action('enqueue_google_tag_manager_script', $context);
$templates = ['author.twig', 'archive.twig'];
$page = new ListingPage($templates, $context);
