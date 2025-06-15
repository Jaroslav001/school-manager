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

        // ─── NEW: School selector ──────────────────────────────
        $schools = get_posts([
            'post_type'      => 'school',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ]);
        $options = ['' => __('&mdash; Select a School &mdash;', 'files-elementor-widget')];
        foreach ($schools as $school) {
            $options[$school->ID] = get_the_title($school);
        }
        $this->add_control(
            'school_id',
            [
                'label'   => __('School', 'files-elementor-widget'),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'options' => $options,
            ]
        );
        // ───────────────────────────────────────────────────────

        $this->end_controls_section();
    }


    protected function render()
    {
        $settings  = $this->get_settings_for_display();
        $school_id = intval($settings['school_id']);

        // 1) Require login
        if (! is_user_logged_in()) {
            echo '<p>' . esc_html__('Please log in to view files.', 'files-elementor-widget') . '</p>';
            return;
        }

        // 2) Require a School selection
        if (! $school_id) {
            echo '<p>' . esc_html__('Please select a School in the widget settings.', 'files-elementor-widget') . '</p>';
            return;
        }

        // 3) Query your File CPT for that School
        $files = get_posts([
            'post_type'      => 'fev_file',  // your File CPT slug
            'posts_per_page' => -1,
            'meta_query'     => [[
                'key'   => '_fev_file_school',
                'value' => $school_id,
            ]]
        ]);

        // 4) If no files, bail
        if (empty($files)) {
            echo '<p>' . esc_html__('No files uploaded for this school.', 'files-elementor-widget') . '</p>';
            return;
        }

        // 5) Render the list
        echo '<ul class="files-widget-list">';
        foreach ($files as $file_post) {
            $att_id    = get_post_meta($file_post->ID, '_fev_file_attachment', true);
            $url       = $att_id ? wp_get_attachment_url($att_id) : '';
            $label     = get_the_title($file_post) ?: basename(get_attached_file($att_id));
            if ($url) {
                printf(
                    '<li><a href="%s" target="_blank" rel="noopener">%s</a></li>',
                    esc_url($url),
                    esc_html($label)
                );
            }
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
