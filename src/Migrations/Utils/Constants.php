<?php

namespace P4\MasterTheme\Migrations\Utils;

/**
 * Set of constant to be used by the migration scripts.
 */
class Constants
{
    private const PREFIX_P4_BLOCKS = 'planet4-blocks';
    private const PREFIX_CORE_BLOCKS = 'core';

    public const BLOCK_MEDIA_VIDEO = self::PREFIX_P4_BLOCKS . '/media-video';
    public const BLOCK_SPLIT_TWO_COLUMNS = self::PREFIX_P4_BLOCKS . '/split-two-columns';
    public const BLOCK_COVERS = self::PREFIX_P4_BLOCKS . '/covers';
    public const BLOCK_P4_COLUMNS = self::PREFIX_P4_BLOCKS . '/columns';


    public const BLOCK_EMBED = self::PREFIX_CORE_BLOCKS . '/embed';
    public const BLOCK_AUDIO = self::PREFIX_CORE_BLOCKS . '/audio';
    public const BLOCK_VIDEO = self::PREFIX_CORE_BLOCKS . '/video';
    public const BLOCK_GROUP = self::PREFIX_CORE_BLOCKS . '/group';
    public const BLOCK_HEADING = self::PREFIX_CORE_BLOCKS . '/heading';
    public const BLOCK_PARAGRAPH = self::PREFIX_CORE_BLOCKS . '/paragraph';
    public const BLOCK_SINGLE_COLUMN = self::PREFIX_CORE_BLOCKS . '/column';
    public const BLOCK_CORE_COLUMNS = self::PREFIX_CORE_BLOCKS . '/columns';
    public const BLOCK_SINGLE_BUTTON = self::PREFIX_CORE_BLOCKS . '/button';
    public const BLOCK_BUTTONS = self::PREFIX_CORE_BLOCKS . '/buttons';
    public const BLOCK_MEDIA_TEXT = self::PREFIX_CORE_BLOCKS . '/media-text';

    public const POST_TYPES_PAGE = 'page';
    public const POST_TYPES_POST = 'post';
    public const POST_TYPES_ACTION = 'action';
    public const POST_TYPES_CAMPAIGN = 'campaign';

    public const ALL_POST_TYPES = [
        self::POST_TYPES_PAGE,
        self::POST_TYPES_POST,
        self::POST_TYPES_ACTION,
        self::POST_TYPES_CAMPAIGN,
    ];

    public const POST_STATUS_LIST = [
        'publish',
        'pending',
        'draft',
        'future',
        'private',
    ];

    public const COVER_TYPE_TAKE_ACTION = 'take-action';
    public const COVER_TYPE_CAMPAIGN = 'campaign';
    public const COVER_TYPE_CONTENT = 'content';

    public const OLD_COVER_TYPES = [
        '1' => self::COVER_TYPE_TAKE_ACTION,
        '2' => self::COVER_TYPE_CAMPAIGN,
        '3' => self::COVER_TYPE_CONTENT,
    ];
}
