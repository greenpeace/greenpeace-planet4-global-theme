<?php

namespace P4\MasterTheme;

/**
 * Class Context Sets common context fields.
 */
class Context
{
    /**
     * Set context relating to the header
     *
     * @param array  $context To be set.
     * @param array  $page_meta_data  meta data of page.
     * @param string $post_title the title of the post.
     */
    public static function set_header(array &$context, array $page_meta_data, string $post_title): void
    {
        $meta_data_title = $page_meta_data['p4_title'] ?? '';
        $post_title_to_show = $meta_data_title ? $meta_data_title : $post_title;

        $context['header_title'] = is_front_page() ? $meta_data_title : $post_title_to_show;
        $context['header_subtitle'] = $page_meta_data['p4_subtitle'] ?? '';
        $context['header_description'] = wpautop($page_meta_data['p4_description'] ?? '');
        $context['header_button_title'] = $page_meta_data['p4_button_title'] ?? '';
        $context['header_button_link'] = $page_meta_data['p4_button_link'] ?? '';
        $context['header_button_link_checkbox'] = $page_meta_data['p4_button_link_checkbox'] ?? '';
        $context['hide_page_title'] = 'on' === ( $page_meta_data['p4_hide_page_title_checkbox'] ?? null )
            || (has_block('post-title') && !has_block('query'));
    }

    /**
     * Set context fileds relating to the background image.
     *
     * @param array $context To be set.
     */
    public static function set_background_image(array &$context): void
    {
        $background_image_id = get_post_meta(get_the_ID(), 'background_image_id', 1);
        $context['background_image'] = wp_get_attachment_url($background_image_id);
        $context['background_image_srcset'] = wp_get_attachment_image_srcset($background_image_id, 'full');
    }

    /**
     * Set open graph context fields.
     *
     * @param array  $context To be set.
     * @param object $post That the context refers to.
     */
    public static function set_og_meta_fields(array &$context, object $post): void
    {
        $context['og_title'] = $post->get_og_title();
        $context['og_description'] = $post->get_og_description();
        $context['og_image_data'] = $post->get_og_image();
    }

    /**
     * Set the context fields relating to the data layer.
     *
     * @param array $context Context to be set.
     * @param array $meta Meta data of the page.
     */
    public static function set_campaign_datalayer(array &$context, array $meta): void
    {
        $context['cf_campaign_name'] = $meta['p4_campaign_name'] ?? '';
        $context['cf_basket_name'] = $meta['p4_basket_name'] ?? '';
        $context['cf_department'] = $meta['p4_department'] ?? '';
        $context['cf_project_id'] = $meta['p4_global_project_tracking_id'] ?? 'not set';
        $context['cf_local_project'] = $meta['p4_local_project'] ?? 'not set';
        $context['cf_scope'] = self::get_campaign_scope($context['cf_campaign_name']);
    }

    /**
     * Set the context fields relating to UTM.
     *
     * @param array  $context Context to be set.
     * @param object $post That the context refers to.
     */
    public static function set_utm_params(array &$context, object $post): void
    {
        $context['utm_campaign_param'] = self::parse_utm_campaign_param($context['cf_local_project']);
        $context['utm_content_param'] = '&utm_content=postid-' . $post->id;
    }

    /**
     * Parse the utm_campaign param. It's not needed to add if the value is equal to `not set`.
     *
     * @param string $cf_local_project It comes from meta p4_global_project_tracking_id value.
     */
    public static function parse_utm_campaign_param(string $cf_local_project): string
    {
        if ('not set' !== $cf_local_project) {
            return '&utm_campaign=' . $cf_local_project;
        }
        return '';
    }

    /**
     * Set p4_blocks datalayer value
     *
     * @param array  $context Context to be set.
     * @param object $post That the context refers to.
     */
    public static function set_p4_blocks_datalayer(array &$context, object $post): void
    {
        $post_content = $post->post_content;

        if (isset($post->articles)) {
            $post_content .= $post->articles;
        }

        if (isset($post->take_action_boxout)) {
            $post_content .= $post->take_action_boxout;
        }

        preg_match_all('/wp:planet4-blocks\/(\S+)|wp:gravityforms\/(\S+)*/', $post_content ?? '', $matches);

        $p4_blocks = array_map(
            function ($block) {
                if (str_contains($block, 'gravityforms')) {
                    $start = stripos($block, ':');
                    $end = stripos($block, '/');
                    return substr($block, $start + 1, $end - strlen($block));
                }

                return substr($block, (stripos($block, '/') + 1) - strlen($block));
            },
            array_unique($matches[0])
        );
        $context['p4_blocks'] = implode(', ', $p4_blocks);
    }

    /**
     * Set reading_time datalayer value
     * Requires milliseconds
     */
    public static function set_reading_time_datalayer(array &$context, object $post): void
    {
        $rt = $post->reading_time();
        if ($rt === null) {
            return;
        }

        $context['reading_time'] = $rt * 1000;
    }

    /**
     * Get campaign scope from value selected in the Global Projects dropdown.
     * Conditions:
     * - If Global Project equals "Local Campaign" then Scope is Local.
     * - If Global Project equals none then Scope is not set
     * - If Global Project matches any other value (apart from "Local Campaign") then Scope is Global
     *
     * @param string $global_project The Global Project value.
     * @return string The campaign scope.
     */
    private static function get_campaign_scope(string $global_project): string
    {
        switch ($global_project) {
            case 'Local Campaign':
                return 'Local';
            case 'not set':
                return 'not set';
            default:
                return 'Global';
        }
    }

    /**
     * @param array       $context   Context to be set.
     * @param array       $meta      Meta data.
     * @param string|null $post_type Post type.
     */
    public static function set_custom_styles(
        array &$context,
        array $meta,
        ?string $post_type = null
    ): void {
        if ('campaign' === $post_type) {
            $custom_styles = [
                'nav_type' => $meta['campaign_nav_type'] ?? null,
            ];

            $context['custom_styles'] = $custom_styles;
            return;
        }

        $context['custom_styles'] = [
            'nav_type' => $meta['nav_type'] ?? 'planet4',
        ];
    }
}
