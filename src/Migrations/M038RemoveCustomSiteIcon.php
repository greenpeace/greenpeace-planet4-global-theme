<?php

namespace P4\MasterTheme\Migrations;

use P4\MasterTheme\MigrationRecord;
use P4\MasterTheme\MigrationScript;

/**
 * Remove custom site icon, since we disabled the option to customize it.
 */
class M038RemoveCustomSiteIcon extends MigrationScript
{
    /**
     * Perform the actual migration.
     *
     * @param MigrationRecord $record Information on the execution, can be used to add logs.
     * phpcs:disable SlevomatCodingStandard.Functions.UnusedParameter -- interface implementation
     */
    protected static function execute(MigrationRecord $record): void
    {
        if (!has_site_icon()) {
            return;
        }
        update_option('site_icon', '');
    }
    // phpcs:enable SlevomatCodingStandard.Functions.UnusedParameter
}
