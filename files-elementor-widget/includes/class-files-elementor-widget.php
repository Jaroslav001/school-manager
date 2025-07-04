<?php
if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use Elementor\Widget_Base;

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
        // ─── 0) Inline Drive-style CSS ─────────────────────────────────────────────
        echo '<style>
                /* Wrapper */
                .gd-files-wrapper { font-family: Roboto, sans-serif; max-width: 800px; margin: 1em auto; }

                /* List vs grid */
                .gd-file-list { list-style: none; margin: 0; padding: 0; display: grid; grid-gap: .5em; }
                /* Items */
                .gd-file-item { display: flex; align-items: center; padding: .6em .8em; border-radius: 4px; transition: background .2s; }
                .gd-file-item:hover { background: #f1f3f4; }
                /* Icons */
                .gd-icon { font-family: "Material Icons"; font-size: 36px; margin-right: .8em; color: #5f6368; }
                /* File info */
                .gd-file-info { overflow: hidden; }
                .gd-file-name a { color:  var(--e-global-color-secondary); font-size: 16px; font-weight: 700; text-decoration: none; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block; }
                .gd-file-name a:hover { text-decoration: underline; }
                .gd-file-meta { color: #5f6368; font-size: 13px; margin-top: .2em; }
                .gd-icon-pdf           { color: #ea4335; } /* red */
                .gd-icon-doc           { color: #4285f4; } /* blue */
                .gd-icon-excel         { color: #0f9d58; } /* green */
                .gd-icon-powerpoint    { color: #fbbc04; } /* orange */
                .gd-icon-zip           { color: #f4b400; } /* yellow */
                .gd-icon-image         { color: #a142f4; } /* purple */
                .gd-icon-default       { color: #5f6368; } /* gray */
            </style>';

        // ─── 1) Enqueue Material Icons (if not already) ─────────────────────────
        wp_enqueue_style('material-icons', 'https://fonts.googleapis.com/icon?family=Material+Icons', [], null);



        $settings  = $this->get_settings_for_display();
        $school_id = intval($settings['school_id']);

        wp_enqueue_style('dashicons');

        if (! is_user_logged_in()) {
            echo '<p>' . esc_html__('Please log in to view files.', 'files-elementor-widget') . '</p>';
            return;
        }
        if (! $school_id) {
            echo '<p>' . esc_html__('Please select a School in the widget settings.', 'files-elementor-widget') . '</p>';
            return;
        }

        $files = get_posts([
            'post_type'      => 'fev_file',
            'posts_per_page' => -1,
            'meta_query'     => [[
                'key'   => '_fev_file_school',
                'value' => $school_id,
            ]],
        ]);

        if (empty($files)) {
            echo '<p>' . esc_html__('No files uploaded for this school.', 'files-elementor-widget') . '</p>';
            return;
        }

        $uploads  = wp_upload_dir();
        $base_url = trailingslashit($uploads['baseurl']);

        // Ensure Material Icons are available
        wp_enqueue_style('material-icons');

        echo '<div class="gd-files-wrapper">';
        echo '<ul class="gd-file-list">';

        foreach ($files as $file_post) {
            $rel_path = get_post_meta($file_post->ID, '_fev_file_path', true);
            if (! $rel_path) {
                continue;
            }
            $url      = esc_url($base_url . $rel_path);
            $filename = esc_html(basename($rel_path));
            $ext      = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            // Modified date (for meta) – use post_date_gmt
            $mod_date = get_the_modified_date('M j, Y', $file_post);

            // Map to Material Icon names
            switch ($ext) {
                case 'pdf':
                    $mi = 'picture_as_pdf';
                    $cls = 'gd-icon-pdf';
                    break;
                case 'doc':
                case 'docx':
                    $mi = 'description';
                    $cls = 'gd-icon-doc';
                    break;
                case 'xls':
                case 'xlsx':
                    $mi = 'table_chart';
                    $cls = 'gd-icon-excel';
                    break;
                case 'ppt':
                case 'pptx':
                    $mi = 'slideshow';
                    $cls = 'gd-icon-powerpoint';
                    break;
                case 'zip':
                case 'rar':
                    $mi = 'folder_zip';
                    $cls = 'gd-icon-zip';
                    break;
                case 'jpg':
                case 'jpeg':
                case 'png':
                case 'gif':
                    $mi = 'image';
                    $cls = 'gd-icon-image';
                    break;
                default:
                    $mi = 'insert_drive_file';
                    $cls = 'gd-icon-insert_drive_file';
                    break;
            }

            // Output the list item
            printf(
                '<li class="gd-file-item">
                    <i class="material-icons gd-icon  %1$s">%2$s</i>
                    <div class="gd-file-info">
                      <div class="gd-file-name"><a href="%3$s" target="_blank" rel="noopener">%4$s</a></div>
                      <div class="gd-file-meta">%5$s • %6$s</div>
                    </div>
                </li>',
                esc_attr($cls),
                esc_html($mi),
                $url,
                $filename,
                esc_html(strtoupper($ext)),
                esc_html($mod_date)
            );
        }
        // Close the list
        echo '</ul>';
        echo '</div>'; // .gd-files-wrapper
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
