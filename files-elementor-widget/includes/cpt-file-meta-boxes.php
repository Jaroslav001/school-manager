<?php
// includes/meta-boxes.php
if (! defined('ABSPATH')) {
    exit;
}

/**
 * 1a) Make the edit/new forms multipart so we can upload files.
 */
add_action('post_edit_form_tag', __NAMESPACE__ . '\\fev_add_form_enctype');
add_action('post_new_form_tag',  __NAMESPACE__ . '\\fev_add_form_enctype');
function fev_add_form_enctype()
{
    echo ' enctype="multipart/form-data"';
}

/**
 * 1b) Enqueue the WP media uploader on our CPT screens.
 */
add_action('admin_enqueue_scripts', __NAMESPACE__ . '\\fev_enqueue_media_uploader');
function fev_enqueue_media_uploader($hook_suffix)
{
    // Bail if get_current_screen() isn't available
    if (! function_exists('get_current_screen')) {
        return;
    }
    $screen = get_current_screen();

    // Only load on our fev_file post-new.php and post.php screens
    if ($screen->post_type === 'fev_file') {
        wp_enqueue_media();
    }
}

/**
 * 2) Remove title/editor/slug for cleaner UI.
 */
add_action('init', function () {
    remove_post_type_support('fev_file', 'title', 'content');
}, 21);

add_action('init', function () {
    // Remove the rich text editor
    remove_post_type_support('fev_file', 'editor');
}, 20);

add_action('add_meta_boxes', function () {
    remove_meta_box('slugdiv', 'fev_file', 'normal');
}, 20);

/**
 * 3) Add our “File Settings” metabox.
 */
add_action('add_meta_boxes', __NAMESPACE__ . '\\fev_add_file_metabox');
function fev_add_file_metabox()
{
    add_meta_box(
        'fev_file_settings',          // ID
        __('File Settings', 'files-elementor-widget'), // Title
        __NAMESPACE__ . '\\fev_file_metabox_callback',   // Callback
        'fev_file',                    // CPT
        'normal',                      // Context
        'high'                        // Priority
    );
}

/**
 * 4) Render the metabox form.
 */
function fev_file_metabox_callback(WP_Post $post)
{
    wp_nonce_field('fev_save_file_settings', 'fev_file_settings_nonce');

    $path      = get_post_meta($post->ID, '_fev_file_path',   true);
    $school_id = get_post_meta($post->ID, '_fev_file_school', true);

    // File upload/input
    if (! $path) {
        echo '<p><strong>' . esc_html__('Upload File', 'files-elementor-widget') . '</strong></p>';
        echo '<input type="file" name="fev_file_upload" /><br>';
    } else {
        echo '<p><strong>' . esc_html__('Current File', 'files-elementor-widget') . '</strong></p>';
        printf(
            '<p><a href="%1$s" target="_blank">%2$s</a></p>',
            esc_url(wp_upload_dir()['baseurl'] . '/' . $path),
            esc_html(basename($path))
        );
    }

    // Associated school dropdown
    echo '<p><strong>' . esc_html__('Associate with School', 'files-elementor-widget') . '</strong></p>';
    echo '<select name="fev_file_school">';
    echo '<option value="">' . esc_html__('&mdash; Select a school &mdash;', 'files-elementor-widget') . '</option>';

    $schools = get_posts([
        'post_type' => 'school',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
    ]);
    foreach ($schools as $s) {
        printf(
            '<option value="%d"%s>%s</option>',
            $s->ID,
            selected($school_id, $s->ID, false),
            esc_html(get_the_title($s))
        );
    }
    echo '</select>';
    // 4) (Optional) If you still need the Media Library uploader, include this script.
    //    Otherwise you can remove this block entirely.
?>
    <script>
        jQuery(function($) {
            var file_frame;
            $('#fev_select_file_button').on('click', function(e) {
                e.preventDefault();
                if (file_frame) {
                    file_frame.open();
                    return;
                }
                file_frame = wp.media({
                    title: '<?php echo esc_js('Select or Upload File'); ?>',
                    button: {
                        text: '<?php echo esc_js('Use this file'); ?>'
                    },
                    multiple: false
                });
                file_frame.on('select', function() {
                    var attachment = file_frame.state().get('selection').first().toJSON();
                    $('#fev_file_attachment').val(attachment.id);
                    $('#fev_file_filename').text(attachment.filename);
                });
                file_frame.open();
            });
        });
    </script>
<?php
}

/**
 * 5) Save metabox data: file upload + school select.
 */
add_action('save_post_fev_file', __NAMESPACE__ . '\\fev_save_file_settings');
function fev_save_file_settings($post_id)
{
    // ─── 1) Nonce, autosave, post-type checks ─────────────────────
    if (
        empty($_POST['fev_file_settings_nonce'])
        || ! wp_verify_nonce($_POST['fev_file_settings_nonce'], 'fev_save_file_settings')
        || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        || get_post_type($post_id) !== 'fev_file'
    ) {
        return;
    }

    // ─── 1a) Grab the School ID immediately (always defined) ──────
    $school_id = isset($_POST['fev_file_school'])
        ? intval($_POST['fev_file_school'])
        : 0;
    error_log(__FUNCTION__ . " fired for post_id={$post_id}, school_id={$school_id}");

    // ─── 2) Handle the file upload (if one was submitted) ─────────
    if (! empty($_FILES['fev_file_upload']['tmp_name'])) {
        $file = $_FILES['fev_file_upload'];
        error_log(__FUNCTION__ . ' Upload array: ' . print_r($file, true));

        // 2a) Any PHP upload errors?
        if ($file['error'] !== UPLOAD_ERR_OK) {
            error_log(__FUNCTION__ . " PHP upload error code: {$file['error']}");
            add_action('admin_notices', function () {
                echo '<div class="notice notice-error"><p>'
                    . esc_html__('File upload failed: PHP error.', 'files-elementor-widget')
                    . '</p></div>';
            });
        }
        // 2b) No school selected?
        elseif (! $school_id) {
            error_log(__FUNCTION__ . ' No School selected, skipping file move.');
            add_action('admin_notices', function () {
                echo '<div class="notice notice-warning"><p>'
                    . esc_html__('No School selected; file not saved.', 'files-elementor-widget')
                    . '</p></div>';
            });
        }
        // 2c) Upload + move into school folder
        else {
            $uploads    = wp_upload_dir();
            $school_dir = trailingslashit($uploads['basedir'] . '/file-drive') . $school_id;
            error_log(__FUNCTION__ . " Target school_dir: {$school_dir}");

            // 3) Ensure the folder exists
            if (! file_exists($school_dir)) {
                $created = wp_mkdir_p($school_dir);
                error_log(
                    __FUNCTION__
                        . " mkdir_p({$school_dir}) returned " . var_export($created, true)
                );
            }

            // 4) Move the file
            $filename = sanitize_file_name($file['name']);
            $target   = $school_dir . '/' . $filename;
            error_log(__FUNCTION__ . " Moving {$file['tmp_name']} → {$target}");

            if (@move_uploaded_file($file['tmp_name'], $target)) {
                error_log(__FUNCTION__ . ' move_uploaded_file succeeded.');
                // Store path relative to uploads/
                $relative = str_replace(
                    trailingslashit($uploads['basedir']),
                    '',
                    $target
                );
                update_post_meta($post_id, '_fev_file_path', $relative);
                add_action('admin_notices', function () {
                    echo '<div class="notice notice-success"><p>'
                        . esc_html__('File uploaded successfully.', 'files-elementor-widget')
                        . '</p></div>';
                });
            } else {
                error_log(__FUNCTION__ . ' move_uploaded_file FAILED!');
                add_action('admin_notices', function () {
                    echo '<div class="notice notice-error"><p>'
                        . esc_html__('Failed to move uploaded file.', 'files-elementor-widget')
                        . '</p></div>';
                });
            }
        }
    }

    // ─── 5) Always save the selected School ID ────────────────────
    update_post_meta($post_id, '_fev_file_school', $school_id);
}
