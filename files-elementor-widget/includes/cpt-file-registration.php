<?php

namespace FEV;

// 1️⃣ Register a "File" custom post type
add_action('init', __NAMESPACE__ . '\\fev_register_file_cpt');
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
        'menu_position'   => 22, // under Schools (21)
        'capability_type'    => 'post',
        'supports'           => [],
        'has_archive'        => false,
    ];

    register_post_type('fev_file', $args);
}
