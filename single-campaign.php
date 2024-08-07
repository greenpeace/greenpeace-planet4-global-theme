<?php

/**
 * Template Variables for Campaigns.
 *
 * @package P4MT
 */

use P4\MasterTheme\Context;
use Timber\Timber;

// Initializing variables.
$context = Timber::context();

/**
 * Post object.
 *
 * @var Post $post
 * */
$post = Timber::query_post(false); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
$context['post'] = $post;

$meta = get_post_meta($post->ID);
// No need to check the user here as that already happens in wp_get_post_autosave.
if (isset($_GET['preview'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $post_preview = wp_get_post_autosave($post->ID);
    if ($post_preview) {
        $meta = array_merge($meta, get_post_meta($post_preview->ID));
    }
}
$meta = array_map(fn ($v) => reset($v), $meta);

$current_level_campaign_id = $post->ID;

do {
    $top_level_campaign_id = $current_level_campaign_id;
    $current_level_campaign_id = wp_get_post_parent_id($current_level_campaign_id);
} while ($current_level_campaign_id);

if ($top_level_campaign_id === $post->ID) {
    $campaign_meta = $meta;
} else {
    $campaign_meta = get_post_meta($top_level_campaign_id);
    $campaign_meta = array_map(fn ($v) => reset($v), $campaign_meta);
}

// This is just an example of how to get children pages, this will probably be done in some kind of menu block.
$sub_pages = get_children(
    [
        'post_parent' => $post->ID,
        'post_type' => 'campaign',
    ]
);

$context['$sub_pages'] = array_map(
    static function ($page) {
        return [
            'link' => get_permalink($page->ID),
            'title' => $page->post_title,
        ];
    },
    $sub_pages
);


$theme_name = $campaign_meta['theme'] ?? $campaign_meta['_campaign_page_template'] ?? null;

if ($theme_name) {
    $context['custom_body_classes'] = 'white-bg theme-' . $theme_name;
}

// Set GTM Data Layer values.
$post->set_data_layer();
$data_layer = $post->get_data_layer();

Context::set_header($context, $meta, $post->title);
Context::set_background_image($context);
Context::set_og_meta_fields($context, $post);
Context::set_campaign_datalayer($context, $campaign_meta);
Context::set_utm_params($context, $post);
Context::set_custom_styles($context, $campaign_meta, 'campaign');

$context['post'] = $post;
$context['social_accounts'] = $post->get_social_accounts($context['footer_social_menu'] ?: []);
$context['page_category'] = $data_layer['page_category'];

$context['custom_font_families'] = [
    'Montserrat:300,400,500,600,700,800',
    'Kanit:400,500,600,800',
    'Open+Sans:400,500,600,800',
    'Anton:400,500,600,800',
    'Amiko:400,500,600,800',
];

// Social footer link overrides.
$context['social_overrides'] = [];

foreach (range(1, 5) as $i) {
    $footer_item_key = 'campaign_footer_item' . $i;

    if (!isset($campaign_meta[ $footer_item_key ])) {
        continue;
    }

    $campaign_footer_item = maybe_unserialize($campaign_meta[ $footer_item_key ]);
    if (!$campaign_footer_item['url'] || !$campaign_footer_item['icon']) {
        continue;
    }

    $context['social_overrides'][ $i ]['url'] = $campaign_footer_item['url'];
    $context['social_overrides'][ $i ]['icon'] = $campaign_footer_item['icon'];
}

Context::set_p4_blocks_datalayer($context, $post);

if (post_password_required($post->ID)) {
    // Password protected form validation.
    $context['is_password_valid'] = $post->is_password_valid();

    // Hide the campaign title from links to the extra feeds.
    remove_action('wp_head', 'feed_links_extra', 3);

    $context['login_url'] = wp_login_url();

    Timber::render('single-password.twig', $context);
} else {
    Timber::render(
        ['single-' . $post->ID . '.twig', 'single-' . $post->post_type . '.twig', 'single.twig' ],
        $context
    );
}
