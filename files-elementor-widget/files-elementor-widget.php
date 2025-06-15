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
