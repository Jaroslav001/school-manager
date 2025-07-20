<?php
// includes/admin-columns.php
if (! defined('ABSPATH')) {
    exit;
}

/**
 * 1) Define which columns show on the Files CPT list.
 */
add_filter('manage_fev_file_posts_columns', function ($columns) {
    return [
        'cb'          => $columns['cb'],
        'file_name'   => __('File Name', 'files-elementor-widget'),
        'file_school' => __('School',    'files-elementor-widget'),
        'date'        => $columns['date'],
    ];
});

/**
 * 2) Render our custom columns.
 */
add_action('manage_fev_file_posts_custom_column', function ($column, $post_id) {
    if ($column === 'file_name') {
        $path = get_post_meta($post_id, '_fev_file_path', true);
        if ($path) {
            printf(
                '<strong><a href="%s" class="row-title">%s</a></strong>',
                esc_url(get_edit_post_link($post_id)),
                esc_html(basename($path))
            );
        } else {
            echo '&mdash;';
        }
    }

    if ($column === 'file_school') {
        $sid = get_post_meta($post_id, '_fev_file_school', true);
        if ($sid && ($s = get_post($sid))) {
            echo esc_html($s->post_title);
        } else {
            echo '&mdash;';
        }
    }
}, 10, 2);

/**
 * 3) Make our columns sortable.
 */
add_filter('manage_edit-fev_file_sortable_columns', function ($cols) {
    $cols['file_name']   = 'file_name';
    $cols['file_school'] = 'file_school';
    return $cols;
});

/**
 * 4) Tell WP how to sort them.
 */
add_filter('request', function ($vars) {
    if (isset($vars['post_type']) && $vars['post_type'] === 'fev_file') {
        if (! empty($vars['orderby'])) {
            switch ($vars['orderby']) {
                case 'file_name':
                    $vars['meta_key'] = '_fev_file_path';
                    $vars['orderby']  = 'meta_value';
                    break;
                case 'file_school':
                    $vars['meta_key'] = '_fev_file_school';
                    $vars['orderby']  = 'meta_value_num';
                    break;
            }
        }
    }
    return $vars;
});
