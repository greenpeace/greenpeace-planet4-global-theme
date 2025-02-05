<?php

namespace P4\MasterTheme;

use WP_Post;

/**
 * Class MediaReplacer.
 *
 * This class is used to replace media elements in WordPress.
 */
class MediaReplacer
{
    /**
     * List of image MIME types.
     * We need this list as for now we will only replace non-image files.
     */
    private const IMAGE_MIME_TYPES = [
        IMAGETYPE_JPEG => [
            'mime' => 'image/jpeg',
            'create' => 'imagecreatefromjpeg',
            'save' => 'imagejpeg',
            'extension' => 'jpg',
        ],
        IMAGETYPE_PNG => [
            'mime' => 'image/png',
            'create' => 'imagecreatefrompng',
            'save' => 'imagepng',
            'extension' => 'png',
        ],
        IMAGETYPE_GIF => [
            'mime' => 'image/gif',
            'create' => 'imagecreatefromgif',
            'save' => 'imagegif',
            'extension' => 'gif',
        ],
    ];

    private const TRANSIENT = [
        'file' => 'file_replacement_notice',
        'cloudflare' => 'cloudflare_purge_notice',
    ];

    private CloudflarePurger $cf;

    private array $replacement_status;

    private array $cloudflare_purge_status;

    /**
     * MediaReplacer constructor.
     */
    public function __construct()
    {
        if (function_exists('is_plugin_active') && !is_plugin_active('wp-stateless/wp-stateless-media.php')) {
            return;
        }

        $this->cf = new CloudflarePurger();

        $this->replacement_status = [
            'success' => [],
            'error' => [],
        ];

        $this->cloudflare_purge_status = [
            'success' => [],
            'error' => [],
        ];

        add_action('admin_enqueue_scripts', [$this, 'enqueue_media_modal_script']);
        add_filter('attachment_fields_to_edit', [$this, 'add_replace_media_button'], 10, 2);
        add_action('add_meta_boxes', [$this, 'add_replace_media_metabox']);
        add_action('wp_ajax_replace_media', [$this, 'ajax_replace_media']);
        add_action('admin_notices', [$this, 'display_notices']);
    }

    /**
     * Enqueues the JavaScript required for the media replacement modal.
     */
    public function enqueue_media_modal_script(): void
    {
        wp_enqueue_script(
            'custom-media-replacer',
            get_template_directory_uri() . '/admin/js/media_replacer.js',
            [],
            Loader::theme_file_ver("admin/js/media_replacer.js"),
            true
        );
    }

    /**
     * Adds a metabox to the attachments editor.
     */
    public function add_replace_media_metabox(): void
    {
        add_meta_box(
            'replace_media_metabox',
            'Replace Media',
            [$this, 'render_replace_media_metabox'],
            'attachment',
            'side',
            'low'
        );
    }

    /**
     * Adds a button to replace media files in the attachments editor.
     *
     * @param WP_Post $post The current attachment post object.
     */
    public function render_replace_media_metabox(WP_Post $post): void
    {
        // Check if the attachment post is an image
        // If so, check if the GD extension is loaded
        if (in_array($post->post_mime_type, array_column(self::IMAGE_MIME_TYPES, 'mime')) && !extension_loaded('gd')) {
            $message = __('There was a problem. This image cannot be replaced.', 'planet4-master-theme-backend');
            echo "<p>" . $message . "</p>";
            return;
        }

        // phpcs:ignore Generic.Files.LineLength.MaxExceeded
        $message = __('Use this to replace the current file without changing the file URL.', 'planet4-master-theme-backend');
        echo "<p>" . $message . "</p>";
        echo $this->get_replace_button_html($post);
    }

    /**
     * Adds a button to replace media files to the Attachment details modal.
     *
     * @param array $form_fields The existing form fields for the attachment.
     * @param WP_Post $post The current attachment post object.
     * @return array Modified form fields with the replace media button added.
     */
    public function add_replace_media_button(array $form_fields, WP_Post $post): array
    {
        // Check if the post type is 'attachment'
        if ($post->post_type !== 'attachment') {
            return $form_fields;
        }

        // Check if the current page is an editing page
        // If so, return as a metabox is added to the page
        if (isset($_GET['action']) && $_GET['action'] === 'edit') {
            return $form_fields;
        }

        // Check if the attachment post is an image
        // If so, check if the GD extension is loaded
        if (in_array($post->post_mime_type, array_column(self::IMAGE_MIME_TYPES, 'mime')) && !extension_loaded('gd')) {
            return $form_fields;
        }

        $form_fields['replace_media_button'] = array(
            'input' => 'html',
            'html' => $this->get_replace_button_html($post),
        );

        return $form_fields;
    }

    /**
     * Renders the HTML of the Replace Media button.
     *
     * @param WP_Post $post The current attachment post object.
     * @return string The button html.
     */
    private function get_replace_button_html(WP_Post $post): string
    {
        return
        '<button
            type="button"
            class="button media-replacer-button"
            data-attachment-id="' . esc_attr($post->ID) . '"
            data-mime-type="' . esc_attr($post->post_mime_type) . '"
        >
            Replace Media
        </button>
        <input
            type="file"
            class="replace-media-file"
            style="display: none;"
            accept="' . esc_attr($post->post_mime_type) . '"
        />
        ';
    }

    /**
     * Handles the AJAX request for replacing media files.
     * Checks for the attachment ID and uploaded file, uploads the file,
     * and replaces the old media file with the new one.
     */
    public function ajax_replace_media(): void
    {
        try {
            // Check if the attachment ID is set
            if (!isset($_POST['attachment_id'])) {
                $message = __('Attachment ID is missing.', 'planet4-master-theme-backend');
                array_push($this->replacement_status['error'], $message);
                wp_send_json_error($message);
                return;
            }

            // Check if the file is set
            if (empty($_FILES['file'])) {
                $message = __('File is missing.', 'planet4-master-theme-backend');
                array_push($this->replacement_status['error'], $message);
                wp_send_json_error($message);
                return;
            }

            $attachment_id = intval($_POST['attachment_id']);
            $file = $_FILES['file'];
            $file_mime_type = mime_content_type($file['tmp_name']);

            // Determine if the file is an image based on its MIME type
            $image_type = array_search($file_mime_type, array_column(self::IMAGE_MIME_TYPES, 'mime'));

            // If the file is an image, we manually create the thumbnails and replace them in Google Storage.
            // That's because thumbnails cannot be created using the "wp_generate_attachment_metadata" function
            // when the WP Stateless plugin is set to store and serve files with Google Cloud Storage only,
            // and media files are not stored locally.
            // This happens because the "wp_handle_upload" function uploads the image directly to Google Storage,
            // and then "wp_generate_attachment_metadata" function is not able to create thumbnails.
            if ($image_type !== false) {
                $this->replace_images($file, $attachment_id);
            } else {
                $this->replace_media_file($file, $attachment_id);
            }
        } catch (\Exception $e) {
            array_push($this->replacement_status['error'], $e->getMessage());
            wp_send_json_error($e->getMessage());
            return;
        }
    }

    /**
     * Replaces the media file associated with the old attachment ID
     * with the new file located at the specified path.
     *
     * @param array $new_file The file data array of the new file.
     * @param int $old_file_id The ID of the old attachment.
     */
    private function replace_media_file(array $file, int $old_file_id): bool
    {
        $upload_overrides = array('test_form' => false);

        // Upload the file
        $movefile = wp_handle_upload($file, $upload_overrides);

        // If the file was not uploaded, abort
        if (!$movefile) {
            $message = __('Media could not be uploaded.', 'planet4-master-theme-backend');
            $error_message = isset($movefile['error']) ? $movefile['error'] : $message;
            set_transient('media_replacement_error', $error_message, 5);
            wp_send_json_error($error_message);
            return false;
        }

        // Get the old file path
        $old_file_path = get_attached_file($old_file_id);

        // Check if the old file exists
        if (file_exists($old_file_path)) {
            unlink($old_file_path);
        }

        // Move the new file to the old file's location
        $file_renamed = rename($movefile['file'], $old_file_path);

        // If the file was not renamed, abort
        if (!$file_renamed) {
            return false;
        }

        // Update the attachment metadata with new information
        $filetype = wp_check_filetype($old_file_path);
        $attachment_data = array(
            'ID' => $old_file_id,
            'post_mime_type' => $filetype['type'],
            'post_title' => preg_replace('/\.[^.]+$/', '', basename($old_file_path)),
            'post_content' => '',
            'post_status' => 'inherit',
        );

        // Update the database record for the file
        $post_updated = wp_update_post($attachment_data);

        // If the post was not updated, abort
        if (is_wp_error($post_updated) || $post_updated === 0) {
            return false;
        }

        // Update file metadata
        // By calling the "wp_update_attachment_metadata" function,
        // the WP Stateless plugin syncs the file with Google Storage.
        // https://github.com/udx/wp-stateless/blob/0871da645453240007178f4a5f243ceab6a188ea/lib/classes/class-bootstrap.php#L376
        $attach_data = wp_generate_attachment_metadata($old_file_id, $old_file_path);
        wp_update_attachment_metadata($old_file_id, $attach_data);
    }


    /**
     * Replaces images (main image and also all the thumbnails).
     * Thumbnails are manually created using the GD library.
     * Images are uploaded to Google Storage using WP Stateless functions.
     *
     * @param array $file An array with data of the new image.
     * @param string $id The id of the image to be replaced.
     */
    private function replace_images(array $file, string $id): bool
    {
        try {
            $new_image_path = $file['tmp_name'];
            $new_image_info = getimagesize($new_image_path);
            $new_image_width = $new_image_info[0];
            $new_image_height = $new_image_info[1];
            $new_image_type = $new_image_info[2];

            // Validate image type against allowed MIME types
            if (!isset(self::IMAGE_MIME_TYPES[$new_image_type])) {
                $error_message = __('Media file is not an image.', 'planet4-master-theme-backend');
                array_push($this->replacement_status['error'], $error_message);
                wp_send_json_error($error_message);
                return false;
            }

            // Load the image dynamically
            $image_data = self::IMAGE_MIME_TYPES[$new_image_type];
            $image = call_user_func($image_data['create'], $new_image_path);
            if (!$image) {
                $error_message = __('Image could not be loaded.', 'planet4-master-theme-backend');
                array_push($this->replacement_status['error'], $error_message);
                wp_send_json_error($error_message);
                return false;
            }

            $old_image_meta = get_post_meta($id, 'sm_cloud')[0];
            if (!$old_image_meta) {
                $error_message = __('Image metadata could not be loaded.', 'planet4-master-theme-backend');
                array_push($this->replacement_status['error'], $error_message);
                wp_send_json_error($error_message);
                return false;
            }

            $old_image_dirname = pathinfo($old_image_meta['name'], PATHINFO_DIRNAME);
            $old_image_filename = pathinfo($old_image_meta['name'], PATHINFO_FILENAME);
            $image_name = $old_image_dirname . '/' . $old_image_filename;

            $metadata = [
                'width' => $new_image_width,
                'height' => $new_image_height,
                'file-hash' => md5($old_image_filename),
                'size' => '__full',
                'object-id' => $id,
                'source-id' => md5($file . ud_get_stateless_media()->get('sm.bucket')),
            ];

            // Upload the resized variant
            $this->upload_file(
                $image,
                $image_data,
                $image_data['extension'],
                $image_data['mime'],
                $old_image_filename,
                $metadata
            );

            // Handle each size variant
            foreach ($old_image_meta['sizes'] as $size => $old_image_data) {
                $old_image_width = $old_image_data['width'];
                $old_image_height = $old_image_data['height'];

                $metadata = [
                    'width' => $new_image_width,
                    'height' => $new_image_height,
                    'file-hash' => md5($image_name . '-' . $old_image_width . 'x' . $old_image_height),
                    'size' => $size,
                    'child-of' => $id,
                ];

                $thumb = $this->create_image_thumbnail(
                    $image,
                    $new_image_width,
                    $new_image_height,
                    $old_image_width,
                    $old_image_height
                );

                // Upload the resized variant
                $this->upload_file(
                    $thumb,
                    $image_data,
                    $image_data['extension'],
                    $image_data['mime'],
                    $image_name . '-' . $old_image_width . 'x' . $old_image_height,
                    $metadata
                );

                // Free memory
                imagedestroy($thumb);
            }

            imagedestroy($image);
            set_transient(self::TRANSIENT['file'], json_encode($this->replacement_status, JSON_PRETTY_PRINT), 5);
            return true;
        } catch (\Exception $e) {
            array_push($this->replacement_status['error'], $e->getMessage());
            wp_send_json_error($e->getMessage());
            return false;
        }
    }

    private function create_image_thumbnail(
        $image,
        $new_image_width,
        $new_image_height,
        $old_image_width,
        $old_image_height
    ) {
        // Determine cropping dimensions
        $src_aspect = $new_image_width / $new_image_height;
        $dst_aspect = $old_image_width / $old_image_height;

        if ($src_aspect > $dst_aspect) {
            $src_height = $new_image_height;
            $src_width = (int) ($new_image_height * $dst_aspect);
            $src_x = (int) (($new_image_width - $src_width) / 2);
            $src_y = 0;
        } else {
            $src_width = $new_image_width;
            $src_height = (int) ($new_image_width / $dst_aspect);
            $src_x = 0;
            $src_y = (int) (($new_image_height - $src_height) / 2);
        }

        $thumb = imagecreatetruecolor($old_image_width, $old_image_height);
        imagecopyresampled(
            $thumb,
            $image,
            0,
            0,
            $src_x,
            $src_y,
            $old_image_width,
            $old_image_height,
            $src_width,
            $src_height
        );
        return $thumb;
    }

    /**
     * Uploads an image (either main or thumbnail) to the media client.
     *
     * This function saves the image to a temporary file, prepares the appropriate upload arguments
     * (including metadata), and then uploads the image to the media storage.
     *
     * @param {Object} image - The image resource (created from the image data).
     * @param {array} image_data - The image metadata, including the file extension, mime type, and save method.
     * @param {string} image_name - The name of the image, used as a base for the upload.
     * @param {number} new_image_width - The width of the new image.
     * @param {number} new_image_height - The height of the new image.
     * @param {array} file - The file object containing information about the uploaded image.
     * @param {string} id - The unique identifier for the image (used for metadata).
     * @param {string} size - The size identificator.
     * @param {boolean} [is_thumbnail=false] - Flag indicating whether the image is a thumbnail.
     *
     * @returns {mixed}
     */
    private function upload_file(
        object $image,
        array $image_data,
        string $extension,
        string $mime,
        string $file_name,
        array $metadata,
    ): mixed {
        try {
            // Save the image to a temporary location
            $temporary_file_path = tempnam(sys_get_temp_dir(), 'thumb_') . '.' . $extension;
            call_user_func($image_data['save'], $image, $temporary_file_path);

            // Prepare the upload arguments
            $image_args = [
                'name' => $file_name . '.' . $extension,
                'force' => true,
                'absolutePath' => $temporary_file_path,
                'cacheControl' => 'public, max-age=36000, must-revalidate',
                'contentDisposition' => null,
                'mimeType' => $mime,
                'metadata' => $metadata,
            ];

            // Upload the image (main or variant)
            $status = ud_get_stateless_media()->get_client()->add_media($image_args);

            if ($status) {
                array_push($this->replacement_status['success'], $file_name . '.' . $extension);
                $this->purge_cloudflare('https://www.greenpeace.org/static/');
                return true;
            } else {
                array_push($this->replacement_status['error'], $file_name . '.' . $extension);
                return false;
            }
        } catch (\Exception $e) {
            array_push($this->replacement_status['error'], $e->getMessage());
            wp_send_json_error($e->getMessage());
            return false;
        }
    }

    /**
     * Purge the Cloudflare cache for a specific URL.
     *
     * @param string $url The URL to be purged.
     */
    private function purge_cloudflare(string $url): void
    {
        try {
            $generator = $this->cf->purge([$url]);
            $api_responses = [];

            foreach ($generator as $purge_result) {
                [$api_response] = $purge_result;
                $api_responses[] = $api_response;
            }

            if ($api_responses[0]) {
                array_push($this->cloudflare_purge_status['success'], $url);
            } else {
                array_push($this->cloudflare_purge_status['error'], $url);
            }
        } catch (\Exception $e) {
            array_push($this->cloudflare_purge_status['error'], $e->getMessage());
        }

        set_transient(self::TRANSIENT['cloudflare'], json_encode($this->cloudflare_purge_status, JSON_PRETTY_PRINT), 5);
    }

    public function display_notices(): void
    {
        $this->render_notices(
            'file',
            'Successfully replaced:',
            'Replacement errors:'
        );
        $this->render_notices(
            'cloudflare',
            'URLs were successfully purged from cache:',
            'There was an error purging these URLs from cache:'
        );
    }

    /**
     * Renders admin notices.
     */
    private function render_notices(string $transient_key, string $success_message, string $error_message): void
    {
        if ($status = get_transient(self::TRANSIENT[$transient_key])) {
            $status = json_decode($status, true);

            if (!empty($status['success'])) {
                printf(
                    "<div class='notice notice-success is-dismissible'><strong>%s</strong><ul><li>%s</li></ul></div>",
                    esc_html($success_message),
                    implode("</li><li>", array_map('esc_html', $status['success']))
                );
            }

            if (!empty($status['error'])) {
                printf(
                    "<div class='notice notice-error is-dismissible'><strong>%s</strong><ul><li>%s</li></ul></div>",
                    esc_html($error_message),
                    implode("</li><li>", array_map('esc_html', $status['error']))
                );
            }

            delete_transient(self::TRANSIENT[$transient_key]);
        }
    }
}
