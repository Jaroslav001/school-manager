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

add_action('post_edit_form_tag',  'fev_add_form_enctype');
add_action('post_new_form_tag',   'fev_add_form_enctype');
function fev_add_form_enctype()
{
    echo ' enctype="multipart/form-data"';
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
        'supports'           => [],
        'has_archive'        => false,
    ];

    register_post_type('fev_file', $args);
}

add_action('init', function () {
    remove_post_type_support('fev_file', 'title', 'content');
}, 21);

/**
 * Enqueue the WP Media Uploader on our File CPT screens.
 */
add_action('admin_enqueue_scripts', 'fev_enqueue_media_uploader');
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
 *
 * @param WP_Post $post The current post object.
 */
function fev_file_metabox_callback($post)
{
    // Nonce for security
    wp_nonce_field('fev_save_file_settings', 'fev_file_settings_nonce');

    // 1) Get existing values
    $existing   = get_post_meta($post->ID, '_fev_file_path', true);
    $school_id  = intval(get_post_meta($post->ID, '_fev_file_school', true));

    // 2) Native file input
    echo '<p><strong>' . esc_html__('Upload File', 'files-elementor-widget') . '</strong></p>';
    echo '<input type="file" name="fev_file_upload" /><br>';
    if ($existing) {
        echo '<em>' . esc_html(basename($existing)) . '</em>';
    }

    // 3) School dropdown
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


add_action('save_post', 'fev_save_file_settings');
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

// 1) Define custom columns for the fev_file list
add_filter('manage_fev_file_posts_columns', 'fev_custom_file_columns');
function fev_custom_file_columns($columns)
{
    $new = [];
    // keep the checkbox column
    if (isset($columns['cb'])) {
        $new['cb'] = $columns['cb'];
    }
    // our File Name column
    $new['file_name']  = __('File Name', 'files-elementor-widget');
    // (optional) keep the date column at the end
    if (isset($columns['date'])) {
        $new['date'] = $columns['date'];
    }
    return $new;
}

// 2) Output File Name in our custom column
add_action('manage_fev_file_posts_custom_column', 'fev_custom_file_column_data', 10, 2);
function fev_custom_file_column_data($column, $post_id)
{
    if ('file_name' === $column) {
        // Pull the stored relative path
        $rel = get_post_meta($post_id, '_fev_file_path', true);

        if ($rel) {
            // Get just the filename
            $filename = basename($rel);
            // URL to edit this CPT entry
            $edit_link = get_edit_post_link($post_id);

            // Wrap in <strong><a class="row-title">…</a></strong>
            printf(
                '<strong><a href="%s" class="row-title">%s</a></strong>',
                esc_url($edit_link),
                esc_html($filename)
            );
        } else {
            echo '&mdash;';
        }
    }
}
/**
 * 1) Register 'file_name' as a sortable column in the Files CPT list table.
 */
add_filter('manage_edit-fev_file_sortable_columns', 'fev_sortable_file_columns');
function fev_sortable_file_columns($columns)
{
    $columns['file_name'] = 'file_name';
    $columns['file_school'] = 'file_school';
    return $columns;
}

/**
 * 2) When ordering by 'file_name', actually sort by our metakey '_fev_file_path'.
 */
add_filter('request', 'fev_file_sort_request');
function fev_file_sort_request($vars)
{
    if (empty($vars['post_type']) || $vars['post_type'] !== 'fev_file') {
        return $vars;
    }

    if (isset($vars['orderby'])) {
        switch ($vars['orderby']) {
            case 'file_name':
                $vars['meta_key']  = '_fev_file_path';
                $vars['orderby']   = 'meta_value';
                $vars['meta_type'] = 'CHAR';
                break;

            case 'file_school':
                $vars['meta_key']  = '_fev_file_school';
                $vars['orderby']   = 'meta_value_num'; // numeric sort by School ID
                break;
        }
    }

    return $vars;
}


/**
 * 1) Add a “School” column to the Files CPT list.
 */
add_filter('manage_fev_file_posts_columns', 'fev_add_school_column');
function fev_add_school_column($columns)
{
    $new = [];

    // Keep the checkbox first
    if (isset($columns['cb'])) {
        $new['cb'] = $columns['cb'];
    }

    // Your File Name column
    if (isset($columns['file_name'])) {
        $new['file_name'] = $columns['file_name'];
    }

    // ── New School column ──
    $new['file_school'] = __('School', 'files-elementor-widget');

    // Preserve the date column
    if (isset($columns['date'])) {
        $new['date'] = $columns['date'];
    }

    return $new;
}

/**
 * 2) Output the assigned School’s title in our new column.
 */
add_action('manage_fev_file_posts_custom_column', 'fev_show_school_column', 10, 2);
function fev_show_school_column($column, $post_id)
{
    if ('file_school' === $column) {
        $school_id = get_post_meta($post_id, '_fev_file_school', true);
        if ($school_id) {
            $school = get_post($school_id);
            echo esc_html($school ? $school->post_title : '');
        } else {
            echo '&mdash;'; // placeholder when none selected
        }
    }
}

/**
 * 1) Add a “Filter by School” dropdown above the Files CPT list table.
 */
add_action('restrict_manage_posts', 'fev_add_school_filter', 10, 2);
function fev_add_school_filter($post_type, $which)
{
    if ($post_type !== 'fev_file') {
        return;
    }

    // Grab current selection, if any
    $selected = isset($_GET['school_filter']) ? intval($_GET['school_filter']) : '';

    // Build the dropdown
    echo '<select name="school_filter" id="dropdown_school_filter">';
    echo '<option value="">' . esc_html__('— Filter by School —', 'files-elementor-widget') . '</option>';

    $schools = get_posts([
        'post_type'      => 'school',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ]);

    foreach ($schools as $school) {
        printf(
            '<option value="%1$d"%2$s>%3$s</option>',
            $school->ID,
            selected($selected, $school->ID, false),
            esc_html(get_the_title($school))
        );
    }
    echo '</select>';
}

/**
 * 2) When the filter is submitted, modify the admin query to only show files for that school.
 */
add_action('pre_get_posts', 'fev_apply_school_filter');
function fev_apply_school_filter(\WP_Query $query)
{
    global $pagenow;

    // Only modify the main admin list query on edit.php for fev_file
    if (
        is_admin()
        && $pagenow === 'edit.php'
        && $query->is_main_query()
        && $query->get('post_type') === 'fev_file'
        && ! empty($_GET['school_filter'])
    ) {
        $school_id = intval($_GET['school_filter']);
        $meta_query = [
            [
                'key'   => '_fev_file_school',
                'value' => $school_id,
                'compare' => '=',
            ],
        ];
        $query->set('meta_query', $meta_query);
    }
}

/**
 * When a fev_file post is permanently deleted, also delete its uploaded file.
 */
add_action('before_delete_post', 'fev_delete_physical_file');
function fev_delete_physical_file($post_id)
{
    // Only run on our File CPT
    if (get_post_type($post_id) !== 'fev_file') {
        return;
    }

    // Get the relative path we saved earlier
    $rel_path = get_post_meta($post_id, '_fev_file_path', true);
    if (! $rel_path) {
        return;
    }

    // Build the full filesystem path
    $uploads   = wp_upload_dir();
    $full_path = trailingslashit($uploads['basedir']) . $rel_path;

    // If the file exists, delete it
    if (file_exists($full_path)) {
        @unlink($full_path);
    }

    // Clean up: if the school folder is now empty, remove it too
    $school_dir = dirname($full_path);
    if (is_dir($school_dir)) {
        $contents = array_diff(scandir($school_dir), ['.', '..']);
        if (empty($contents)) {
            @rmdir($school_dir);
        }
    }
}

add_action('wp_enqueue_scripts', 'fev_enqueue_material_icons');
function fev_enqueue_material_icons()
{
    wp_enqueue_style(
        'material-icons',
        'https://fonts.googleapis.com/icon?family=Material+Icons',
        [],
        null
    );
}


/**
 * Inject each assigned School as a submenu item under
 * the Custom Link titled "Schools", in any wp_nav_menu
 * (including Elementor's Nav Menu widget).
 */
add_filter('wp_get_nav_menu_items', 'sm_inject_schools_into_menu', 10, 3);
function sm_inject_schools_into_menu($items, $menu, $args)
{
    $new = [];

    foreach ($items as $item) {
        $new[] = $item;

        // Look for the placeholder menu-item titled exactly "Schools"
        if ('Schools' === $item->title && 'custom' === $item->type) {
            // Get the current user’s assigned school IDs
            $assigned = get_user_meta(get_current_user_id(), 'assigned_schools', true);
            if (is_array($assigned) && $assigned) {
                $order = $item->menu_order;
                foreach ($assigned as $school_id) {
                    $school = get_post(intval($school_id));
                    if (! $school) {
                        continue;
                    }
                    // Clone the placeholder and override
                    $sub                     = clone $item;
                    $sub->ID                 = 1000000 + $school->ID; // unique
                    $sub->db_id              = 0;
                    $sub->title              = get_the_title($school);
                    $sub->url                = get_permalink($school);
                    $sub->menu_item_parent   = $item->ID;              // child of “Schools”
                    $sub->menu_order         = ++$order;
                    $sub->classes            = ['menu-item', 'menu-item-school'];
                    // push it into our new list
                    $new[] = $sub;
                }
            }
        }
    }

    return $new;
}
