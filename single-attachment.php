<?php

/**
 * The Template for displaying all attachment posts
 *
 * Methods for \Timber\Helper can be found in the /lib sub-directory
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since    Timber 0.1
 */

use P4\MasterTheme\Post;
use Timber\Timber;

// Initializing variables.
$context = Timber::context();
/**
 * P4 Post Object
 *
 * @var Post $post
 */
$post = Timber::get_post(false); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
$context['post'] = $post;
$context['social_accounts'] = Post::filter_social_accounts($context['footer_social_menu'] ?: []);

Timber::render([ 'single-' . $post->ID . '.twig', 'single-' . $post->post_type . '.twig', 'single.twig' ], $context);
