<?php

/**
 * Template Name: Evergreen Page
 * The template for displaying evergreen pages.
 *
 * To generate specific templates for your pages you can use:
 * /mytheme/views/page-mypage.twig
 * (which will still route through this PHP file)
 * OR
 * /mytheme/page-mypage.php
 * (in which case you'll want to duplicate this file and save to the above path)
 *
 * Methods for \Timber\Helper can be found in the /lib sub-directory
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since    Timber 0.1
 */

use P4\MasterTheme\Context;
use Timber\Timber;

$context = Timber::context();
$post = Timber::get_post(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
$page_meta_data = get_post_meta($post->ID);
$page_meta_data = array_map(fn ($v) => reset($v), $page_meta_data);

// Set Navigation Issues links.
$post->set_issues_links();

// Get Navigation Campaigns links.
$page_tags = wp_get_post_tags($post->ID);
$tags = [];

if (is_array($page_tags) && $page_tags) {
    foreach ($page_tags as $page_tag) {
        $tags[] = [
            'name' => $page_tag->name,
            'link' => get_tag_link($page_tag),
        ];
    }
    $context['campaigns'] = $tags;
}

$context['post'] = $post;
$context['custom_body_classes'] = 'white-bg';
$context['page_category'] = 'Evergreen Page';
$context['social_accounts'] = $post->get_social_accounts($context['footer_social_menu'] ?: []);

Context::set_header($context, $page_meta_data, $post->title);
Context::set_background_image($context);
Context::set_og_meta_fields($context, $post);
Context::set_p4_blocks_datalayer($context, $post);

if (post_password_required($post->ID)) {
    $context['login_url'] = wp_login_url();

    Timber::render('single-page.twig', $context);
} else {
    Timber::render([ 'evergreen.twig' ], $context);
}
