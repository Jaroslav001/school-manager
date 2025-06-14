<?php
/**
 * Plugin Name: School Manager
 * Plugin URI:  https://example.com
 * Description: Creates a School CPT, allows Admins and Managers to assign schools to users, and restricts access based on assignments. Also customizes the admin menu icon and color for the Schools menu.
 * Version:     1.0.2
 * Author:      Your Name
 * Author URI:  https://example.com
 * License:     GPL2
 * Text Domain: school-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class School_Manager {
    public function __construct() {
        // Register CPT and roles
        add_action( 'init', [ $this, 'setup_roles' ], 20 );
        add_action( 'init', [ $this, 'register_school_cpt' ] );

        // Control user-edit capabilities for Managers
        add_filter( 'map_meta_cap', [ $this, 'filter_map_meta_cap' ], 10, 4 );

        // User profile metabox for Assigned Schools
        add_action( 'show_user_profile', [ $this, 'render_user_meta_box' ] );
        add_action( 'edit_user_profile', [ $this, 'render_user_meta_box' ] );
        add_action( 'personal_options_update', [ $this, 'save_user_meta' ] );
        add_action( 'edit_user_profile_update', [ $this, 'save_user_meta' ] );

        // Restrict front-end school page access
        add_action( 'template_redirect', [ $this, 'restrict_school_page_access' ] );

        // Customize admin menu icon and color
        add_action( 'admin_head', [ $this, 'admin_icon_style' ] );
    }

    public function setup_roles() {
        // Create Manager role
        if ( ! get_role( 'manager' ) ) {
            add_role( 'manager', 'Manager', [
                'read'       => true,
                'edit_users' => true,
                'list_users' => true,
            ] );
        }
        // Create Lector role
        if ( ! get_role( 'lector' ) ) {
            add_role( 'lector', 'Lector', [
                'read' => true,
            ] );
        }
    }

        public function register_school_cpt() {
        register_post_type( 'school', [
            'label'           => __( 'Schools', 'school-manager' ),
            'public'          => true,
            'has_archive'     => true,
            'rewrite'         => [ 'slug' => 'schools', 'with_front' => false ],
            'show_in_menu'    => true,
            'menu_icon'       => 'dashicons-welcome-learn-more',
            'supports'        => [ 'title', 'editor', 'thumbnail' ],
            'capability_type' => 'post',
            'show_in_rest'    => true,
        ] );

        // Ensure Elementor support for School CPT
        if ( function_exists( 'add_post_type_support' ) ) {
            add_post_type_support( 'school', 'elementor' );
        }
    }
    }

    public function filter_map_meta_cap( $caps, $cap, $user_id, $args ) {
        // Allow Managers only to edit Lector profiles
        if ( in_array( $cap, [ 'edit_user', 'edit_users', 'list_users', 'promote_user' ], true ) ) {
            $target_id = intval( $args[0] ?? 0 );
            if ( user_can( $user_id, 'manager' ) && $target_id ) {
                if ( ! user_can( $target_id, 'lector' ) ) {
                    $caps[] = 'do_not_allow';
                }
            }
        }
        return $caps;
    }

    public function render_user_meta_box( $user ) {
        // Only Admins, or Managers editing a Lector, may assign schools
        if ( ! current_user_can( 'administrator' ) && ! ( current_user_can( 'manager' ) && in_array( 'lector', (array) $user->roles, true ) ) ) {
            return;
        }

        // Fetch available schools
        $args = [ 'post_type' => 'school', 'posts_per_page' => -1 ];
        if ( current_user_can( 'manager' ) ) {
            $mine = get_user_meta( get_current_user_id(), 'assigned_schools', true ) ?: [];
            $args['post__in'] = array_map( 'intval', $mine );
        }
        $schools  = get_posts( $args );
        $assigned = get_user_meta( $user->ID, 'assigned_schools', true ) ?: [];

        echo '<h2>' . esc_html__( 'Assigned Schools', 'school-manager' ) . '</h2>';
        echo '<table class="form-table"><tr><th></th><td>';
        foreach ( $schools as $s ) {
            $checked = in_array( $s->ID, (array) $assigned, true ) ? 'checked' : '';
            printf(
                '<label><input type="checkbox" name="assigned_schools[]" value="%d" %s> %s</label><br>',
                $s->ID,
                $checked,
                esc_html( get_the_title( $s ) )
            );
        }
        echo '</td></tr></table>';
    }

    public function save_user_meta( $user_id ) {
        // Permission check
        if ( ! current_user_can( 'administrator' ) && ! ( current_user_can( 'manager' ) && user_can( $user_id, 'lector' ) ) ) {
            return;
        }
        $schools = array_map( 'intval', (array) ( $_POST['assigned_schools'] ?? [] ) );
        update_user_meta( $user_id, 'assigned_schools', $schools );
    }

    public function restrict_school_page_access() {
        if ( is_singular( 'school' ) ) {
            if ( current_user_can( 'administrator' ) ) {
                return;
            }
            $assigned = get_user_meta( get_current_user_id(), 'assigned_schools', true ) ?: [];
            $assigned = array_map( 'intval', (array) $assigned );
            $school_id = get_queried_object_id();
            if ( ! in_array( $school_id, $assigned, true ) ) {
                status_header( 403 );
                get_header();
                echo '<div class="elementor-message elementor-message-danger" style="margin:2em auto;max-width:600px;">';
                echo esc_html__( 'You do not have access to this school.', 'school-manager' );
                echo '</div>';
                get_footer();
                exit;
            }
        }
    }

    /**
     * Output custom admin CSS to recolor the Schools menu icon.
     */
    public function admin_icon_style() {
        ?>
        <style>
        /* Change the color of the dashicon */
        #menu-post-school .wp-menu-image:before {
            color: #1e73be; /* customize your color here */
        }
        </style>
        <?php
    }
}

// Initialize the plugin
new School_Manager();
