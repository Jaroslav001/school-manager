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
require_once __DIR__ . '/includes/cpt-file-delete-upload.php';
require_once __DIR__ . '/includes/enqueue-assets.php';

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
// add_action('pre_get_posts', 'fev_apply_school_filter');
// function fev_apply_school_filter(\WP_Query $query)
// {
//     global $pagenow;

//     // Only modify the main admin list query on edit.php for fev_file
//     if (
//         is_admin()
//         && $pagenow === 'edit.php'
//         && $query->is_main_query()
//         && $query->get('post_type') === 'fev_file'
//         && ! empty($_GET['school_filter'])
//     ) {
//         $school_id = intval($_GET['school_filter']);
//         $meta_query = [
//             [
//                 'key'   => '_fev_file_school',
//                 'value' => $school_id,
//                 'compare' => '=',
//             ],
//         ];
//         $query->set('meta_query', $meta_query);
//     }
// }


// Schools-menu injector
require_once plugin_dir_path(__FILE__) . 'includes/schools-menu.php';
