<?php

/**
 * The template for displaying 404 pages (Not Found)
 *
 * Methods for \Timber\Helper can be found in the /functions sub-directory
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since    Timber 0.1
 */

$context = Timber::context();

$options = get_option('planet4_options');
$context['page_notfound_image'] = $options['404_page_bg_image']
    ?? esc_url(get_template_directory_uri() . '/images/404-header.jpg');
$context['page_notfound_text'] = $options['404_page_text']
    ?? __('Sorry, we can\'t find that page!', 'planet4-master-theme');
$context['page_notfound_help'] = __('Enter your search term below', 'planet4-master-theme');
$context['page_category'] = __('404 Page', 'planet4-master-theme');
$context['custom_body_classes'] = 'brown-bg page-404-page';

Timber::render('404.twig', $context);
