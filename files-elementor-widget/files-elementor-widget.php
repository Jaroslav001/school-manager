<?php

/**
 * Plugin Name: Files Elementor Widget
 * Description: Adds a custom Elementor widget to list files from a specified uploads folder.
 * Version:     1.0.0
 * Text Domain: files-elementor-widget
 */

if (! defined('ABSPATH')) {
    exit;
}

// 1️⃣ Register a "File" custom post type
add_action('init', 'fev_register_file_cpt');
function fev_register_file_cpt()
{
    $labels = [
        'name'               => _x('Files', 'post type general name', 'files-elementor-widget'),
        'singular_name'      => _x('File', 'post type singular name', 'files-elementor-widget'),
        'menu_name'          => _x('Files', 'admin menu', 'files-elementor-widget'),
        'name_admin_bar'     => _x('File', 'add new on admin bar', 'files-elementor-widget'),
        'add_new'            => _x('Add New', 'file', 'files-elementor-widget'),
        'add_new_item'       => __('Add New File', 'files-elementor-widget'),
        'new_item'           => __('New File', 'files-elementor-widget'),
        'edit_item'          => __('Edit File', 'files-elementor-widget'),
        'view_item'          => __('View File', 'files-elementor-widget'),
        'all_items'          => __('All Files', 'files-elementor-widget'),
        'search_items'       => __('Search Files', 'files-elementor-widget'),
        'not_found'          => __('No files found.', 'files-elementor-widget'),
        'not_found_in_trash' => __('No files in Trash.', 'files-elementor-widget'),
    ];

    $args = [
        'labels'             => $labels,
        'public'             => false,
        'show_ui'            => true,
        'show_in_menu'       => true,    // or true to place at top-level
        'menu_icon'          => 'dashicons-media-document',
        'capability_type'    => 'post',
        'supports'           => ['title'],
        'has_archive'        => false,
    ];

    register_post_type('fev_file', $args);
}

// 2.1️⃣ Add metabox for File settings
add_action('add_meta_boxes', 'fev_add_file_metabox');
function fev_add_file_metabox()
{
    add_meta_box(
        'fev_file_settings',           // ID
        __('File Settings', 'files-elementor-widget'), // Title
        'fev_file_metabox_callback',   // Callback
        'fev_file',                    // CPT
        'normal',                      // Context
        'high'                         // Priority
    );
}

/**
 * Renders the File Settings metabox.
 */
function fev_file_metabox_callback($post)
{
    // Use nonce for verification
    wp_nonce_field('fev_save_file_settings', 'fev_file_settings_nonce');

    // Retrieve existing values
    $file_id      = get_post_meta($post->ID, '_fev_file_attachment', true);
    $school_id    = get_post_meta($post->ID, '_fev_file_school', true);

    // 1) File selector
    echo '<p><strong>' . esc_html__('Select File', 'files-elementor-widget') . '</strong></p>';
    echo '<div>';
    echo '<input type="hidden" id="fev_file_attachment" name="fev_file_attachment" value="' . esc_attr($file_id) . '">';
    echo '<button class="button" id="fev_select_file_button">' . esc_html__('Choose or Upload', 'files-elementor-widget') . '</button>';
    if ($file_id) {
        echo ' <span id="fev_file_filename">' . esc_html(basename(get_attached_file($file_id))) . '</span>';
    }
    echo '</div>';

    // 2) School dropdown
    $schools = get_posts([
        'post_type'      => 'school',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ]);
    echo '<p><strong>' . esc_html__('Associate with School', 'files-elementor-widget') . '</strong></p>';
    echo '<select name="fev_file_school">';
    echo '<option value="">' . esc_html__('&mdash; Select a school &mdash;', 'files-elementor-widget') . '</option>';
    foreach ($schools as $school) {
        printf(
            '<option value="%d" %s>%s</option>',
            $school->ID,
            selected($school_id, $school->ID, false),
            esc_html(get_the_title($school))
        );
    }
    echo '</select>';

    // 3) Enqueue Media Uploader script
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

// 2.3️⃣ Save File Settings metabox
add_action('save_post', 'fev_save_file_settings');
function fev_save_file_settings($post_id)
{
    // Verify nonce
    if (
        empty($_POST['fev_file_settings_nonce'])
        || ! wp_verify_nonce($_POST['fev_file_settings_nonce'], 'fev_save_file_settings')
    ) {
        return;
    }
    // Bail on autosave or wrong post type
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (get_post_type($post_id) !== 'fev_file') return;

    // Sanitize & update file attachment ID
    $file_id = isset($_POST['fev_file_attachment']) ? intval($_POST['fev_file_attachment']) : '';
    update_post_meta($post_id, '_fev_file_attachment', $file_id);

    // Sanitize & update associated school
    $school_id = isset($_POST['fev_file_school']) ? intval($_POST['fev_file_school']) : '';
    update_post_meta($post_id, '_fev_file_school', $school_id);
}

// Wait for Elementor to initialize
add_action('elementor/init', function () {
    if (! class_exists('Elementor\Widget_Base')) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-warning"><p>';
            echo esc_html__('Files Elementor Widget: please install & activate Elementor.', 'files-elementor-widget');
            echo '</p></div>';
        });
        return;
    }

    // Register our widget once Elementor is ready
    add_action('elementor/widgets/register', function ($widgets_manager) {
        require_once plugin_dir_path(__FILE__) . 'includes/class-files-elementor-widget.php';
        $widgets_manager->register_widget_type(new \Files_Elementor_Widget());
    });
});
