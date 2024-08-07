<?php

declare(strict_types=1);

namespace P4\MasterTheme;

class TimberSettings
{
    public static function hooks(): void
    {
        add_filter('timber/post/classmap', function ($classmap) {
            $custom_classmap = [
                'page' => Post::class,
                'post' => Post::class,
                //'tag' => Post::class,
                'campaign' => Post::class,
                'p4-page-type' => Post::class,
            ];

            return array_merge($classmap, $custom_classmap);
        });

        add_filter('timber/user/class', fn() => User::class);

        add_filter('timber/twig/environment/options', static function (array $options): array {
            $options['autoescape'] = 'html';
            $options['cache'] = defined('WP_DEBUG') ? !WP_DEBUG : true;
            return $options;
        });

        add_filter('timber/locations', static function (array $locations): array {
            $locations['enform'] = [
                dirname(__DIR__) . '/templates/block_templates',
                dirname(__DIR__) . '/templates/block_templates/enform',
            ];
            return $locations;
        });
    }
}
