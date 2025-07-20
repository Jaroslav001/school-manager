<?php

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
    $css_file = plugin_dir_path(__FILE__, 2) . 'assets/css/admin-menu-colors.css';

    if (file_exists($css_file)) {
        wp_enqueue_style(
            'fev-admin-menu-colors',
            plugin_dir_url(__FILE__) . '../assets/css/admin-menu-colors.css',
            [],
            filemtime($css_file)
        );
    } else {
        error_log('❌ Admin CSS NOT found: ' . $css_file);
    }
}
