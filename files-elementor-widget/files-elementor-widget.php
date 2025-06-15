<?php
/**
 * Plugin Name: Files Elementor Widget
 * Plugin URI:  https://example.com
 * Description: Adds a custom Elementor widget to list files from a specified uploads folder, gated to logged-in users.
 * Version:     1.0.0
 * Author:      Your Name
 * Author URI:  https://example.com
 * Text Domain: files-elementor-widget
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Include the widget class
require_once plugin_dir_path( __FILE__ ) . 'includes/class-files-elementor-widget.php';

// Register the widget with Elementor
add_action( 'elementor/widgets/register', function( $widgets_manager ) {
    $widgets_manager->register_widget_type( new \Files_Elementor_Widget() );
} );
