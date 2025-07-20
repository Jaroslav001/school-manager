<?php

add_action('before_delete_post', 'fev_delete_physical_file');
function fev_delete_physical_file($post_id)
{
    if (get_post_type($post_id) !== 'fev_file') {
        return;
    }

    $rel_path = get_post_meta($post_id, '_fev_file_path', true);
    if (! $rel_path) {
        return;
    }

    $uploads   = wp_upload_dir();
    $full_path = trailingslashit($uploads['basedir']) . $rel_path;

    if (file_exists($full_path)) {
        @unlink($full_path);
    }

    $school_dir = dirname($full_path);
    if (is_dir($school_dir)) {
        $contents = array_diff(scandir($school_dir), ['.', '..']);
        if (empty($contents)) {
            @rmdir($school_dir);
        }
    }
}
