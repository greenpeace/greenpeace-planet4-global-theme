<?php

/**
 * Template Name: Sitemap Page
 * The template for displaying the Sitemap page.
 *
 * @package P4MT
 */

use P4\MasterTheme\Sitemap;
use Timber\Timber;

$context = Timber::context();
$post = Timber::get_post(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
$page_meta_data = get_post_meta($post->ID);

$context['post'] = $post;
$context['header_title'] = is_front_page() ? ''
    : (!empty($page_meta_data['p4_title'][0]) ? $page_meta_data['p4_title'][0] : $post->title);
$context['background_image'] = wp_get_attachment_url(get_post_meta(get_the_ID(), 'background_image_id', 1));
$context['custom_body_classes'] = 'white-bg';
$context['page_category'] = 'Sitemap Page';

if (!empty(planet4_get_option('new_ia'))) {
    $context['categories'] = get_categories(['orderby' => 'name', 'order' => 'ASC']);
    $context['posts'] = [];
    foreach ($context['categories'] as $cat) {
        if ($cat->slug === 'uncategorized') {
            continue;
        }
        $context['posts'][$cat->term_id] = get_posts([
            'post_type' => 'page',
            'category' => $cat->term_id,
            'orderby' => 'title',
            'order' => 'ASC',
        ]);
    }
} else {
    $sitemap = new Sitemap();

    $context['actions_title'] = __('Act', 'planet4-master-theme');
    $context['issues_title'] = __('Explore', 'planet4-master-theme');
    $context['evergreen_title'] = __('About Greenpeace', 'planet4-master-theme');
    $context['page_types_title'] = __('Articles', 'planet4-master-theme');

    $context['actions'] = $sitemap->get_actions();
    $context['issues'] = $sitemap->get_issues();
    $context['evergreen_pages'] = $sitemap->get_evergreen_pages();
    $context['page_types'] = $sitemap->get_page_types();
}

Timber::render(['sitemap.twig'], $context);
