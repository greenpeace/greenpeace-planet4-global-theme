<?php

/**
 * An importer for archived content from an XML file.
 */

 namespace P4\MasterTheme\Controllers\Menu;

use WP_Query;

/**
 * An importer for archived content from an XML file.
 */
class ArchiveImport extends Controller
{
    private const WAY_BACK_URL = 'https://wayback.archive-it.org/9650/';

    private const P3_BASE_URL = 'http://p3-raw.greenpeace.org/';

    /**
     * Get all urls of P3 that were already imported.
     *
     * @return string[] All urls of P3 that were already imported.
     */
    private static function get_imported_p3_urls(): array
    {
        $query = new WP_Query([
            'meta_key' => 'p3_url',
            'posts_per_page' => -1,
        ]);

        $urls = [];
        foreach ($query->posts as $post) {
            $urls[] = get_post_meta($post->ID, 'p3_url')[0];
        }

        return $urls;
    }

    /**
     * Add the admin menu item.
     */
    public function create_admin_menu(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        add_submenu_page(
            'edit.php?post_type=archive',
            __('Archive Import', 'planet4-blocks-backend'),
            __('Archive Import', 'planet4-blocks-backend'),
            'edit_posts',
            'archive-import',
            [ $this, 'get_page' ]
        );
    }

    /**
     * Render the admin page.
     *
     * @return string The html for the admin page.
     */
    public function get_page(): string
    {
        // There is no button for this currently, needs to be manually added to the url.
        if (isset($_GET['delete_all'])) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $archives = get_posts([
                'post_type' => 'archive',
                'numberposts' => -1,
            ]);
            foreach ($archives as $archive) {
                wp_delete_post($archive->ID);
            }
            return 'Deleted all archived posts';
        }

        $file = wp_import_handle_upload();

        if (isset($file['error'])) {
            echo $file['error']; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

            wp_import_upload_form('edit.php?post_type=archive&page=archive-import');

            return '';
        }
        $urls_imported_as_p4_content = self::get_imported_p3_urls();

        //phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
        $data = simplexml_load_string(file_get_contents($file['file']));

        $items = $data->item;

        $total = count($items);
        $errors = [];
        $existing = [];
        $created = [];

        foreach ($items as $item) {
            $url = self::WAY_BACK_URL . $item->url;
            if ($this->does_post_exist($url, $urls_imported_as_p4_content)) {
                $existing[] = $url;
                continue;
            }

            $username = 'archive-user';

            $author_id = $this->find_or_create_author($username);

            $result = wp_insert_post([
                'post_author' => $author_id,
                'post_date' => (string) $item->date,
                'post_content' => (string) $item->text,
                'post_title' => wp_strip_all_tags((string) $item->title),
                'permalink' => $url,
                'post_name' => $url,
                'guid' => $url,
                'post_excerpt' => (string) ($item->excerpt ?? self::create_excerpt($item->text)),
                'post_type' => \P4\MasterTheme\PostArchive::POST_TYPE,
                // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
                'post_status' => ('1' === (string) $item->isDateMissing || empty($item->date)) ?
                    'draft' : 'publish',
            ]);

            if (is_plugin_active('sitepress-multilingual-cms/sitepress.php')) {
                global $sitepress;
                $sitepress->set_element_language_details(
                    $result,
                    'post_archive',
                    null,
                    (string) $item->language
                );
            }

            if (is_wp_error($result)) {
                $errors[] = $result;
            } else {
                $created[] = $result;
            }
        }

        //phpcs:disable
        echo 'Peak memory usage: ' . $this->readable_bytes(memory_get_peak_usage());
        echo "Total: $total<br/>";
        echo 'Existing or duplicate: ' . count($existing) . '<br/>';
        echo 'Created:: ' . count($created) . '<br/>';
        echo 'Errors(' . count($errors) . '): ' . print_r($errors, true) . '<br/>';
        //phpcs:enable
    }

    /**
     * Find or create an author by full name.
     *
     * @param string $fullname The full name of the author.
     *
     * @return int The author ID.
     */
    private function find_or_create_author(string $fullname): int
    {
        $username = $this->nickname_from_fullname($fullname);

        $user = get_user_by('login', $username);

        if ($user) {
            return $user->ID;
        }

        // No user exists yet, let's create it.
        $result = wp_create_user(
            $username,
            'a0s98dy8f0 9as7dfy79asbyd9fyasd9fy',
            'p3-archive#' . $username . '@greenpeace.org',
        );

        if (is_wp_error($result)) {
            //phpcs:disable
            die(print_r($result, true));
            //phpcs:enable
        }

        return $result;
    }

    /**
     * Create a nickname from a full name.
     *
     * @param string $fullname The full author name.
     * @return string The generated nickname.
     */
    private function nickname_from_fullname(string $fullname): string
    {
        return preg_replace('/\W/', '', strtolower($fullname));
    }

    /**
     * Check if a post was either imported earlier as P4 content, or was already imported as archived content.
     *
     * @param string $url The P3 URL.
     * @param array  $urls_imported_as_p4_content List of P3 URLs that were imported as P4 content.
     * @return bool Whether the post already exists.
     */
    private function does_post_exist(string $url, array $urls_imported_as_p4_content): bool
    {
        global $wpdb;

        $path_only = rtrim(str_replace(self::WAY_BACK_URL . self::P3_BASE_URL, '', $url), '/');

        foreach ($urls_imported_as_p4_content as $imported_url) {
            // If the path matches one of the imported urls.
            if (strpos($imported_url, $path_only) !== false) {
                return true;
            }
        }

        $archived_post = $wpdb->get_row(
            $wpdb->prepare('SELECT guid FROM wp_posts WHERE guid = %s', [$url]),
            'ARRAY_A',
        );

        return (bool) $archived_post;
    }

    /**
     * Utility function to format bytes into human readable sizes.
     *
     * @param int|string $bytes The bytes to format.
     * @return string The formatted bytes.
     */
    private function readable_bytes(int|string $bytes): string
    {
        $i = floor(log($bytes) / log(1024));
        $sizes = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

        return sprintf('%.02F', $bytes / (1024 ** $i)) * 1 . ' ' . $sizes[$i];
    }

    /**
     * Generate an excerpt from text.
     *
     * @param string $text The text to create an excerpt for.
     * @return string The excerpt.
     */
    private static function create_excerpt(string $text): string
    {
        return wp_trim_words($text, 55);
    }
}
