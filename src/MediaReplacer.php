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
    private array $replacement_status;
    private array $user_messages;
    private CloudflarePurger $cf;

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
    ];

    /**
     * MediaReplacer constructor.
     */
    public function __construct()
    {
        // If the plugin is not active, abort.
        if (function_exists('is_plugin_active') && !is_plugin_active('wp-stateless/wp-stateless-media.php')) {
            return;
        }

        // If the stateless mode is disabled, abort.
        if (function_exists('ud_get_stateless_media') && ud_get_stateless_media('sm.mode') === 'disabled') {
            return;
        }

        $this->cf = new CloudflarePurger();

        $this->replacement_status = [
            'success' => [],
            'error' => [],
        ];

        // phpcs:disable Generic.Files.LineLength.MaxExceeded
        $this->user_messages = [
            'replace' => __('Replace Media', 'planet4-master-theme-backend'),
            'metabox' => __('Use this to replace the current file without changing the file URL.', 'planet4-master-theme-backend'),
            'attach' => __('Attachment ID is missing.', 'planet4-master-theme-backend'),
            'file' => __('File is missing.', 'planet4-master-theme-backend'),
            'media' => __('Media could not be replaced.', 'planet4-master-theme-backend'),
            'gd' => __('GD library is missing.', 'planet4-master-theme-backend'),
            'image' => __('Error processing image.', 'planet4-master-theme-backend'),
            'success' => __('File was succesfully replaced!', 'planet4-master-theme-backend'),
            'error' => __('There was a problem and the file could not be replaced:', 'planet4-master-theme-backend'),
            'cf_success' => __('URLs were successfully purged from cache:', 'planet4-master-theme-backend'),
            'cf_error' => __('There was an error purging these URLs from cache:', 'planet4-master-theme-backend'),
        ];
        // phpcs:enable Generic.Files.LineLength.MaxExceeded

        add_action('admin_enqueue_scripts', [$this, 'enqueue_media_modal_script']);
        add_filter('attachment_fields_to_edit', [$this, 'add_replace_media_button'], 10, 2);
        add_action('add_meta_boxes', [$this, 'add_replace_media_metabox']);
        add_action('wp_ajax_replace_media', [$this, 'ajax_replace_media']);
        add_action('admin_notices', [$this, 'display_admin_notices']);
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
            $this->user_messages['replace'],
            [$this, 'render_replace_media_metabox'],
            'attachment',
            'side',
            'low'
        );
    }

    /**
     * Adds a button to replace media files in the attachments editor.
     *
     * @param WP_Post $post The attachment post object.
     */
    public function render_replace_media_metabox(WP_Post $post): void
    {
        echo "<p>" . $this->user_messages['metabox'] . "</p>";
        echo $this->get_replace_button_html($post);
    }

    /**
     * Adds a button to replace media files in the Attachment details modal.
     *
     * @param array $form_fields Existing attachment form fields.
     * @param WP_Post $post The attachment post object.
     * @return array Modified attachment form fields.
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

        $form_fields['replace_media_button'] = array(
            'input' => 'html',
            'html' => $this->get_replace_button_html($post),
        );

        return $form_fields;
    }

    /**
     * Generates the HTML for the Replace Media button.
     *
     * @param WP_Post $post The attachment post object.
     * @return string The HTML for the replace button.
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
        ' . $this->user_messages['replace'] . '
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
                throw new \LogicException($this->user_messages['attach']);
            }

            // Check if the file is set
            if (empty($_FILES['file'])) {
                throw new \LogicException($this->user_messages['file']);
            }

            $attachment_id = intval($_POST['attachment_id']);
            $file = $_FILES['file'];
            $file_mime_type = mime_content_type($file['tmp_name']);

            // Determine if the file is an image based on its MIME type
            $image_type = array_search($file_mime_type, array_column(self::IMAGE_MIME_TYPES, 'mime'));

            if ($image_type !== false) {
                $this->replace_images($file, $attachment_id);
            } else {
                $this->replace_media_file($file, $attachment_id, $file_mime_type);
            }
        } catch (\LogicException $e) {
            $this->error_handler($e->getMessage());
        }
    }

    /**
     * Replaces the non-image media file.
     *
     * @param array $file The uploaded file data.
     * @param int $old_file_id The ID of the attachment to replace.
     * @param string $file_mime_type The MIME type of the uploaded file.
     */
    private function replace_media_file(
        array $file,
        int $old_file_id,
        string $file_mime_type
    ): void {
        try {
            if (!function_exists('ud_get_stateless_media')) {
                throw new \LogicException($this->user_messages['error']);
            }

            $filename = get_post_meta($old_file_id)['_wp_attached_file'][0];

            $status = $this->upload_file(
                $filename,
                $file['tmp_name'],
                $file_mime_type,
                [
                    'size' => '__full',
                    'object-id' => $old_file_id,
                    'source-id' => md5($file . ud_get_stateless_media()->get('sm.bucket')), //NOSONAR
                    'file-hash' => md5($filename), //NOSONAR
                ]
            );

            if (!$status) {
                throw new \LogicException($this->user_messages['error']);
            }

            wp_send_json_success();
        } catch (\LogicException $e) {
            $this->error_handler($e->getMessage());
        }
    }

    /**
     * Replaces images (main image and also all the thumbnails).
     * Thumbnails are manually created using the GD library.
     * Images are uploaded to Google Storage using WP Stateless functions.
     *
     * @param array $file The uploaded file data.
     * @param string $id The attachment ID.
     */
    private function replace_images(array $file, string $id): void
    {
        try {
            // The GD extension is needed for image manipulation.
            // If it's not loaded, abort.
            if (!extension_loaded('gd')) {
                throw new \LogicException($this->user_messages['gd']);
            }

            $new_image_path = $file['tmp_name'];
            $new_image_info = getimagesize($new_image_path);
            $new_image_width = $new_image_info[0];
            $new_image_height = $new_image_info[1];
            $new_image_type = $new_image_info[2];

            // Validate image type against allowed MIME types
            if (!isset(self::IMAGE_MIME_TYPES[$new_image_type])) {
                throw new \LogicException($this->user_messages['image']);
            }

            // Load the image dynamically
            $image_data = self::IMAGE_MIME_TYPES[$new_image_type];
            $image = call_user_func($image_data['create'], $new_image_path);
            if (!$image) {
                throw new \LogicException($this->user_messages['image']);
            }

            // Get the image metadata.
            $old_image_meta = get_post_meta($id, 'sm_cloud')[0];
            if (!$old_image_meta) {
                throw new \LogicException($this->user_messages['image']);
            }

            $old_image_extension = pathinfo($old_image_meta['name'], PATHINFO_EXTENSION);
            $old_image_dirname = pathinfo($old_image_meta['name'], PATHINFO_DIRNAME);
            $old_image_filename = pathinfo($old_image_meta['name'], PATHINFO_FILENAME);
            $image_name = $old_image_dirname . '/' . $old_image_filename;

            if (!function_exists('ud_get_stateless_media')) {
                throw new \LogicException($this->user_messages['image']);
            }

            // Create metadata for uploading the main image.
            $metadata = [
                'width' => $new_image_width,
                'height' => $new_image_height,
                'size' => '__full',
                'object-id' => $id,
                'source-id' => md5($file . ud_get_stateless_media()->get('sm.bucket')), //NOSONAR
                'file-hash' => md5($old_image_filename), //NOSONAR
            ];

            // Save the file to a temporary location
            $temporary_file_path = tempnam(sys_get_temp_dir(), 'img_') . '.' . $old_image_extension;
            call_user_func($image_data['save'], $image, $temporary_file_path);

            // Replace the main image.
            $status = $this->upload_file(
                $old_image_filename . '.' . $old_image_extension,
                $temporary_file_path,
                $image_data['mime'],
                $metadata
            );

            $this->upload_thumbnails(
                $id,
                $image,
                $new_image_width,
                $new_image_height,
                $old_image_meta,
                $image_name,
                $image_data,
                $old_image_extension
            );

            // Free memory
            imagedestroy($image);

            if (!$status) {
                throw new \LogicException($this->user_messages['error']);
            }

            wp_send_json_success();
        } catch (\LogicException $e) {
            $this->error_handler($e->getMessage());
        }
    }

    /**
     * Uploads and replaces image thumbnails.
     *
     * @param string $id The attachment ID.
     * @param object $image The GD image resource.
     * @param int $new_image_width The width of the new image.
     * @param int $new_image_height The height of the new image.
     * @param array $old_image_meta Metadata of the old image.
     * @param string $image_name The name of the image.
     * @param array $image_data The image properties (MIME type, extension, etc.).
     * @param string $old_image_extension The original image file extension.
     */
    private function upload_thumbnails(
        string $id,
        object $image,
        int $new_image_width,
        int $new_image_height,
        array $old_image_meta,
        string $image_name,
        array $image_data,
        string $old_image_extension
    ): void {
        // Handle image thumbnails.
        foreach ($old_image_meta['sizes'] as $size => $old_image_data) {
            $old_image_width = $old_image_data['width'];
            $old_image_height = $old_image_data['height'];

            // Create metadata for the thumbnails.
            $metadata = [
                'width' => $new_image_width,
                'height' => $new_image_height,
                'file-hash' => md5($image_name . '-' . $old_image_width . 'x' . $old_image_height), //NOSONAR
                'size' => $size,
                'child-of' => $id,
            ];

            // Create thumbnail.
            $thumb = $this->create_image_thumbnail(
                $image,
                $new_image_width,
                $new_image_height,
                $old_image_width,
                $old_image_height
            );

            // Save the file to a temporary location
            $temporary_file_path = tempnam(sys_get_temp_dir(), 'thumb_') . '.' . $old_image_extension;
            call_user_func($image_data['save'], $thumb, $temporary_file_path);

            // Replace thumbnail in Google Cloud Storage.
            $this->upload_file(
                $image_name . '-' . $old_image_width . 'x' . $old_image_height . '.' . $old_image_extension,
                $temporary_file_path,
                $image_data['mime'],
                $metadata
            );

            // Free memory
            imagedestroy($thumb);
        }
    }

    /**
     * Creates an image thumbnail.
     *
     * @param object $image The GD image resource.
     * @param int $new_image_width The width of the new image.
     * @param int $new_image_height The height of the new image.
     * @param int $old_image_width The width of the thumbnail.
     * @param int $old_image_height The height of the thumbnail.
     * @return object The generated thumbnail image.
     */
    private function create_image_thumbnail(
        object $image,
        int $new_image_width,
        int $new_image_height,
        int $old_image_width,
        int $old_image_height
    ): object {
        try {
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
        } catch (\LogicException $e) {
            $this->error_handler($e->getMessage());
        }
    }

    /**
     * Uploads a file to the media storage.
     *
     * @param string $name The file name.
     * @param string $absolute_path The absolute path to the file.
     * @param string $mime The MIME type of the file.
     * @param array $metadata Metadata for the file upload.
     * @return bool True if the upload was successful, false otherwise.
     */
    private function upload_file(
        string $name,
        string $absolute_path,
        string $mime,
        array $metadata
    ): bool {
        try {
            // Prepare the upload arguments
            $image_args = [
                'name' => $name,
                'absolutePath' => $absolute_path,
                'mimeType' => $mime,
                'metadata' => $metadata,
                'cacheControl' => 'public, max-age=36000, must-revalidate',
                'contentDisposition' => null,
                'force' => true,
            ];

            if (!function_exists('ud_get_stateless_media')) {
                throw new \LogicException($this->user_messages['error']);
            }

            // Upload the file to Google Cloud Storage.
            $status = ud_get_stateless_media()->get_client()->add_media($image_args);

            if ($status) {
                $stateless_url = "https://www.greenpeace.org/static/" . $status['bucket'] . "/" . $status['name'];
                $this->success_handler($this->user_messages['success'], $stateless_url);
                return true;
            }
            throw new \LogicException($this->user_messages['error']);
        } catch (\LogicException $e) {
            $this->error_handler($e->getMessage());
            return false;
        }
    }

    /**
     * Handles errors in replacements.
     * Sets error messages.
     *
     * @param string $message The error message.
     */
    private function error_handler(string $message): void
    {
        //phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged
        error_log($message);
        array_push($this->replacement_status['error'], $message);
        $this->transient_handler(self::TRANSIENT['file'], $this->replacement_status);
        wp_send_json_error($message);
    }

    /**
     * Handles successful replacements.
     * Sets successful messages.
     *
     * @param string $message The success message.
     */
    private function success_handler(string $message, string $stateless_url): void
    {
        $this->cf->purge([$stateless_url]);

        // foreach ($aa as [$response, $purgedUrls]) {
        //     error_log ("Purged URLs:");
        //     error_log(print_r($purgedUrls, true));
        //     // print_r($purgedUrls);
        //     // echo "Response:\n";
        //     error_log('aaaa $response');
        //     error_log(print_r($response, true));
        // }

        array_push($this->replacement_status['success'], $message);
        $this->transient_handler(self::TRANSIENT['file'], $this->replacement_status);
    }

    /**
     * Manages transient messages.
     *
     * @param string $transient The transient key.
     * @param array $messages The messages to store.
     */
    private function transient_handler(string $transient, array $messages): void
    {
        $encoded_msg = json_encode($messages, JSON_PRETTY_PRINT);
        set_transient($transient, $encoded_msg, 5);
    }

    /**
     * Displays admin notices.
     */
    public function display_admin_notices(): void
    {
        $this->render_notice(
            'file',
            $this->user_messages['success'],
            $this->user_messages['error'],
        );
    }

    /**
     * Renders an admin notice.
     *
     * @param string $transient_key The key for the transient notice.
     * @param string $success_message The success message.
     * @param string $error_message The error message.
     */
    private function render_notice(string $transient_key, string $success_message, string $error_message): void
    {
        if (!$status = get_transient(self::TRANSIENT[$transient_key])) {
            return;
        }

        $status = json_decode($status, true);

        if (!empty($status['success'])) {
            printf(
                "<div class='notice notice-success is-dismissible'><p>%s</p></div>",
                esc_html($success_message),
            );
        }

        if (!empty($status['error'])) {
            printf(
                "<div class='notice notice-error is-dismissible'><p>%s</p><li>%s</li></div>",
                esc_html($error_message),
                implode("</li><li>", array_map('esc_html', $status['error']))
            );
        }

        delete_transient(self::TRANSIENT[$transient_key]);
    }
}
