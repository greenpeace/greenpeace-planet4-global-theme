<?php

/**
 * The Template for displaying all attachment posts
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since    Timber 0.1
 */

use P4\MasterTheme\Post;
use Timber\Timber;

// Initializing variables.
$context = Timber::get_context();
/**
 * P4 Post Object
 *
 * @var Post $post
 */
$post = Timber::query_post(false, Post::class); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
$context['post'] = $post;
$context['social_accounts'] = Post::filter_social_accounts($context['footer_social_menu'] ?: []);

do_action('enqueue_google_tag_manager_script', $context);
Timber::render([ 'single-' . $post->ID . '.twig', 'single-' . $post->post_type . '.twig', 'single.twig' ], $context);
