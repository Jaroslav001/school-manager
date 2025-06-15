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
