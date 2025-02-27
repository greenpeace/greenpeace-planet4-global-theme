<?php

namespace P4\MasterTheme;

use P4\MasterTheme\Settings\Features;

/**
 * Class BlockSettings.
 *
 * This class is used to handle blocks configuration.
 */
class BlockSettings
{
    private const ALL_BLOCKS_FEATURE = 'allow_all_blocks';

    private const CORE_BLOCKS_PREFIX = "core";

    private const CORE_EMBED_BLOCKS_PREFIX = "core-embed";

    private const P4_BLOCKS_PREFIX = "planet4-blocks";

    private const P4_TEMPLATES_PREFIX = "planet4-block-templates";

    private const HUBSPOT_FORMS_BLOCK = 'leadin/hubspot-form-block';

    private const GRAVITY_FORMS_BLOCK = 'gravityforms/form';

    private const POST_BLOCK_TYPES = [
        self::P4_BLOCKS_PREFIX . '/accordion',
        self::P4_BLOCKS_PREFIX . '/articles',
        self::P4_BLOCKS_PREFIX . '/counter',
        self::P4_BLOCKS_PREFIX . '/gallery',
        self::P4_BLOCKS_PREFIX . '/social-media',
        self::P4_BLOCKS_PREFIX . '/spreadsheet',
        self::P4_BLOCKS_PREFIX . '/take-action-boxout',
        self::P4_BLOCKS_PREFIX . '/timeline',
        self::P4_BLOCKS_PREFIX . '/topic-link',
        self::HUBSPOT_FORMS_BLOCK,
        self::GRAVITY_FORMS_BLOCK,
    ];

    private const PAGE_BLOCK_TYPES = [
        self::P4_BLOCKS_PREFIX . '/accordion',
        self::P4_BLOCKS_PREFIX . '/articles',
        self::P4_BLOCKS_PREFIX . '/carousel-header',
        self::P4_BLOCKS_PREFIX . '/columns',
        self::P4_BLOCKS_PREFIX . '/cookies',
        self::P4_BLOCKS_PREFIX . '/counter',
        self::P4_BLOCKS_PREFIX . '/covers',
        self::P4_BLOCKS_PREFIX . '/gallery',
        self::P4_BLOCKS_PREFIX . '/happypoint',
        self::P4_BLOCKS_PREFIX . '/social-media',
        self::P4_BLOCKS_PREFIX . '/spreadsheet',
        self::P4_BLOCKS_PREFIX . '/submenu',
        self::P4_BLOCKS_PREFIX . '/take-action-boxout',
        self::P4_BLOCKS_PREFIX . '/timeline',
        self::P4_BLOCKS_PREFIX . '/guestbook',
        self::P4_BLOCKS_PREFIX . '/secondary-navigation',
        self::HUBSPOT_FORMS_BLOCK,
        self::GRAVITY_FORMS_BLOCK,
    ];

    private const CAMPAIGN_BLOCK_TYPES = [
        self::P4_BLOCKS_PREFIX . '/accordion',
        self::P4_BLOCKS_PREFIX . '/articles',
        self::P4_BLOCKS_PREFIX . '/carousel-header',
        self::P4_BLOCKS_PREFIX . '/columns',
        self::P4_BLOCKS_PREFIX . '/cookies',
        self::P4_BLOCKS_PREFIX . '/counter',
        self::P4_BLOCKS_PREFIX . '/covers',
        self::P4_BLOCKS_PREFIX . '/gallery',
        self::P4_BLOCKS_PREFIX . '/happypoint',
        self::P4_BLOCKS_PREFIX . '/social-media',
        self::P4_BLOCKS_PREFIX . '/spreadsheet',
        self::P4_BLOCKS_PREFIX . '/timeline',
        self::P4_BLOCKS_PREFIX . '/guestbook',
        self::HUBSPOT_FORMS_BLOCK,
        self::GRAVITY_FORMS_BLOCK,
    ];

    private const ACTION_BLOCK_TYPES = [
        self::P4_BLOCKS_PREFIX . '/accordion',
        self::P4_BLOCKS_PREFIX . '/articles',
        self::P4_BLOCKS_PREFIX . '/carousel-header',
        self::P4_BLOCKS_PREFIX . '/columns',
        self::P4_BLOCKS_PREFIX . '/cookies',
        self::P4_BLOCKS_PREFIX . '/counter',
        self::P4_BLOCKS_PREFIX . '/covers',
        self::P4_BLOCKS_PREFIX . '/gallery',
        self::P4_BLOCKS_PREFIX . '/happypoint',
        self::P4_BLOCKS_PREFIX . '/social-media',
        self::P4_BLOCKS_PREFIX . '/spreadsheet',
        self::P4_BLOCKS_PREFIX . '/submenu',
        self::P4_BLOCKS_PREFIX . '/take-action-boxout',
        self::P4_BLOCKS_PREFIX . '/timeline',
        self::P4_BLOCKS_PREFIX . '/guestbook',
        self::P4_BLOCKS_PREFIX . '/secondary-navigation',
        self::HUBSPOT_FORMS_BLOCK,
        self::GRAVITY_FORMS_BLOCK,
    ];

    private const BLOCK_TEMPLATES = [
        self::P4_TEMPLATES_PREFIX . '/deep-dive',
        self::P4_TEMPLATES_PREFIX . '/highlighted-cta',
        self::P4_TEMPLATES_PREFIX . '/quick-links',
        self::P4_TEMPLATES_PREFIX . '/reality-check',
        self::P4_TEMPLATES_PREFIX . '/issues',
        self::P4_TEMPLATES_PREFIX . '/page-header',
        self::P4_TEMPLATES_PREFIX . '/side-image-with-text-and-cta',

        // layouts.
        self::P4_TEMPLATES_PREFIX . '/deep-dive-topic',
        self::P4_TEMPLATES_PREFIX . '/homepage',
        self::P4_TEMPLATES_PREFIX . '/campaign',
        self::P4_TEMPLATES_PREFIX . '/take-action',
        self::P4_TEMPLATES_PREFIX . '/action',
        self::P4_TEMPLATES_PREFIX . '/get-informed',
        self::P4_TEMPLATES_PREFIX . '/high-level-topic',
    ];

    // https://github.com/WordPress/gutenberg/blob/trunk/lib/blocks.php.
    private const WORDPRESS_BLOCKS = [
        self::CORE_BLOCKS_PREFIX . '/audio',
        self::CORE_BLOCKS_PREFIX . '/video',
        self::CORE_BLOCKS_PREFIX . '/block',
        self::CORE_BLOCKS_PREFIX . '/paragraph',
        self::CORE_BLOCKS_PREFIX . '/heading',
        self::CORE_BLOCKS_PREFIX . '/image',
        self::CORE_BLOCKS_PREFIX . '/list',
        self::CORE_BLOCKS_PREFIX . '/list-item',
        self::CORE_BLOCKS_PREFIX . '/quote',
        self::CORE_BLOCKS_PREFIX . '/file',
        self::CORE_BLOCKS_PREFIX . '/html',
        self::CORE_BLOCKS_PREFIX . '/table',
        self::CORE_BLOCKS_PREFIX . '/buttons',
        self::CORE_BLOCKS_PREFIX . '/button',
        self::CORE_BLOCKS_PREFIX . '/separator',
        self::CORE_BLOCKS_PREFIX . '/spacer',
        self::CORE_BLOCKS_PREFIX . '/shortcode',
        self::CORE_BLOCKS_PREFIX . '/group',
        self::CORE_BLOCKS_PREFIX . '/columns',
        self::CORE_BLOCKS_PREFIX . '/column',
        self::CORE_BLOCKS_PREFIX . '/embed',
        self::CORE_BLOCKS_PREFIX . '/media-text',
        self::CORE_EMBED_BLOCKS_PREFIX . '/twitter',
        self::CORE_EMBED_BLOCKS_PREFIX . '/youtube',
        self::CORE_EMBED_BLOCKS_PREFIX . '/facebook',
        self::CORE_EMBED_BLOCKS_PREFIX . '/instagram',
        self::CORE_EMBED_BLOCKS_PREFIX . '/wordpress',
        self::CORE_EMBED_BLOCKS_PREFIX . '/soundcloud',
        self::CORE_EMBED_BLOCKS_PREFIX . '/spotify',
        self::CORE_EMBED_BLOCKS_PREFIX . '/flickr',
        self::CORE_EMBED_BLOCKS_PREFIX . '/vimeo',
        self::CORE_EMBED_BLOCKS_PREFIX . '/dailymotion',
        self::CORE_EMBED_BLOCKS_PREFIX . '/funnyordie',
        self::CORE_EMBED_BLOCKS_PREFIX . '/imgur',
        self::CORE_EMBED_BLOCKS_PREFIX . '/issuu',
        self::CORE_EMBED_BLOCKS_PREFIX . '/kickstarter',
        self::CORE_EMBED_BLOCKS_PREFIX . '/meetup-com',
        self::CORE_EMBED_BLOCKS_PREFIX . '/mixcloud',
        self::CORE_EMBED_BLOCKS_PREFIX . '/photobucket',
        self::CORE_EMBED_BLOCKS_PREFIX . '/polldaddy',
        self::CORE_EMBED_BLOCKS_PREFIX . '/reddit',
        self::CORE_EMBED_BLOCKS_PREFIX . '/scribd',
        self::CORE_EMBED_BLOCKS_PREFIX . '/slideshare',
        self::CORE_EMBED_BLOCKS_PREFIX . '/speaker',
        self::CORE_EMBED_BLOCKS_PREFIX . '/ted',
        self::CORE_EMBED_BLOCKS_PREFIX . '/videopress',
    ];

    /**
     * Activator constructor.
     */
    public function __construct()
    {
        add_filter('allowed_block_types_all', [$this, 'set_allowed_block_types'], 10, 2);
    }

    /**
     * Allowed block types based on post type
     *
     * @param array|bool  $allowed_block_types array of allowed block types.
     * @param object $context Current editor context.
     *
     * @return array|bool Array with allowed types, or true if all blocks are allowed.
     */
    // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
    public function set_allowed_block_types(array|bool $allowed_block_types, object $context)
    {
        if (Features::is_active(self::ALL_BLOCKS_FEATURE)) {
            return true;
        }
        $post_type = $context->post ? $context->post->post_type : null;

        $page_block_types = array_merge(
            self::PAGE_BLOCK_TYPES,
            self::BLOCK_TEMPLATES,
        );

        $campaign_block_types = array_merge(
            self::CAMPAIGN_BLOCK_TYPES,
            self::BLOCK_TEMPLATES,
        );

        $action_block_types = array_merge(
            self::ACTION_BLOCK_TYPES,
            self::BLOCK_TEMPLATES,
        );

        $all_allowed_p4_block_types = [
            'post' => self::POST_BLOCK_TYPES,
            'page' => $page_block_types,
            'campaign' => $campaign_block_types,
            'p4_action' => $action_block_types,
        ];

        $allowed_p4_block_types = $all_allowed_p4_block_types[ $post_type ] ?? $all_allowed_p4_block_types['page'];

        return array_merge(self::WORDPRESS_BLOCKS, $allowed_p4_block_types);
    }
}
