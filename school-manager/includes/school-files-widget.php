<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class School_Files_Widget {
    public static function render_files_shortcode( $atts ) {
        // Temporary test
        return '<p>⚡️ School_Files_Widget loaded!</p>';
    }

    public static function init_elementor() {
        // no-op for now
    }
}
