<?php

namespace P4\MasterTheme;

use P4\MasterTheme\Settings\Features;
use P4\MasterTheme\Features\Planet4Blocks;
use P4\MasterTheme\Patterns\BlockPattern;
use P4\MasterTheme\View\View;
use RuntimeException;

/**
 * Class Loader.
 * Loads all necessary classes for Planet4 Master Theme.
 */
final class Loader
{
    /**
     * A static instance of Loader.
     *
     */
    private static Loader $instance;
    /**
     * Indexed array of all the classes/services that are needed.
     *
     */
    private array $services;
    /**
     * Indexed array of all the classes/services that are used by Planet4.
     *
     */
    private array $default_services;

    /**
     * Singleton creational pattern.
     * Makes sure there is only one instance at all times.
     *
     * @param array $services The Controller services to inject.
     *
     */
    public static function get_instance(array $services = []): Loader
    {
        if (!isset(self::$instance)) {
            self::$instance = new self($services);
        }
        return self::$instance;
    }

    /**
     * Loader constructor.
     *
     * @param array $services The dependencies to inject.
     */
    private function __construct(array $services)
    {
        $this->load_services($services);
        $this->load_block_services();
        Commands::load();

        add_action('init', [self::class, 'add_blocks'], 20);

        // Load parallax library for Media & Text block.
        add_action(
            'wp_enqueue_scripts',
            static function (): void {
                wp_enqueue_script(
                    'rellax',
                    'https://cdnjs.cloudflare.com/ajax/libs/rellax/1.12.1/rellax.min.js',
                    [],
                    '1.12.1',
                    true
                );
            }
        );
    }

    /**
     * Inject dependencies.
     *
     * @param array $services The dependencies to inject.
     */
    private function load_services(array $services): void
    {
        $this->default_services = [
            CustomTaxonomy::class,
            PostCampaign::class,
            PostArchive::class,
            Settings::class,
            Features::class,
            PostReportController::class,
            Cookies::class,
            DevReport::class,
            MasterSite::class,
            HttpHeaders::class,
            ActionPage::class,
            PageMeta::class,
            PostMeta::class,
            GravityFormsExtensions::class,
            BlockSettings::class,
            MediaReplacer::class,
            EnqueueController::class,
        ];

        if (is_admin()) {
            global $pagenow;

            // Load P4 Control Panel only on Dashboard page.
            $this->default_services[] = ControlPanel::class;
            $this->default_services[] = MediaArchive\UiIntegration::class;
            $this->default_services[] = MediaArchive\Rest::class;
            foreach (Features::external_settings() as $setting_class) {
                $this->default_services[] = $setting_class;
            }

            // Load P4 Metaboxes only when adding/editing a new Page/Post/Campaign.
            if ('post-new.php' === $pagenow || 'post.php' === $pagenow) {
                $this->default_services[] = MetaboxRegister::class;
            }

            // Load `Campaigns` class only when adding/editing a new tag.
            if ('edit-tags.php' === $pagenow || 'term.php' === $pagenow) {
                $this->default_services[] = Campaigns::class;
            }

            $this->default_services[] = Exporter::class;

            // Load `CampaignImporter` class only for WordPress import requests.
            // phpcs:disable
            if ('wordpress' === filter_input(INPUT_GET, 'import', FILTER_SANITIZE_FULL_SPECIAL_CHARS)) {
                // phpcs:enable
                $this->default_services[] = Importer::class;
            }

            (new Controllers\Menu\ArchiveImport(new View()))->load();
        }

        // Run Activator after theme switched to planet4-master-theme or a planet4 child theme.
        if (get_option('theme_switched')) {
            $this->default_services[] = Activator::class;
        }

        if (wp_is_json_request()) {
            $this->default_services[] = MetaboxRegister::class;
        }

        $services = array_merge($services, $this->default_services);
        if (!$services) {
            return;
        }

        foreach ($services as $service) {
            $this->services[$service] = new $service();
        }
    }

     /**
     * Inject dependencies for blocks.
     */
    private function load_block_services(): void
    {
        if (!Planet4Blocks::is_active()) {
            return;
        }

        if (!defined('P4_MASTER_THEME_LANGUAGES')) {
            define(
                'P4_MASTER_THEME_LANGUAGES',
                [
                    'en_US' => 'English',
                    'el_GR' => 'Ελληνικά',
                ]
            );
        }

        $services = [];
        $services[] = Controllers\Menu\BlocksReportController::class;
        $services[] = Controllers\Menu\BlocksUsageController::class;
        $services[] = Controllers\Menu\ReusableBlocksController::class;
        $services[] = Controllers\Menu\PostmetaCheckController::class;
        $services[] = Admin\Rest::class;

        foreach ($services as $service) {
            (new $service(new View()))->load();
        }
    }

    /**
     * Gets the loaded services.
     *
     * @return array The loaded services.
     */
    public function get_services(): array
    {
        return $this->services;
    }

    /**
     * Load blocks from Theme.
     */
    public static function add_blocks(): void
    {
        if (!Planet4Blocks::is_active()) {
            return;
        }

        new MasterBlocks();//NOSONAR
        new Blocks\Accordion();//NOSONAR
        new Blocks\Articles();//NOSONAR
        new Blocks\CarouselHeader();//NOSONAR
        new Blocks\Columns();//NOSONAR
        new Blocks\Cookies();//NOSONAR
        new Blocks\Counter();//NOSONAR
        new Blocks\Covers();//NOSONAR
        new Blocks\Gallery();//NOSONAR
        new Blocks\GuestBook();//NOSONAR
        new Blocks\HappyPoint();//NOSONAR
        new Blocks\SocialMedia();//NOSONAR
        new Blocks\Spreadsheet();//NOSONAR
        new Blocks\TableOfContents();//NOSONAR
        new Blocks\TakeActionBoxout();//NOSONAR
        new Blocks\Timeline();//NOSONAR
        new Blocks\TopicLink();//NOSONAR
        new Blocks\SecondaryNavigation();//NOSONAR

        register_block_pattern_category(
            'page-headers',
            [ 'label' => 'Page Headers' ],
        );

        // Load block patterns.
        BlockPattern::register_all();

        new Blocks\ActionButtonText();//NOSONAR

        Blocks\QueryLoopExtension::registerHooks();
        add_filter(
            'allowed_block_types_all',
            function ($allowed_block_types) {
                if (!is_array($allowed_block_types)) {
                    return $allowed_block_types;
                }

                if (!in_array('core/query', $allowed_block_types)) {
                    $allowed_block_types[] = 'core/query';
                }

                return $allowed_block_types;
            },
            10,
            1
        );
    }

    // phpcs:enable WordPress.Security.NonceVerification.Recommended

    /**
     * @param string $rel_path Relative path to the file.
     * @return int timestamp of file creation
     */
    public static function theme_file_ver(string $rel_path): int
    {
        $filepath = trailingslashit(get_template_directory()) . $rel_path;

        return self::get_timestamp($filepath);
    }

    /**
     * Get timestamp of a file.
     *
     * @param string $path The path of the file.
     *
     * @throws RuntimeException If the file doesn't exist, or filectime fails in some other way.
     * @return int Timestamp of last file change.
     */
    private static function get_timestamp(string $path): int
    {
        $ctime = filectime($path);

        if (!$ctime) {
            throw new RuntimeException("Tried to get file change time of {$path} but failed to.");
        }

        return $ctime;
    }

    /**
     * Enqueue a style with a version based on the file change time.
     *
     * @param string $relative_path An existing css file.
     * @param string|null   $handle The handle to enqueue with. Generated from path if empty.
     * @param array  $deps Dependencies of this style.
     */
    public static function enqueue_versioned_style(
        string $relative_path,
        ?string $handle = null,
        array $deps = []
    ): void {
        $relative_path = '/' . ltrim($relative_path, '/');

        $version = self::get_timestamp(get_template_directory() . $relative_path);

        wp_enqueue_style(
            // Fall back to unique handle based on the path.
            $handle ?? str_replace('/[^\w]+/g', '', $relative_path),
            get_template_directory_uri() . $relative_path,
            $deps,
            $version
        );
    }

    /**
     * Enqueue a script with a version based on the file change time.
     *
     * @param string $relative_path An existing js file.
     * @param array  $deps Dependencies of the script.
     * @param bool   $in_footer Whether the script should be loaded in the footer.
     */
    public static function enqueue_versioned_script(
        string $relative_path,
        array $deps = [],
        bool $in_footer = false
    ): void {
        // Create a supposedly unique handle based on the path.
        $handle = str_replace('/[^\w]/', '', $relative_path);

        $relative_path = '/' . ltrim($relative_path, '/');

        $version = self::get_timestamp(get_template_directory() . $relative_path);

        wp_enqueue_script(
            $handle,
            get_template_directory_uri() . $relative_path,
            $deps,
            $version,
            $in_footer
        );
    }
}
