<?php

/**
 * Class School_Files_Widget
 * 
 * Provides the [school_files] shortcode and integrates with Elementor.
 */

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class School_Files_Widget
{
    /**
     * Shortcode handler for [school_files]
     * Maps folder to school, enforces access, and lists files.
     *
     * @param array $atts
     * @return string
     */
    public static function render_files_shortcode($atts)
    {
        // 1) Only logged-in users
        if (! is_user_logged_in()) {
            return '<p>' . esc_html__('Please log in to view these files.', 'school-manager') . '</p>';
        }

        // 2) Determine folder slug: from shortcode or current school
        $atts   = shortcode_atts(['folder' => ''], $atts, 'school_files');
        $folder = sanitize_title($atts['folder']);
        if (empty($folder)) {
            global $post;
            if (isset($post) && 'school' === get_post_type($post)) {
                $folder = $post->post_name;
            } else {
                return '<p>' . esc_html__('No folder specified.', 'school-manager') . '</p>';
            }
        }

        // 3) Map slug to School post
        $school = get_page_by_path($folder, OBJECT, 'school');
        if (! $school) {
            return '<p>' . esc_html__('Folder not found.', 'school-manager') . '</p>';
        }

        // 4) Enforce assignment access
        if (! current_user_can('administrator')) {
            $assigned = get_user_meta(get_current_user_id(), 'assigned_schools', true) ?: [];
            if (! in_array($school->ID, (array) $assigned, true)) {
                return '<p>' . esc_html__('You do not have permission to view these files.', 'school-manager') . '</p>';
            }
        }

        // 5) Build upload paths
        $uploads  = wp_upload_dir();
        $base_dir = trailingslashit($uploads['basedir']) . 'file-drive/';
        $base_url = trailingslashit($uploads['baseurl']) . 'file-drive/';
        $dir      = $base_dir . $folder;

        if (! is_dir($dir)) {
            return '<p>' . esc_html__('Folder not found.', 'school-manager') . '</p>';
        }

        // 6) Scan and output files
        $files = array_diff(scandir($dir), ['.', '..']);
        if (empty($files)) {
            return '<p>' . esc_html__('No files in this folder.', 'school-manager') . '</p>';
        }

        $output = '<ul class="school-files">';
        foreach ($files as $file) {
            $url   = esc_url($base_url . rawurlencode($folder) . '/' . rawurlencode($file));
            $label = esc_html($file);
            $output .= sprintf('<li><a href="%s" target="_blank" rel="noopener">%s</a></li>', $url, $label);
        }
        $output .= '</ul>';

        return $output;
    }

    /**
     * Initialize Elementor widget registration.
     */
    public static function init_elementor()
    {
        if (did_action('elementor/loaded')) {
            add_action('elementor/widgets/register', [__CLASS__, 'register_widget']);
        }
    }

    /**
     * Register the Elementor widget.
     *
     * @param \\Elementor\Widgets_Manager $widgets_manager
     */
    public static function register_widget($widgets_manager)
    {
        // To be implemented: include and register your custom widget class
        // require_once __DIR__ . '/school-files-elementor-widget.php';
        // $widgets_manager->register( new \School_Files_Elementor_Widget() );
    }
}
