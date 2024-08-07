<?php

/**
 * The template for displaying Author Archive pages
 *
 * Methods for \Timber\Helper can be found in the /lib sub-directory
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since    Timber 0.1
 */

use P4\MasterTheme\Post;
use P4\MasterTheme\ListingPage;
use Timber\Timber;

$context = Timber::context();

if (isset($wp_query->query_vars['author'])) {
    $author = Timber::get_user($wp_query->query_vars['author']);
    $context['author'] = $author;
    $context['title'] = 'Author Archives: ' . $author->name();

    $context['page_category'] = 'Author Page';

    $context['social_accounts'] = Post::filter_social_accounts(
        $context['footer_social_menu'] ?: []
    );
    $context['og_title'] = $author->name;
    $context['og_description'] = get_the_author_meta('description', $author->ID);
    $context['og_image_data'] = [
        'url' => get_avatar_url($author->ID, [ 'size' => 300 ]),
        'width' => '300',
        'height' => '300',
    ];
}

$templates = ['author.twig', 'archive.twig'];
$page = new ListingPage($templates, $context);
