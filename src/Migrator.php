<?php

namespace P4\MasterTheme;

use P4\MasterTheme\Migrations\M001EnableEnFormFeature;
use P4\MasterTheme\Migrations\M002EnableLazyYoutube;
use P4\MasterTheme\Migrations\M004UpdateMissingMediaPath;
use P4\MasterTheme\Migrations\M003UpdateArticlesBlockAttribute;
use P4\MasterTheme\Migrations\M005TurnBoxoutSettingIntoBlock;
use P4\MasterTheme\Migrations\M006MoveFeaturesToSeparateOption;
use P4\MasterTheme\Migrations\M007RemoveEnhancedDonateButtonOption;
use P4\MasterTheme\Migrations\M008RemoveArticlesDefaultOptions;
use P4\MasterTheme\Migrations\M009PopulateCookiesFields;
use P4\MasterTheme\Migrations\M010RemoveGdprPluginOptions;
use P4\MasterTheme\Migrations\M011RemoveSmartsheetOption;
use P4\MasterTheme\Migrations\M012RemoveThemeEditorOption;
use P4\MasterTheme\Migrations\M013RemoveDuplicatedOptions;
use P4\MasterTheme\Migrations\M014RemoveDropdownNavigationMenusOption;
use P4\MasterTheme\Migrations\M015RemoveListingPagesBackgroundImage;
use P4\MasterTheme\Migrations\M016CreateDefaultActionType;
use P4\MasterTheme\Migrations\M017NewIAToggle;
use P4\MasterTheme\Migrations\M018MigrateDonateButtonSetting;
use P4\MasterTheme\Migrations\M019MigrateReadingTime;
use P4\MasterTheme\Migrations\M020MigrateCommentsSettings;
use P4\MasterTheme\Migrations\M021MigrateDefaultPostType;
use P4\MasterTheme\Migrations\M022UpdatePostRevisions;
use P4\MasterTheme\Migrations\M023EnablePlanet4Blocks;
use P4\MasterTheme\Migrations\M024RemoveNewIdentitySylesOption;
use P4\MasterTheme\Migrations\M025CreateDefaultPostsPage;
use P4\MasterTheme\Migrations\M026ReplaceDeprecatedColorsFromContent;
use P4\MasterTheme\Migrations\M027RemoveListingPageGridViewOption;
use P4\MasterTheme\Migrations\M028MovePageHeaderSideBarOptions;
use P4\MasterTheme\Migrations\M029RemoveTemplateEditorOption;
use P4\MasterTheme\Migrations\M030RemovePurgeOnFeatureChangeOption;
use P4\MasterTheme\Migrations\M031MigrateMediaBlockToAudioVideoBlock;
use P4\MasterTheme\Migrations\M032MigrateSplit2ColumnBlock;
use P4\MasterTheme\Migrations\M033MigrateSocialMediaTwitterBlockToEmbedBlock;
use P4\MasterTheme\Migrations\M034PrePopulateOldPostsArchiveNotice;
use P4\MasterTheme\Migrations\M035MigrateCampaignCoversToP4ColumnsBlock;
use P4\MasterTheme\Migrations\M036RemoveEnFormOptions;
use P4\MasterTheme\Migrations\M037MigrateCoversContentBlockToPostsListBlock;
use P4\MasterTheme\Migrations\M038RemoveCustomSiteIcon;

/**
 * Run any new migration scripts and record results in the log.
 */
class Migrator
{
    /**
     * Run any new migration scripts and record results in the log.
     */
    public static function migrate(): void
    {

        // Fetch migration script ids that have run from WP option.
        $log = MigrationLog::from_wp_options();

        /**
         * @var MigrationScript[] $scripts
         */
        $scripts = [
            M001EnableEnFormFeature::class,
            M002EnableLazyYoutube::class,
            M004UpdateMissingMediaPath::class,
            M003UpdateArticlesBlockAttribute::class,
            M005TurnBoxoutSettingIntoBlock::class,
            M006MoveFeaturesToSeparateOption::class,
            M007RemoveEnhancedDonateButtonOption::class,
            M008RemoveArticlesDefaultOptions::class,
            M009PopulateCookiesFields::class,
            M010RemoveGdprPluginOptions::class,
            M011RemoveSmartsheetOption::class,
            M012RemoveThemeEditorOption::class,
            M013RemoveDuplicatedOptions::class,
            M014RemoveDropdownNavigationMenusOption::class,
            M015RemoveListingPagesBackgroundImage::class,
            M016CreateDefaultActionType::class,
            M017NewIAToggle::class,
            M018MigrateDonateButtonSetting::class,
            M019MigrateReadingTime::class,
            M020MigrateCommentsSettings::class,
            M021MigrateDefaultPostType::class,
            M022UpdatePostRevisions::class,
            M023EnablePlanet4Blocks::class,
            M024RemoveNewIdentitySylesOption::class,
            M025CreateDefaultPostsPage::class,
            M026ReplaceDeprecatedColorsFromContent::class,
            M027RemoveListingPageGridViewOption::class,
            M028MovePageHeaderSideBarOptions::class,
            M029RemoveTemplateEditorOption::class,
            M030RemovePurgeOnFeatureChangeOption::class,
            M031MigrateMediaBlockToAudioVideoBlock::class,
            M032MigrateSplit2ColumnBlock::class,
            M033MigrateSocialMediaTwitterBlockToEmbedBlock::class,
            M034PrePopulateOldPostsArchiveNotice::class,
            M035MigrateCampaignCoversToP4ColumnsBlock::class,
            M036RemoveEnFormOptions::class,
            M037MigrateCoversContentBlockToPostsListBlock::class,
            M038RemoveCustomSiteIcon::class,
        ];

        // Loop migrations and run those that haven't run yet.
        foreach ($scripts as $script) {
            if ($log->already_ran($script::get_id())) {
                continue;
            }

            $record = $script::run();
            $log->add($record);
        }

        $log->persist();
    }
}
