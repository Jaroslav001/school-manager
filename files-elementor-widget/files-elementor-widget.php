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

// Autoload via Composer if present
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php';
}

// Otherwise, manually include:
require_once __DIR__ . '/includes/cpt-file-registration.php';
require_once __DIR__ . '/includes/cpt-file-meta-boxes.php';
require_once __DIR__ . '/includes/cpt-file-admin-columns.php';

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

add_action('admin_enqueue_scripts', 'fev_enqueue_admin_menu_colors');
function fev_enqueue_admin_menu_colors()
{
    // Build the full filesystem path to our CSS
    $css_file = plugin_dir_path(__FILE__) . 'assets/css/admin-menu-colors.css';

    if (file_exists($css_file)) {
        // Use filemtime() so you don’t have to bump the version manually
        wp_enqueue_style(
            'fev-admin-menu-colors',
            plugin_dir_url(__FILE__) . 'assets/css/admin-menu-colors.css',
            [],
            filemtime($css_file)
        );
    } else {
        error_log('❌ Admin CSS NOT found: ' . $css_file);
    }
}


// Schools-menu injector
require_once plugin_dir_path(__FILE__) . 'includes/schools-menu.php';
