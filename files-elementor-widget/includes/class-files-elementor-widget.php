<?php
if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class Files_Elementor_Widget extends Widget_Base
{

    public function get_name()
    {
        return 'files_widget';
    }

    public function get_title()
    {
        return __('Files', 'files-elementor-widget');
    }

    public function get_icon()
    {
        return 'eicon-file';
    }

    public function get_categories()
    {
        return ['general'];
    }

    protected function register_controls()
    {
        $this->start_controls_section(
            'section_content',
            ['label' => __('Content', 'files-elementor-widget')]
        );

        $this->add_control(
            'folder',
            [
                'label'       => __('Folder Name', 'files-elementor-widget'),
                'type'        => Controls_Manager::TEXT,
                'placeholder' => 'e.g. my-school-files',
                'description' => __('Folder under wp-content/uploads/file-drive/ containing your files.', 'files-elementor-widget'),
            ]
        );

        $this->end_controls_section();
    }

    protected function render()
    {
        $settings = $this->get_settings_for_display();
        $folder   = sanitize_file_name($settings['folder']);

        // 1) Require login
        if (! is_user_logged_in()) {
            echo '<p>' . esc_html__('Please log in to view files.', 'files-elementor-widget') . '</p>';
            return;
        }

        // 2) Require folder name
        if (empty($folder)) {
            echo '<p>' . esc_html__('Folder name not specified.', 'files-elementor-widget') . '</p>';
            return;
        }

        // 3) Build paths
        $uploads  = wp_upload_dir();
        $base_dir = trailingslashit($uploads['basedir']) . 'file-drive/';
        $base_url = trailingslashit($uploads['baseurl']) . 'file-drive/';
        $dir      = $base_dir . $folder;

        // 4) Directory checks
        if (! is_dir($dir)) {
            printf(
                '<p>%s: <strong>%s</strong></p>',
                esc_html__('Folder not found', 'files-elementor-widget'),
                esc_html($folder)
            );
            return;
        }

        // 5) Scan & list
        $files = array_diff(scandir($dir), ['.', '..']);
        if (empty($files)) {
            echo '<p>' . esc_html__('No files found in this folder.', 'files-elementor-widget') . '</p>';
            return;
        }

        echo '<ul class="files-widget-list">';
        foreach ($files as $file) {
            $url   = esc_url($base_url . rawurlencode($folder) . '/' . rawurlencode($file));
            $label = esc_html($file);
            printf('<li><a href="%s" target="_blank" rel="noopener">%s</a></li>', $url, $label);
        }
        echo '</ul>';
    }

    protected function _content_template()
    {
?>
        <#
            var folder=settings.folder;
            if ( ! folder ) {
            print( 'Folder name not specified.' );
            } else {
            print( '<p>Preview not available in the editor.</p>' );
            }
            #>
    <?php
    }
}
