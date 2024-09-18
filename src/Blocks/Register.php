<?php

declare(strict_types=1);

namespace P4\MasterTheme\Blocks;

class Register
{
    /**
     * Requires a file `block.json`
     * in assets/(src|build)/blocks/{blockDirName}/
     */
    public static function registerFromAssets(
        string $blockDirName,
        array $properties = []
    ): void {
        register_block_type_from_metadata(
            get_template_directory() . '/assets/build/blocks/' . $blockDirName,
            $properties
        );
    }
}
